#!/usr/bin/php -q
<?php

echo "Der Langrisser Event Dumper (cli)\n";
echo "Copyright (c) 2018 Derrick Sobodash\n";

// Convert integer to hexadecimal string
function hexstr($byte) {
  if(strlen(dechex($byte)) % 2 > 0)
    return("0x" . str_pad(dechex($byte), strlen(dechex($byte)) + 1, "0", STR_PAD_LEFT));
  else
    return("0x" . dechex($byte));
}

// Read byte value from filestream
function fgetb($fd) {
  return(ord(fgetc($fd)));
}

// Sanitize formatted game script
function sctxt($text) {
  // Remove font tags
  $text = str_replace(
            array("{font0}", "{font1}", "{font2}", "{font3}", "{font4}"),
            array("", "", "", "", ""),
            $text);
  
  // Remove skip tags
  while(strpos($text, "{skip")) {
    $text = preg_replace("/\{skip[^}]+\}/", "", $text);
  }
  
  // Transform tags
  $text = str_replace(
              array("{06}", "{07}", "{02}", "{03}", "{end}", "{3a}", "{37}", "{38}"),
              array("\\r", "\\n", "_NAME_", "_NUM_", "\\0", "o", "a", "a"),
              $text);
  
  return($text);
}

set_time_limit(6000000);

// Read definitions to arrays
if (!file_exists("resources/define/bgm.txt"))
	die("Fatal error: BGM definitions not found.\n");
$ar_bgm = explode("\n", file_get_contents("resources/define/bgm.txt"));

if (!file_exists("resources/define/item.txt"))
	die("Fatal error: Item definitions not found.\n");
$ar_item = explode("\n", file_get_contents("resources/define/item.txt"));

if (!file_exists("resources/define/portrait.txt"))
	die("Fatal error: Portrait definitions not found.\n");
$ar_portrait = explode("\n", file_get_contents("resources/define/portrait.txt"));

if (!file_exists("resources/define/unit.txt"))
	die("Fatal error: Unit definitions not found.\n");
$ar_unit = explode("\n", file_get_contents("resources/define/unit.txt"));

if (!file_exists("resources/define/unit.txt"))
	die("Fatal error: Unit definitions not found.\n");
$ar_unit = explode("\n", file_get_contents("resources/define/unit.txt"));

if (!file_exists("resources/define/team.txt"))
	die("Fatal error: Unit definitions not found.\n");
$ar_team = explode("\n", file_get_contents("resources/define/team.txt"));

$ar_target = array("COMMANDER", "SUBUNIT");

// Build array of event scripts
$events = array_values(array_diff(scandir("resources/events"), array('..', '.', ".DS_Store")));

$pointers = array();

// Begin dumping
for($i = 0; $i < count($events); $i++) {
	$fd = fopen("resources/events/" . $events[$i], "rb");
	$fo = fopen("resources/scripts/event/" . substr($events[$i], 0, -3) . "txt", "w");
	
	// Get section pointers
	// uint_16[pointer]
	$begin = fgetb($fd) + (fgetb($fd) << 8);
	$section = array($begin);
	while(ftell($fd) < $section[0])
	  $section[] = fgetb($fd) + (fgetb($fd) << 8);
	
	// Movement Hooks
	// uint_8[priority] uint_8[unit] uint_8[turn] uint_8[unk1] uint_16[jump]
	fputs($fo, "// Movement-Triggered Events:\n" .
	           "// event.move.addHook(priority, goto, unit, turn, unk1)\n");
	while(ftell($fd) < $section[1]) {
	  $t_cmd = fgetb($fd);
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	    $pointers[] = $bytecode[4] + ($bytecode[5] << 8);
	    fputs($fo, "event.move.addHook(" .
	               "$bytecode[0], " .
	               "lbl_" . dechex($bytecode[4] + ($bytecode[5] << 8)) . ", " .
	               "{$ar_unit[$bytecode[1]]}, " .
	               "$bytecode[2], " .
	               hexstr($bytecode[3]) . ");\n");
	  }
	  else $t_cmd = fgetc($fd);
	} fputs($fo, "\n");

	// Attack Hooks
	// uint_8[priority] uint_8[attacker] uint_8[unk1] uint_8[reciever] uint_8[unk2] uint_8[unk3] uint_16[jump]
	fputs($fo, "// Attack-Triggered Events:\n" .
	           "// event.attack.addHook(priority, goto, attacker, defender, unk1, unk2, unk3)\n");
	while(ftell($fd) < $section[2]) {
	  $t_cmd = fgetb($fd);
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	    $pointers[] = $bytecode[6] + ($bytecode[7] << 8);
	    fputs($fo, "event.attack.addHook(" .
	               "$bytecode[0], " .
	               "lbl_" . dechex($bytecode[6] + ($bytecode[7] << 8)) . ", " .
	               "{$ar_unit[$bytecode[1]]}, " .
	               "{$ar_unit[$bytecode[3]]}, " .
	               hexstr($bytecode[2]) . ", " .
	               hexstr($bytecode[4]) . ", " .
	               hexstr($bytecode[5]) . ");\n");
	  }
	  else $t_cmd = fgetc($fd);
	} fputs($fo, "\n");
	
	// Defeat/Damage Hooks
	// uint_8[priority] uint_8[quantity] uint_8[unit] * uint_16[jump]
	// or
	// uint_8[priority] uint_8[0xff] uint_8[attacker] uint_8[unk1] uint_8[defender] uint_8[unk2] uint_16[jump]
	fputs($fo, "// Damage-Triggered Events:\n" .
	           "// event.defeat.addHook(priority, goto, unit, ...)\n" .
               "// event.damage.addHook(priority, goto, attacker, defender, unk1, unk2)\n");
	while(ftell($fd) < $section[3]) {
	  $t_cmd = fgetb($fd);
	  if($t_cmd != 255) {
	    $t_cmd2 = fgetb($fd);
	    // defeated
	    if($t_cmd2 != 255) {
	      $bytecode = array($t_cmd, $t_cmd2);
	      for($k = 0; $k < $t_cmd2; $k++)
	        $bytecode[] = fgetb($fd);
	      // Grab the goto address
	      $t_ptr = fgetb($fd) + (fgetb($fd) << 8);
	      $pointers[] = $t_ptr;
          fputs($fo, "event.defeat.addHook(" .
	                 "$bytecode[0], " .
	                 "lbl_" . dechex($t_ptr));
	      // Print out all the units
	      for($k = 2; $k < count($bytecode); $k++)
	        if($bytecode[$k] != 0)
	          fputs($fo, ", {$ar_unit[$bytecode[$k]]}");
	      fputs($fo, ");\n");
	    }
	    // damaged
	    else {
	      $bytecode = array($t_cmd, $t_cmd2, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	      $pointers[] = $bytecode[6] + ($bytecode[7] << 8);
          fputs($fo, "event.damage.addHook(" .
                     "$bytecode[0], " .
	                 "lbl_" . dechex($bytecode[6] + ($bytecode[7] << 8)) . ", " .
	                 "{$ar_unit[$bytecode[2]]}, " .
	                 "{$ar_unit[$bytecode[4]]}, " .
	                 hexstr($bytecode[3]) . ", " .
	                 hexstr($bytecode[5]) . ");\n");
	    }
	  }
	  else $t_cmd = fgetc($fd);
	} fputs($fo, "\n");
	
	// Position Hooks
	// uint_8[priority] uint_8[unit] uint_8[unit] uint_8[radius] uint_8[unk1] uint_8[unk2] uint_8[unk3] uint_8[unk4] uint_16[jump]
    // or
    // uint_8[priority] uint_8[unit] uint_8[unk1] uint_8[unk2] uint_8[x1] uint_8[y1] uint_8[x2] uint_8[y2] uint_16[jump]
	fputs($fo, "// Position-Triggered Events:\n" .
	           "// event.box.addHook(priority, goto, unit, unit, radius, unk1, unk2, unk3, unk4)\n" .
               "// event.radius.addHook(priority, goto, unit, x1, y1, x2, y2, unk1, unk2)\n");
	while(ftell($fd) < $section[4]) {
	  $t_cmd = fgetb($fd);
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	    $pointers[] = $bytecode[8] + ($bytecode[9] << 9);
        // box
	    if($bytecode[2] == 0 && $bytecode[3] == 0 )
          fputs($fo, "event.box.addHook(" .
                     "$bytecode[0], " .
	                 "lbl_" . dechex($bytecode[8] + ($bytecode[9] << 8)) . ", " .
                     "{$ar_unit[$bytecode[1]]}, " .
                     "$bytecode[4], " . 
                     "$bytecode[5], " . 
                     "$bytecode[6], " . 
                     "$bytecode[7], " . 
	                 hexstr($bytecode[2]) . ", " .
	                 hexstr($bytecode[3]) . ");\n");
	    // radius
	    else
          fputs($fo, "event.radius.addHook(" .
                     "$bytecode[0], " .
	                 "lbl_" . dechex($bytecode[8] + ($bytecode[9] << 8)) . ", " .
	                 "{$ar_unit[$bytecode[1]]}, " .
	                 "{$ar_unit[$bytecode[2]]}, " .
	                 "$bytecode[3], " .
	                 hexstr($bytecode[4]) . ", " .
	                 hexstr($bytecode[5]) . ", " .
	                 hexstr($bytecode[6]) . ", " .
	                 hexstr($bytecode[7]) . ");\n");
	  }
	  else $t_cmd = fgetc($fd);
	} fputs($fo, "\n");
	
	// Turn Hooks
	// uint_8[priority] uint_8[team] uint_8[turn] uint_8[unk1] uint_16[jump]
	fputs($fo, "// Turn-Triggered Events\n" .
	           "// event.turn.addHook(priority, goto, team, turn, unk1)\n");
	while(ftell($fd) < $section[5]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	    $pointers[] = $bytecode[4] + ($bytecode[5] << 8);
	    fputs($fo, "event.turn.addHook(" .
                   "$bytecode[0], " .
	               "lbl_" . dechex($bytecode[4] + ($bytecode[5] << 8)) . ", " .
	               "{$ar_team[$bytecode[1]]}, " .
	               "$bytecode[2], " .
	               hexstr($bytecode[3]) . ");\n");
	  }
	  else  $t_cmd = fgetc($fd);
	} fputs($fo, "\n");
	
	// Read core events
	fputs($fo, "// Core Events");
	$t_ptr = fgetb($fd) + (fgetb($fd) << 8);
	$pointers[] = $t_ptr;
	while(!feof($fd)) {
	  // Write a label for any referenced addresses
	  if(in_array(ftell($fd), $pointers))
	    fputs($fo, "\nlbl_" . dechex(ftell($fd)) . ":\n");
	  $code = fgetb($fd);	
	  switch($code) {
	    // Set Focus
	    // screen.focus.set(x, y, speed)
	    // uint_8[0x00] uint_8[speed] uint_8[x] uint_8[y]
	    // Hide Cursor
	    // screen.cursor.visible(cursor)
	    // uint_8[0x00] uint_8[0xfd] uint_8[cursor] uint_8[0x00]
	    case 0x00:
	      $t_code = fgetb($fd);
	      if($t_code == 0xfd) {
	        $t_visible = fgetb($fd);
	        $t_null = fgetb($fd);
	        fputs($fo, "screen.cusor.visible(" . 
	                   ($t_visible == 0 ? "TRUE" : "FALSE") . ");\n");
	      }
	      else if($t_code == 0xfe) {
	        $t_x = fgetb($fd);
	        $t_y = fgetb($fd);
	        fputs($fo, "screen.focus.set(" .
	                   "$t_x, " .
	                   "$t_y, " .
	                   "FAST);\n");
	      }
	      else if($t_code == 0xff) {
	        $t_x = fgetb($fd);
	        $t_y = fgetb($fd);
	        fputs($fo, "screen.focus.set(" .
	                   "$t_x, " .
	                   "$t_y, " .
	                   "SLOW);\n");
	      }
	      else {
	        $t_null = fgetb($fd);
	        $t_null = fgetb($fd);
	        fputs($fo, "screen.focus.set(" .
	                   "{$ar_unit[$t_code]});\n");
	      }
	      break;
	    
	    // Dialogue
	    // screen.talk(speaker, target, portrait, focus, line)
	    // uint_8[0x02] uint_8[speaker] uint_8[target] uint_8[portrait] uint_8[focus] uint_8[line]
	    case 0x02:
	      $t_speaker = fgetb($fd);
	      $t_target = fgetb($fd);
	      $t_portrait = fgetb($fd);
	      $t_focus = fgetb($fd);
	      $t_line = fgetb($fd);
	      fputs($fo, "screen.talk(" .
	                 "{$ar_unit[$t_speaker]}" .
	                 "{$ar_unit[$t_target]}" .
	                 "{$ar_portrait[$t_portrait]}" .
	                 ($t_focus == 0 ? "NOFOLLOW" : "FOLLOW") . ", " .
	                 "$t_line);\n");
	      fputs($fo, "// Dialogue\n");
	      break;
	    
	    // RAM Add/Sub
	    // ram.sum(target, variable)
	    // ram.sub(target, variable)
	    // uint_8[0x0b] uint_8[action] uint_8[value]
	    case 0x0b:
	      $t_action = fgetb($fd);
	      $t_value = fgetb($fd);
	      $t_upper = $t_value >> 3;
	      $t_lower = $t_value & 0x7;
	      if($t_action == 0)
	        fputs($fo, "ram.sum(" .
	                   "$" . dechex(0xa4788 + $t_upper) . ", " .
	                   "$" . dechex(0x7eb58 + $t_lower) . ");\n");
	      else if($t_action == 255)
	        fputs($fo, "ram.sub(" .
	                   "$" . dechex(0xa4788 + $t_upper) . ", " .
	                   "$" . dechex(0x7eb58 + $t_lower) . ");\n");
	      else
	        fputs($fo, "ram.sub(UNHANDLED EVAL: $t_value)");
	      break;
	    
	    // Change Music
	    // sound.setBGM(team, track)
	    // uint_8[0x0c] uint_8[unit] uint_8[song]
	    case 0x0c:
	      $t_team = fgetb($fd);
	      $t_track = fgetb($fd);
	      fputs($fo, "sound.setBGM(" .
	                 "$ar_team[$t_team], " .
	                 "$ar_bgm[$t_track]);\n");
	      break;
	    
	    // Deploy Unit
	    // screen.unit.showSub(unit)
	    // uint_8[0x1e] uint_8[unit]
	    case 0x1e:
	      fputs($fo, "screen.unit.showSub(" .
	                 $ar_unit[fgetb($fd)] . ");\n");
	      break;
	    
	    // Position Unit
	    // screen.unit.position(unit, x, y)
	    // uint_8[0x36] uint_8[unit] uint_8[x] uint_8[y]
	    case 0x36:
	      $t_unit = fgetb($fd);
	      $t_x = fgetb($fd);
	      $t_y = fgetb($fd);
	      fputs($fo, "screen.unit.position(" .
	                 "$ar_unit[$t_unit], " .
	                 "$t_x, " .
	                 "$t_y);\n");
	      break;
	    
	    // Hide Unit
	    // screen.unit.hide(unit, target)
	    // uint_8[0x37] uint_8[unit] uint_8[target]
	    case 0x37:
	      $t_unit = fgetb($fd);
	      $t_target = fgetb($fd);
	      fputs($fo, "screen.unit.hide(" .
	                 "$ar_unit[$t_unit], " .
	                 "$ar_target[$t_target]);\n");
	      break;
	    
	    // Fade In
	    // screen.fadeIn(time)
	    // unit_8[0x38] uint_8[time]
	    case 0x38:
	      fputs($fo, "screen.fadeIn(" .
	                 fgetb($fd) . ");\n");
	      break;

	    // Move Cusor to Commander
	    // screen.cusor.set(commander)
	    // uint_8[0x3d] uint_8[commander]
	    case 0x3d:
	      fputs($fo, "screen.cursor.set(" .
	                 $ar_unit[fgetb($fd)] . ");\n");
	      break;
	    
	    // NOP
	    case 0xff:
	      break;
	    
	    // Catch unsupported codes
	    default:
	      echo "Caught unhandled exception: \$code==" . hexstr($code) . "\n";
	      die();
	      break;
	  }
	}
}


/*
Section 6: Event Script
Depends on action code

Begins with jump to pre-battle deployment

3d xx - Move cusor to commander xx
screen.cursor.set(x)

37 xx yy - Set unit xx status to yy (00 = hidden)
screen.unit.hide(unit)

36 uu xx yy - Position unit uu at xx,yy (ffff = selected coordinates)
screen.unit.move(unit, x, y)
3e xx yy - xx faces yy
screen.unit.face(unit1, unit2)

1e uu - Reveal unit uu
08 xx yy - If xx=0 
screen.unit.show(unit)

In all hooks, first byte appears to be a priority byte
*/


?>