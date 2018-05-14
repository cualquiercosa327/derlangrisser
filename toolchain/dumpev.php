#!/usr/bin/php -q
<?php

echo "Der Langrisser Event Dumper (cli)\n";
echo "Copyright (c) 2018 Derrick Sobodash\n";

function hexstr($byte) {
  return("0x" . str_pad(dechex($byte), 2, "0", STR_PAD_LEFT));
}

function fgetb($fd) {
  return(ord(fgetc($fd)));
}

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

if (!file_exists("resources/events/define_bgm.txt"))
	die("Fatal error: BGM definitions not found.\n");
$ar_bgm = explode("\n", file_get_contents("resources/events/define_bgm.txt"));

if (!file_exists("resources/events/define_item.txt"))
	die("Fatal error: Item definitions not found.\n");
$ar_item = explode("\n", file_get_contents("resources/events/define_item.txt"));

if (!file_exists("resources/events/define_portrait.txt"))
	die("Fatal error: Portrait definitions not found.\n");
$ar_portrait = explode("\n", file_get_contents("resources/events/define_portrait.txt"));

if (!file_exists("resources/events/define_unit.txt"))
	die("Fatal error: Unit definitions not found.\n");
$ar_unit = explode("\n", file_get_contents("resources/events/define_unit.txt"));

$pointers = array();

// Make sure all events are present
foreach($events as $file)
	if(!file_exists($file))
		die("Fatal error: Event $file not found.\n");

for($i = 0; $i < count($events); $i++) {
	$fd = fopen($events[$i], "rb");
	$fo = fopen(substr($events[$i], 0, -3) . "txt", "w");
	
	fputs($fo, ";EVENT SCRIPT " . ($i+1) . "\n");
	
	// Get section pointers
	$begin = ord(fgetc($fd)) + (ord(fgetc($fd)) << 8);
	$section = array($begin);
	while(ftell($fd) < $section[0])
	  $section[] = ord(fgetc($fd)) + (ord(fgetc($fd)) << 8);
	
	// Read movement hooks
	fputs($fo, "\n;Hooks for movement-triggered events\n");
	fputs($fo, "movement:\n");
	while(ftell($fd) < $section[1]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	    $t_priority = str_pad($bytecode[0], 6, " ");
	    $t_unit = $ar_unit[$bytecode[1]];
	    $t_turn = $bytecode[2];
	    $t_unk = $bytecode[3];
	    $t_ptr = $bytecode[4] + ($bytecode[5] << 8);
	    $pointers[] = $t_ptr;
	    fputs($fo, "  $t_priority IF unit=$t_unit AND turn=$t_turn THEN GOSUB lbl_" . dechex($t_ptr) . "\n");
	    for($i=0; $i<count($bytecode); $i++)
	      $bytecode[$i] = hexstr($bytecode[$i]);
	    fputs($fo, "         ;$bytecode[0] $bytecode[1] $bytecode[2] $bytecode[3] $bytecode[4] $bytecode[5]\n");
	  }
	  else {
	    $t_cmd = fgetc($fd);
	    fputs($fo, "movement_end:\n");
	  }
	}

	// Read attack hooks
	fputs($fo, "\n;Hooks for attack-triggered events\n");
	fputs($fo, "attack:\n");
	while(ftell($fd) < $section[2]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	    $t_priority = str_pad($bytecode[0], 6, " ");
	    $t_unit1 = $ar_unit[$bytecode[1]];
	    $t_unit2 = $ar_unit[$bytecode[3]];
	    $t_ptr = $bytecode[6] + ($bytecode[7] << 8);
	    $pointers[] = $t_ptr;
	    fputs($fo, "  $t_priority IF unit1=$t_unit1 AND unit2=$t_unit2 THEN GOSUB lbl_" . dechex($t_ptr) . "\n");
	    for($i=0; $i<count($bytecode); $i++)
	      $bytecode[$i] = hexstr($bytecode[$i]);
	    fputs($fo, "         ;$bytecode[0] $bytecode[1] $bytecode[2] $bytecode[3] $bytecode[4] $bytecode[5] $bytecode[6] $bytecode[7]\n");
	  }
	  else {
	    $t_cmd = fgetc($fd);
	    fputs($fo, "attack_end:\n");
	  }
	}
	
	// Read defeat/damage hooks
	fputs($fo, "\n;Hooks for damage-triggered events\n");
	fputs($fo, "damage:\n");
	while(ftell($fd) < $section[3]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $t_cmd2 = ord(fgetc($fd));
	    if($t_cmd2 != 255) {
	      $bytecode = array($t_cmd, $t_cmd2, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	      $t_priority = str_pad($bytecode[0], 6, " ");
	      $t_unit = $ar_unit[$bytecode[2]];
	      $t_ptr = $bytecode[4] + ($bytecode[5] << 8);
	      $pointers[] = $t_ptr;
	      fputs($fo, "  $t_priority IF defeated=$t_unit THEN GOSUB lbl_" . dechex($t_ptr) . "\n");
	      for($i=0; $i<count($bytecode); $i++)
	        $bytecode[$i] = hexstr($bytecode[$i]);
	      fputs($fo, "         ;$bytecode[0] $bytecode[1] $bytecode[2] $bytecode[3] $bytecode[4] $bytecode[5]\n");
	    }
	    else {
	      $bytecode = array($t_cmd, $t_cmd2, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	      $t_priority = str_pad($bytecode[0], 6, " ");
	      $t_unit1 = $ar_unit[$bytecode[2]];
	      $t_unit2 = $ar_unit[$bytecode[4]];
	      fputs($fo, "  $t_priority IF attacker=$t_unit1 AND defeated=$t_unit2 THEN GOSUB lbl_" . dechex($t_ptr) . "\n");
	      for($i=0; $i<count($bytecode); $i++)
	        $bytecode[$i] = hexstr($bytecode[$i]);
	      fputs($fo, "         ;$bytecode[0] $bytecode[1] $bytecode[2] $bytecode[3] $bytecode[4] $bytecode[5] $bytecode[6] $bytecode[7]\n");
	    }
	  }
	  else {
	    $t_cmd = fgetc($fd);
	    fputs($fo, "damage_end:\n");
	  }
	}
	
	// Read position hooks
	fputs($fo, "\n;Hooks for position-triggered events\n");
	fputs($fo, "position:\n");
	while(ftell($fd) < $section[4]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	    if($bytecode[2] == 0 && $bytecode[3] == 0 ) {
	      $t_priority = str_pad($bytecode[0], 6, " ");
	      $t_unit = $ar_unit[$bytecode[1]];
	      $t_x1 = $bytecode[4];
	      $t_y1 = $bytecode[5];
	      $t_x2 = $bytecode[6];
	      $t_y2 = $bytecode[7];
	      $t_ptr = $bytecode[8] + ($bytecode[9] << 8);
	      $pointers[] = $t_ptr;
	      fputs($fo, "  $t_priority IF $t_unit>[$t_x1,$t_y1] AND $t_unit<[$t_x2,$t_y2] THEN GOSUB lbl_" . dechex($t_ptr) . "\n");
	      for($i=0; $i<count($bytecode); $i++)
	        $bytecode[$i] = hexstr($bytecode[$i]);
	      fputs($fo, "         ;$bytecode[0] $bytecode[1] $bytecode[2] $bytecode[3] $bytecode[4] $bytecode[5] $bytecode[6] $bytecode[7] $bytecode[8] $bytecode[9]\n");
	    }
	    else {
	      $t_priority = str_pad($bytecode[0], 6, " ");
	      $t_unit1 = $ar_unit[$bytecode[1]];
	      $t_unit2 = $ar_unit[$bytecode[2]];
	      $t_radius = $bytecode[3];
	      $t_ptr = $bytecode[8] + ($bytecode[9] << 8);
	      $pointers[] = $t_ptr;
	      fputs($fo, "  $t_priority IF $t_unit1>=$t_unit2*$t_radius THEN GOSUB lbl_" . dechex($t_ptr) . "\n");
	      for($i=0; $i<count($bytecode); $i++)
	        $bytecode[$i] = hexstr($bytecode[$i]);
	      fputs($fo, "         ;$bytecode[0] $bytecode[1] $bytecode[2] $bytecode[3] $bytecode[4] $bytecode[5] $bytecode[6] $bytecode[7] $bytecode[8] $bytecode[9]\n");
	    }
	  }
	  else {
	    $t_cmd = fgetc($fd);
	    fputs($fo, "position_end:\n");
	  }
	}
	
	// Read turn hooks
	fputs($fo, "\n;Hooks for turn-triggered events\n");
	fputs($fo, "turn:\n");
	while(ftell($fd) < $section[5]) {
	  $t_cmd = ord(fgetc($fd));
	  if($t_cmd != 255) {
	    $bytecode = array($t_cmd, ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)), ord(fgetc($fd)));
	    $t_priority = str_pad($bytecode[0], 6, " ");
	    $t_phase = $bytecode[1];
	    $t_turn = $bytecode[2];
	    $t_ptr = $bytecode[4] + ($bytecode[5] << 8);
	    $pointers[] = $t_ptr;
	    fputs($fo, "  $t_priority IF turn=$t_turn AND phase=$t_phase THEN GOSUB lbl_" . dechex($t_ptr) . "\n");
	    for($i=0; $i<count($bytecode); $i++)
	      $bytecode[$i] = hexstr($bytecode[$i]);
	    fputs($fo, "         ;$bytecode[0] $bytecode[1] $bytecode[2] $bytecode[3] $bytecode[4] $bytecode[5]\n");

	  }
	  else {
	    $t_cmd = fgetc($fd);
	    fputs($fo, "turn_end:\n");
	  }
	}
	
	// Read core events
	fputs($fo, "\n;Core event script\n");
	fputs($fo, "events:\n");
	$t_ptr = ord(fgetc($fd)) + (ord(fgetc($fd)) << 8);
	$pointers[] = $t_ptr;
	fputs($fo, "         GOSUB lbl_" . dechex($t_ptr) . "\n");
	while(!feof($fd)) {
	  if(in_array(ftell($fd), $pointers)) {
	    fputs($fo, "lbl_" . dechex(ftell($fd)) . ":\n");
	  }
	  $code = ord(fgetc($fd));
	  switch($code) {
	    // Change music
	    case $code == 0x0c:
	      $t_team = ord(fgetc($fd));
	      $t_track = ord(fgetc($fd));
	      fputs($fo, "         SETBGM $t_team=$ar_bgm[$t_track]\n");
	    // Assign variable
	    case $code == 0x0b:
	      $t_action = ord(fgetc($fd));
	      $t_value = ord(fgetc($fd));
	      $t_upper = $t_value >> 3;
	      $t_lower = $t_value & 0x7;
	      if($t_value == 0)
	        fputs($fo, "         EVAL $" . dechex(0xa4788 + $t_upper) . "+=$" . dechex(0x7eb58 + $t_lower) . "\n");
	      else if($t_value == 255)
	        fputs($fo, "         EVAL $" . dechex(0xa4788 + $t_upper) . "-=$" . dechex(0x7eb58 + $t_lower) . "\n");
	      else
	        fputs($fo, "         UNHANDLED EVAL: $t_value");
	    // Set cursor focus
	    case $code == 0x0:
	    
	  }
	  die();
	}
	fputs($fo, "events_end:\n");
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
sound.setAlly()
sound.setEnemy()
sound.play()

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