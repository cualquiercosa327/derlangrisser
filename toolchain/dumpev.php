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

// Convert integer to hexadecimal memory
function memstr($byte) {
  if(strlen(dechex($byte)) % 2 > 0)
    return("$" . str_pad(dechex($byte), strlen(dechex($byte)) + 1, "0", STR_PAD_LEFT));
  else
    return("$" . dechex($byte));
}

// Read byte value from filestream
function fgetb($fd) {
  return(ord(fgetc($fd)));
}

// Sanitize formatted game script
function sctxt($text) {
  $text = str_replace(array("\r", "\n"), array("", " "), trim($text));

  // Remove font tags
  $text = str_replace(
            array("{font0}", "{font1}", "{font2}", "{font3}", "{font4}"),
            array("", "", "", "", ""),
            $text);
  // Remove skip tags
  while(strpos($text, "{skip"))
    $text = preg_replace("/\{skip[^}]+\}/", "", $text);
  // Transform tags
  $text = str_replace(
              array("{06}", "{07}", "{02}", "{03}", "{end}", "{3a}", "{37}", "{38}"),
              array("\\r", "\\n", "_NAME_", "_NUM_", "\\0", "o", "a", "a"),
              $text);
  // Nuke stray spaces
  $text = str_replace(
            array("  ", " \\n", " \\r", "\\n ", "\\r "),
            array(" ", "\\n", "\\r", "\\n", "\\r"),
            $text
          );
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
	$ar_talk = explode("{end}", file_get_contents("resources/scripts/en/sc" . substr($events[$i], 2, 2) . ".txt"));
	
	// Get section pointers
	// uint_16[pointer]
	$begin = fgetb($fd) + (fgetb($fd) << 8);
	$section = array($begin);
	while(ftell($fd) < $section[0])
	  $section[] = fgetb($fd) + (fgetb($fd) << 8);
	
	// Movement Hooks
	// uint_8[priority] uint_8[unit] uint_8[turn] uint_8[unk1] uint_16[jump]
	fputs($fo, "# Movement-Triggered Events:\n" .
	           "# on.move(priority, goto, unit, turn, unk1)\n");
	while(ftell($fd) < $section[1]) {
	  $t_cmd = fgetb($fd);
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	    $pointers[] = $bytecode[4] + ($bytecode[5] << 8);
	    fputs($fo, "on.move(" .
	               "$bytecode[0], " .
	               "lbl_" . dechex($bytecode[4] + ($bytecode[5] << 8)) . ", " .
	               "{$ar_unit[$bytecode[1]]}, " .
	               "$bytecode[2], " .
	               hexstr($bytecode[3]) . ")\n");
	  }
	  else $t_cmd = fgetc($fd);
	} fputs($fo, "\n");

	// Attack Hooks
	// uint_8[priority] uint_8[attacker] uint_8[unk1] uint_8[reciever] uint_8[unk2] uint_8[unk3] uint_16[jump]
	fputs($fo, "# Attack-Triggered Events:\n" .
	           "# on.attack(priority, goto, attacker, defender, unk1, unk2, unk3)\n");
	while(ftell($fd) < $section[2]) {
	  $t_cmd = fgetb($fd);
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	    $pointers[] = $bytecode[6] + ($bytecode[7] << 8);
	    fputs($fo, "on.attack(" .
	               "$bytecode[0], " .
	               "lbl_" . dechex($bytecode[6] + ($bytecode[7] << 8)) . ", " .
	               "{$ar_unit[$bytecode[1]]}, " .
	               "{$ar_unit[$bytecode[3]]}, " .
	               hexstr($bytecode[2]) . ", " .
	               hexstr($bytecode[4]) . ", " .
	               hexstr($bytecode[5]) . ")\n");
	  }
	  else $t_cmd = fgetc($fd);
	} fputs($fo, "\n");
	
	// Defeat/Damage Hooks
	// uint_8[priority] uint_8[quantity] uint_8[unit] * uint_16[jump]
	// or
	// uint_8[priority] uint_8[0xff] uint_8[attacker] uint_8[unk1] uint_8[defender] uint_8[unk2] uint_16[jump]
	fputs($fo, "# Damage-Triggered Events:\n" .
	           "# on.defeat(priority, goto, unit, ...)\n" .
               "# on.damage(priority, goto, attacker, defender, unk1, unk2)\n");
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
          fputs($fo, "on.defeat(" .
	                 "$bytecode[0], " .
	                 "lbl_" . dechex($t_ptr));
	      // Print out all the units
	      for($k = 2; $k < count($bytecode); $k++)
	        if($bytecode[$k] != 0)
	          fputs($fo, ", {$ar_unit[$bytecode[$k]]}");
	      fputs($fo, ")\n");
	    }
	    // damaged
	    else {
	      $bytecode = array($t_cmd, $t_cmd2, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	      $pointers[] = $bytecode[6] + ($bytecode[7] << 8);
          fputs($fo, "on.damage(" .
                     "$bytecode[0], " .
	                 "lbl_" . dechex($bytecode[6] + ($bytecode[7] << 8)) . ", " .
	                 "{$ar_unit[$bytecode[2]]}, " .
	                 "{$ar_unit[$bytecode[4]]}, " .
	                 hexstr($bytecode[3]) . ", " .
	                 hexstr($bytecode[5]) . ")\n");
	    }
	  }
	  else $t_cmd = fgetc($fd);
	} fputs($fo, "\n");
	
	// Position Hooks
	// uint_8[priority] uint_8[unit] uint_8[unit] uint_8[radius] uint_8[unk1] uint_8[unk2] uint_8[unk3] uint_8[unk4] uint_16[jump]
    // or
    // uint_8[priority] uint_8[unit] uint_8[unk1] uint_8[unk2] uint_8[x1] uint_8[y1] uint_8[x2] uint_8[y2] uint_16[jump]
	fputs($fo, "# Position-Triggered Events:\n" .
	           "# on.bound(priority, goto, unit, unit, radius, unk1, unk2, unk3, unk4)\n" .
               "# on.range(priority, goto, unit, x1, y1, x2, y2, unk1, unk2)\n");
	while(ftell($fd) < $section[4]) {
	  $t_cmd = fgetb($fd);
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	    $pointers[] = $bytecode[8] + ($bytecode[9] << 8);
        // box
	    if($bytecode[2] == 0 && $bytecode[3] == 0 )
          fputs($fo, "on.bound(" .
                     "$bytecode[0], " .
	                 "lbl_" . dechex($bytecode[8] + ($bytecode[9] << 8)) . ", " .
                     "{$ar_unit[$bytecode[1]]}, " .
                     "$bytecode[4], " . 
                     "$bytecode[5], " . 
                     "$bytecode[6], " . 
                     "$bytecode[7], " . 
	                 hexstr($bytecode[2]) . ", " .
	                 hexstr($bytecode[3]) . ")\n");
	    // radius
	    else
          fputs($fo, "on.range(" .
                     "$bytecode[0], " .
	                 "lbl_" . dechex($bytecode[8] + ($bytecode[9] << 8)) . ", " .
	                 "{$ar_unit[$bytecode[1]]}, " .
	                 "{$ar_unit[$bytecode[2]]}, " .
	                 "$bytecode[3], " .
	                 hexstr($bytecode[4]) . ", " .
	                 hexstr($bytecode[5]) . ", " .
	                 hexstr($bytecode[6]) . ", " .
	                 hexstr($bytecode[7]) . ")\n");
	  }
	  else $t_cmd = fgetc($fd);
	} fputs($fo, "\n");
	
	// Turn Hooks
	// uint_8[priority] uint_8[team] uint_8[turn] uint_8[unk1] uint_16[jump]
	fputs($fo, "# Turn-Triggered Events\n" .
	           "# on.turn(priority, goto, team, turn, unk1)\n");
	while(ftell($fd) < $section[5]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd));
	    $pointers[] = $bytecode[4] + ($bytecode[5] << 8);
	    fputs($fo, "on.turn(" .
                   "$bytecode[0], " .
	               "lbl_" . dechex($bytecode[4] + ($bytecode[5] << 8)) . ", " .
	               "{$ar_team[$bytecode[1]]}, " .
	               "$bytecode[2], " .
	               hexstr($bytecode[3]) . ")\n");
	  }
	  else  $t_cmd = fgetc($fd);
	} fputs($fo, "\n");
	
	// Read core events
	fputs($fo, "# Core Events\n");
	$t_ptr = fgetb($fd) + (fgetb($fd) << 8);
	$pointers[] = $t_ptr;
	while(ftell($fd) < filesize("resources/events/" . $events[$i])) {
	  // Write a label for any referenced addresses
	  if(in_array(ftell($fd), $pointers))
	    fputs($fo, "lbl_" . dechex(ftell($fd)) . ":\n");
	  $code = fgetb($fd);	
	  switch($code) {
	    // Focus Point
	    // focus.point(x, y, speed)
	    // uint_8[0x00] uint_8[speed] uint_8[x] uint_8[y]
	    //
	    // Focus Unit
	    // focus.unit(unit)
	    // uint_8[0x00] uint_8[unit] uint_8[0x00] uint_8[0x00]
	    //
	    // Hide Cursor
	    // cursor.hide()
	    // uint_8[0x00] uint_8[0xfd] uint_8[0x00] uint_8[0x00]
	    //
	    // Show Cursor
	    // cursor.show()
	    // uint_8[0x00] uint_8[0xfd] uint_8[visible] uint_8[0x00]
	    case 0x00:
	      $t_code = fgetb($fd);
	      if($t_code == 0xfd) {
	        $t_visible = fgetb($fd);
	        fseek($fd, 1, SEEK_CUR);
	        fputs($fo, "  cursor." . 
	                   ($t_visible == 0 ? "show" : "hide") . "()\n");
	      }
	      else if($t_code == 0xfe) {
	        $t_x = fgetb($fd);
	        $t_y = fgetb($fd);
	        fputs($fo, "  focus.point(" .
	                   "$t_x, " .
	                   "$t_y, " .
	                   "FAST)\n");
	      }
	      else if($t_code == 0xff) {
	        $t_x = fgetb($fd);
	        $t_y = fgetb($fd);
	        fputs($fo, "  focus.point(" .
	                   "$t_x, " .
	                   "$t_y, " .
	                   "SLOW)\n");
	      }
	      else {
	        fseek($fd, 2, SEEK_CUR);
	        fputs($fo, "  focus.unit(" .
	                   "{$ar_unit[$t_code]})\n");
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
	      fputs($fo, "  msg(" .
	                 "{$ar_unit[$t_speaker]}, " .
	                 "{$ar_unit[$t_target]}, " .
	                 "{$ar_portrait[$t_portrait]}, " .
	                 ($t_focus == 0 ? "NOFOLLOW" : "FOLLOW") . ", " .
	                 "$t_line)\n");
	      fputs($fo, "# " . sctxt($ar_talk[$t_line - 1]) . "\\0\n");
	      break;
	    
	    // ifDead Conditional Jump
	    // branch.ifdead(goto, unit)
	    // uint_8[0x04] uint_8[unit] uint_16[goto]
	    case 0x04:
	      $t_unit = fgetb($fd);
	      $t_goto = fgetb($fd) + (fgetb($fd) << 8);
	      $pointers[] = $t_goto;
	      fputs($fo, "  branch.ifdead(" .
	                 "lbl_" . dechex($t_goto) . ", " .
	                 "{$ar_unit[$t_unit]})\n");
	      break;
	    
	    // ifAlive Conditional Jump
	    // branch.ifalive(goto, unit)
	    // uint_8[0x04] uint_8[unit] uint_16[goto]
	    case 0x05:
	      $t_unit = fgetb($fd);
	      $t_goto = fgetb($fd) + (fgetb($fd) << 8);
	      $pointers[] = $t_goto;
	      fputs($fo, "  branch.ifalive(" .
	                 "lbl_" . dechex($t_goto) . ", " .
	                 "{$ar_unit[$t_unit]})\n");
	      break;
	    
	    // Local RAM Add/Sub
	    // ram.local.sum(target, variable)
	    // ram.local.sub(target, variable)
	    // uint_8[0x08] uint_8[action] uint_8[value]
	    case 0x08:
	      $t_action = fgetb($fd);
	      $t_value = fgetb($fd);
	      $t_upper = $t_value >> 3;
	      $t_lower = $t_value & 0x7;
	      if($t_action == 0)
	        fputs($fo, "  mem.local.sum(" .
	                   memstr(0xa47d0 + $t_upper) . ", " .
	                   memstr(0x7eb58 + $t_lower) . ")\n");
	      else if($t_action == 255)
	        fputs($fo, "  mem.local.sub(" .
	                   memstr(0xa47d0 + $t_upper) . ", " .
	                   memstr(0x7eb58 + $t_lower) . ")\n");
	      else
	        fputs($fo, "  mem.local.sub(UNHANDLED EVAL: $t_value)");
	      break;
	    
	    // Branch if Global RAM Equal
	    // branch.ram.global(goto, address1, address2)
	    // uint_8[0x0a] uint_8[value] uint_16[goto]
	    case 0x0a:
	      $t_value = fgetb($fd);
	      $t_upper = $t_value >> 3;
	      $t_lower = $t_value & 0x7;
	      $t_goto = fgetb($fd) + (fgetb($fd) << 8);
	      $pointers[] = $t_goto;
	      fputs($fo, "  branch.mem.global(" .
	                 "lbl_" . dechex($t_goto) . ", " .
	                 memstr(0xa4788 + $t_upper) . ", " .
	                 memstr(0x7eb58 + $t_lower) . ")\n");
	      break;
	    
	    // Global RAM Add/Sub
	    // ram.global.sum(target, variable)
	    // ram.global.sub(target, variable)
	    // uint_8[0x0b] uint_8[action] uint_8[value]
	    case 0x0b:
	      $t_action = fgetb($fd);
	      $t_value = fgetb($fd);
	      $t_upper = $t_value >> 3;
	      $t_lower = $t_value & 0x7;
	      if($t_action == 0)
	        fputs($fo, "  mem.global.sum(" .
	                   memstr(0xa4788 + $t_upper) . ", " .
	                   memstr(0x7eb58 + $t_lower) . ")\n");
	      else if($t_action == 255)
	        fputs($fo, "  mem.global.sub(" .
	                   memstr(0xa4788 + $t_upper) . ", " .
	                   memstr(0x7eb58 + $t_lower) . ")\n");
	      else
	        fputs($fo, "  mem.global.sub(UNHANDLED EVAL: $t_value)\n");
	      break;
	    
	    // Change Music
	    // sound.setBGM(team, track)
	    // uint_8[0x0c] uint_8[unit] uint_8[song]
	    case 0x0c:
	      $t_team = fgetb($fd);
	      $t_track = fgetb($fd);
	      fputs($fo, "  bgm(" .
	                 "$ar_team[$t_team], " .
	                 "$ar_bgm[$t_track])\n");
	      break;
	    
	    // Deploy
	    // screen.unit.deploy(unit, x, y)
	    // uint_8[0x0e] uint_8[unit] uint_8[x] uint_8[y]
	    case 0x0d:
	      $t_unit = fgetb($fd);
	      $t_x = fgetb($fd);
	      $t_y = fgetb($fd);
	      fputs($fo, "  unit.deploy(" .
	                 "$ar_unit[$t_unit], " .
	                 "$t_x, " .
	                 "$t_y)\n");
	      break;
	    
	    // Retreat
	    // screen.unit.retreat(unit)
	    // uint_8[0x0e] uint_8[unit]
	    case 0x0e:
	      $t_unit = fgetb($fd);
	      fputs($fo, "  unit.retreat(" .
	                 "$ar_unit[$t_unit])\n");
	      break;
	    
	    // Set Unit Byte
	    // ram.unit.setbyte(unit, position, byte)
	    // uint_8[0x10] uint_8[unit] uint_8[position] uint_8[byte]
	    case 0x10:
	      $t_unit = fgetb($fd);
	      $t_position = fgetb($fd);
	      $t_byte = fgetb($fd);
	      fputs($fo, "  mem.unit.setbyte(" .
	                 "$ar_unit[$t_unit], " .
	                 memstr($t_position) . ", " .
	                 hexstr($t_byte) . ")\n");
	      break;
	    
	    // Set Unit Bit
	    // ram.unit.setbit(unit, position, bit)
	    // uint_8[0x11] uint_8[unit] uint_8[position] uint_8[bit]
	    case 0x11:
	      $t_unit = fgetb($fd);
	      $t_position = fgetb($fd);
	      $t_bit = fgetb($fd);
	      fputs($fo, "  mem.unit.setbit(" .
	                 "$ar_unit[$t_unit], " .
	                 memstr($t_position) . ", " .
	                 "$t_bit)\n");
	      break;
	    
	    // Clear Unit Bit
	    // ram.unit.clearbit(unit, position, bit)
	    // uint_8[0x12] uint_8[unit] uint_8[position] uint_8[bit]
	    case 0x12:
	      $t_unit = fgetb($fd);
	      $t_position = fgetb($fd);
	      $t_bit = fgetb($fd);
	      fputs($fo, "  mem.unit.clearbit(" .
	                 "$ar_unit[$t_unit], " .
	                 memstr($t_position) . ", " .
	                 "$t_bit)\n");
	      break;
	    
	    // Clean Dead Units
	    // screen.unit.cleanup()
	    // uint_8[0x13]
	    case 0x13:
	      fputs($fo, "  unit.cleanup()\n");
	      break;
	    
	    // Set Next Scenario
	    // ram.nextscenario()
	    // uint_8[0x14] uint_8[scenario]
	    case 0x14:
	      fputs($fo, "  mem.nextscenario(" .
	                 fgetb($fd) . ")\n");
	      break;
	    
	    // Game Over
	    // branch.gameover()
	    // uint_8[0x15]
	    case 0x15:
	      fputs($fo, "  branch.gameover()\n");
	      break;
	    
	    // Goto
	    // branch.goto(goto)
	    // uint_8[0x16] uint_16[goto]
	    case 0x16:
	      $t_goto = fgetb($fd) + (fgetb($fd) << 8);
	      $pointers[] = $t_goto;
	      fputs($fo, "  branch.goto(" .
	                 "lbl_" . dechex($t_goto) . ")\n");
	      break;
	    
	    // Subroutine
	    // branch.exec(goto)
	    // uint_8[0x17] uint_16[goto]
	    case 0x17:
	      $t_goto = fgetb($fd) + (fgetb($fd) << 8);
	      $pointers[] = $t_goto;
	      fputs($fo, "  branch.sub(" .
	                 "lbl_" . dechex($t_goto) . ")\n");
	      break;
	    
	    // End Subroutine
	    // return()
	    // uint_8[0x18]
	    case 0x18:
	      fputs($fo, "endsub\n\n");
	      break;
	    
	    // Prompt Yes/No
	    // screen.prompt.yesno(goto)
	    // uint_8[0x1d] uint_8[0x00] uint_8[0xf4] uint_16[0x0000] uint_8[0x22] uint_16[goto]
	    case 0x1d:
	      $t_null = fread($fd, 5);
	      $t_goto = fgetb($fd) + (fgetb($fd) << 8);
	      $pointers[] = $t_goto;
	      fputs($fo, "  prompt.yesno(" .
	                 "lbl_" . dechex($t_goto) . ")\n");
	      break;
	    
	    // Deploy Unit
	    // screen.unit.showSub(unit)
	    // uint_8[0x1e] uint_8[unit]
	    case 0x1e:
	      fputs($fo, "  unit.showSub(" .
	                 $ar_unit[fgetb($fd)] . ")\n");
	      break;
	    
	    // Fade Out
	    // screen.fadeOut(time)
	    // unit_8[0x38] uint_8[time]
	    case 0x32:
	      fputs($fo, "  screen.fadeout(" .
	                 fgetb($fd) . ")\n");
	      break;
	    
	    // Position Unit
	    // screen.unit.position(unit, x, y)
	    // uint_8[0x36] uint_8[unit] uint_8[x] uint_8[y]
	    case 0x36:
	      $t_unit = fgetb($fd);
	      $t_x = fgetb($fd);
	      $t_y = fgetb($fd);
	      fputs($fo, "  unit.position(" .
	                 "$ar_unit[$t_unit], " .
	                 "$t_x, " .
	                 "$t_y)\n");
	      break;
	    
	    // Hide Unit
	    // screen.unit.hide(unit, target)
	    // uint_8[0x37] uint_8[unit] uint_8[target]
	    case 0x37:
	      fputs($fo, "  unit.hide(" .
	                 $ar_unit[fgetb($fd)] . ", " .
	                 $ar_target[fgetb($fd)] . ")\n");
	      break;
	    
	    // Fade In
	    // screen.fadeIn(time)
	    // unit_8[0x38] uint_8[time]
	    case 0x38:
	      fputs($fo, "  screen.fadein(" .
	                 fgetb($fd) . ")\n");
	      break;

        // Hide All Characters
        // screen.hideAllUnits()
        // uint_8[0x39]
        case 0x39:
          fputs($fo, "  unit.hideall()\n");
          break;

	    // Move Cusor to Commander
	    // screen.cusor.set(commander)
	    // uint_8[0x3d] uint_8[commander]
	    case 0x3d:
	      fputs($fo, "  cursor.set(" .
	                 $ar_unit[fgetb($fd)] . ")\n");
	      break;
	    
	    // Face Unit
	    // screen.unit.face(unit, direction)
	    // uint_8[0x3e] uint_8[unit] uint_8[direction]
	    case 0x3e:
	      fputs($fo, "  unit.face(" .
	                 $ar_unit[fgetb($fd)] . ", " .
	                 $ar_unit[fgetb($fd)] . ")\n");
	      break;
	    
	    // Move Unit
	    // screen.unit.move(unit, x, y)
	    // uint_8[0x3f] uint_8[unit] uint_8[x] uint_8[y]
	    case 0x3f:
	      fputs($fo, "  unit.move(" .
	                 $ar_unit[fgetb($fd)] . ", " .
	                 fgetb($fd) . ", " .
	                 fgetb($fd) . ")\n");
	      break;
	    
	    // Set Mask
	    // screen.mask(r, g, b)
	    // uint_8[0x42] uint_8[b] uint_8[g] uint_8[r]
	    case 0x42:
	      $t_b = fgetb($fd);
	      $t_g = fgetb($fd);
	      $t_r = fgetb($fd);
	      fputs($fo, "  screen.mask(" .
	                 "$t_r, " .
	                 "$t_g, " .
	                 "$t_b)\n");
	      break;
	    
	    // Set Map
	    // screen.map(map)
	    // uint_8[0x47] uint_8[map]
	    case 0x47:
	      fputs($fo, "  screen.map(" .
	                 fgetb($fd) . ")\n");
	      break;
	    
	    // Receive Money
	    // ram.money.add(money)
	    // uint_8[0x4e] uint_16[money]
	    case 0x4e:
	      // Game money's lowest increment is 10
	      $t_money = (fgetb($fd) + (fgetb($fd) << 8)) * 10;
	      fputs($fo, "  mem.money.add(" .
	                 "$t_money)\n");
	      break;
	    
	    // Scenario Clear
	    // scenario.clear()
	    // uint_8[0x4f]
	    case 0x4f:
	      fputs($fo, "  branch.scenarioclear()\n");
	      break;
	    
	    // Raise Stat
	    // screen.unit.raisestat(stat, amount)
	    // uint_8[0x40] uint_8[stat] uint_8[amount]
	    case 0x40:
	      fputs($fo, "  unit.raisestat(" .
	                 fgetb($fd) . ", " .
	                 fgetb($fd) . ")\n");
	      break;
	    
	    // Section End
	    case 0xff:
	      $null = fgetc($fd);
	      fputs($fo, "break\n\n");
	      break;
	    
	    // Catch unsupported codes
	    default:
	      echo "Caught unhandled exception: \$code==" . hexstr($code) . " at 0x" . dechex(ftell($fd)-1) . "\n";
	      die();
	      break;
	  }
	}
}

?>