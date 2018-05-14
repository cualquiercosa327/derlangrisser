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

// Build array of event scripts
$events = array_values(array_diff(scandir("resources/events"), array('..', '.', ".DS_Store")));

$pointers = array();

// Begin dumping
for($i = 0; $i < count($events); $i++) {
	$fd = fopen("resources/events/" . $events[$i], "rb");
	$fo = fopen("resources/scripts/event/" . substr($events[$i], 0, -3) . "txt", "w");
	
	// Get section pointers
	$begin = ord(fgetc($fd)) + (ord(fgetc($fd)) << 8);
	$section = array($begin);
	while(ftell($fd) < $section[0])
	  $section[] = ord(fgetc($fd)) + (ord(fgetc($fd)) << 8);
	
	// Read movement hooks
	fputs($fo, "// Movement-Triggered Events:\n" .
	           "// event.move.addHook(priority, goto, unit, turn, unk1)\n");
	while(ftell($fd) < $section[1]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	    $pointers[] = $bytecode[4] + ($bytecode[5] << 8);
	    fputs($fo, "event.move.addHook(" .
	               "$bytecode[0], " .
	               "lbl_" . dechex($bytecode[4] + ($bytecode[5] << 8)) . ", " .
	               "{$ar_unit[$bytecode[1]]}, " .
	               "$bytecode[2], " .
	               hexstr($bytecode[3]) . ");\n");
	  }
	  else {
	    $t_cmd = fgetc($fd);
	  }
	}
	fputs($fo, "\n");

	// Read attack hooks
	fputs($fo, "// Attack-Triggered Events:\n" .
	           "// event.attack.addHook(priority, goto, attacker, defender, unk1, unk2, unk3)\n");
	while(ftell($fd) < $section[2]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
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
	  else {
	    $t_cmd = fgetc($fd);
	  }
	}
	fputs($fo, "\n");
	
	// Read defeat/damage hooks
	fputs($fo, "// Damage-Triggered Events:\n" .
	           "// event.defeat.addHook(priority, goto, unit, ...)\n" .
               "// event.damage.addHook(priority, goto, attacker, defender, unk1, unk2, unk3)\n");
	while(ftell($fd) < $section[3]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $t_cmd2 = ord(fgetc($fd));
	    // defeated
	    if($t_cmd2 != 255) {
	      $bytecode = array($t_cmd, $t_cmd2);
	      for($k = 0; $k < $t_cmd2; $k++) {
	        $bytecode[] = ord(fgetc($fd));
	      }
	      // Grab the goto address
	      $t_ptr = ord(fgetc($fd)) + (ord(fgetc($fd)) << 8);
	      $pointers[] = $t_ptr;
          fputs($fo, "event.defeat.addHook(" .
	                 "$bytecode[0], " .
	                 "lbl_" . dechex($t_ptr));
	      // Print out all the units
	      for($k = 2; $k < count($bytecode); $k++) {
	        if($bytecode[$k] != 0)
	          fputs($fo, ", {$ar_unit[$bytecode[$k]]}");
	      }
	      fputs($fo, ");\n");
	    }
	    // damaged
	    else {
	      $bytecode = array($t_cmd, $t_cmd2, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	      $pointers[] = $bytecode[6] + ($bytecode[7] << 8);
          fputs($fo, "event.damage.addHook(" .
                     "$bytecode[0], " .
	                 "lbl_" . dechex($bytecode[6] + ($bytecode[7] << 8)) . ", " .
	                 "{$ar_unit[$bytecode[2]]}, " .
	                 "{$ar_unit[$bytecode[4]]}, " .
	                 hexstr($bytecode[1]) . ", " .
	                 hexstr($bytecode[3]) . ", " .
	                 hexstr($bytecode[5]) . ");\n");
	    }
	  }
	  else {
	    $t_cmd = fgetc($fd);
	  }
	}
	fputs($fo, "\n");
	
	// Read position hooks
	fputs($fo, "// Position-Triggered Events:\n" .
	           "// event.box.addHook(priority, goto, unit, unit, radius, unk1, unk2, unk3, unk4)\n" .
               "// event.radius.addHook(priority, goto, unit, x, y, x, y, unk1, unk2)\n");
	while(ftell($fd) < $section[4]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	    $pointers[] = $bytecode[8] + ($bytecode[7] << 9);
        // box
	    if($bytecode[2] == 0 && $bytecode[3] == 0 ) {
          fputs($fo, "event.box.addHook(" .
                     "$bytecode[0], " .
	                 "lbl_" . dechex($bytecode[8] + ($bytecode[7] << 9)) . ", " .
                     "{$ar_unit[$bytecode[1]]}, " .
                     "$bytecode[4], " . 
                     "$bytecode[5], " . 
                     "$bytecode[6], " . 
                     "$bytecode[7], " . 
	                 hexstr($bytecode[2]) . ", " .
	                 hexstr($bytecode[3]) . ");\n");
	    }
	    // radius
	    else {
          fputs($fo, "event.radius.addHook(" .
                     "$bytecode[0], " .
	                 "lbl_" . dechex($bytecode[8] + ($bytecode[7] << 9)) . ", " .
	                 "{$ar_unit[$bytecode[1]]}, " .
	                 "{$ar_unit[$bytecode[2]]}, " .
	                 "$bytecode[3], " .
	                 hexstr($bytecode[4]) . ", " .
	                 hexstr($bytecode[5]) . ", " .
	                 hexstr($bytecode[6]) . ", " .
	                 hexstr($bytecode[7]) . ");\n");
	    }
	  }
	  else {
	    $t_cmd = fgetc($fd);
	  }
	}
	fputs($fo, "\n");
	
	// Read turn hooks
	fputs($fo, "// Turn-Triggered Events\n" .
	           "// event.turn.addHook(priority, goto, part, turn, 00)\n");
	while(ftell($fd) < $section[5]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	    $pointers[] = $bytecode[4] + ($bytecode[5] << 8);
	    fputs($fo, "event.turn.addHook(" .
                   "$bytecode[0], " .
	               "lbl_" . dechex($bytecode[4] + ($bytecode[5] << 8)) . ", " .
	               "$bytecode[1], " .
	               "$bytecode[2], " .
	               "$bytecode[3]);\n");
	  }
	  else {
	    $t_cmd = fgetc($fd);
	  }
	}
	fputs($fo, "\n");
	
	// Read core events
	fputs($fo, "// Core Events\n");
	$t_ptr = ord(fgetc($fd)) + (ord(fgetc($fd)) << 8);
	$pointers[] = $t_ptr;
	while(!feof($fd)) {
	  // Write a label for any referenced addresses
	  if(in_array(ftell($fd), $pointers)) {
	    fputs($fo, "\nlbl_" . dechex(ftell($fd)) . ":\n");
	  }
	  $code = ord(fgetc($fd));
	  switch($code) {
	    // Change music
	    case $code == 0x0c:
	      $t_team = ord(fgetc($fd));
	      $t_track = ord(fgetc($fd));
	      fputs($fo, "sound.setBGM($t_team, $ar_bgm[$t_track]);\n");
	    // Assign variable
	    case $code == 0x0b:
	      $t_action = ord(fgetc($fd));
	      $t_value = ord(fgetc($fd));
	      $t_upper = $t_value >> 3;
	      $t_lower = $t_value & 0x7;
	      if($t_value == 0)
	        fputs($fo, "ram.sum($" . dechex(0xa4788 + $t_upper) . ", $" . dechex(0x7eb58 + $t_lower) . ");\n");
	      else if($t_value == 255)
	        fputs($fo, "ram.sub($" . dechex(0xa4788 + $t_upper) . ", $" . dechex(0x7eb58 + $t_lower) . ");\n");
	      else
	        fputs($fo, "         UNHANDLED EVAL: $t_value");
	    // Set cursor focus
	    case $code == 0x0:
	    
	  }
	  die();
	}
	die();
}


/*
Section 1: Movement Hooks
All 6 bytes
[priority] [unit 1 byte] [turn] [ff] [jump 2 bytes]

event.move.addHook(priority, jump, unit, turn, 0xff);

Section 2: Attack Hooks
All 8 bytes
[priority] [attacker unit 2 bytes] [reciever unit 2 bytes] [ff] [jump 2 bytes]
ffff = anyone

event.attack.addHook(priority, jump, attacker, defender, 0x00, 0x00, 0xff);

Section 3: Defeat/Injury Hooks
Byte 2 is type of event
02 = Defeated
[priority] [no units] [unit] ... [jump 2 bytes]

event.defeat.addHook(priority, jump, units, ...);

ff = Attacked
[priority] [ff] [unit] [status] [unit] [status] [jump 2 bytes]

event.defeat.addHook(priority, jump, unit, unit, ff, 00, 00);


Section 4: Position Hooks
All 10 bytes
[priority] [unit] [unit] [radius] [00000000] [jump 2 bytes]

event.position.addHook(priority, jump, type, unit, unit, radius, 00000000);

[priority] [unit] [0000] [x,y] [x,y] [jump 2 bytes]

event.position.addHook(priority, jump, type, unit, x, y, x, y, 0000);

Section 5: Turn Hooks
All 6 bytes
[priority] [01] [turn] [00] [jump 2 bytes]

event.turn.addHook(priority, jump, part, turn, 00);


Section 6: Event Script
Depends on action code

Begins with jump to pre-battle deployment

00 fe xx yy - Instantly change to coordinates xx,yy
00 ff xx yy - Slowly change to coordinates xx,yy
screen.focus.set(x, y, SPEED)


0c 01 xx - Change ally music to xx
sound.setBGM()
sound.playSFX()

3d xx - Move cusor to commander xx
screen.cursor.set(x)

37 xx yy - Set unit xx status to yy (00 = hidden)
screen.unit.hide(unit)

36 uu xx yy - Position unit uu at xx,yy (ffff = selected coordinates)
screen.unit.move(unit, x, y)

38 xx - Fade in screen over xx seconds
screen.fadeIn(time)

02 xx yy zz pp ll - xx speaks to yy using picture pp and line ll, focus pp (01 = follow/00 = nofollow)
screen.talk(xx, yy, zz, ll, focus)

3e xx yy - xx faces yy
screen.unit.face(unit1, unit2)

1e uu - Reveal unit uu
08 xx yy - If xx=0 
screen.unit.show(unit)

In all hooks, first byte appears to be a priority byte
*/


?>