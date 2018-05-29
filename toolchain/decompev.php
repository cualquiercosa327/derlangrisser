#!/usr/bin/php -q
<?php

set_time_limit(6000000);

echo "Der Langrisser Event Decompiler (cli)\n";
echo "Copyright (c) 2018 Derrick Sobodash\n";

$w_lang = "en";


// string hexstr(uint_8 byte)
// Converts an integer to hexadecimal string
function hexstr($byte) {
  if(strlen(dechex($byte)) % 2 > 0)
    return("0x" . str_pad(dechex($byte), strlen(dechex($byte)) + 1, "0", STR_PAD_LEFT));
  else
    return("0x" . dechex($byte));
}

// string memstr(uint_8 byte)
// Converts an integer to a hexadecimal memory address
function memstr($byte) {
  if(strlen(dechex($byte)) % 2 > 0)
    return("$" . str_pad(dechex($byte), strlen(dechex($byte)) + 1, "0", STR_PAD_LEFT));
  else
    return("$" . dechex($byte));
}

// uint_8 fgetb(resource fd)
// Reads a byte value from a filestream handle
function fgetb($fd) {
  return(ord(fgetc($fd)));
}

// uint_16 fgetw(resource fd, boolean lsb=TRUE)
// Reads a 16-bit word value from a filestream handle. Second parameter
// changes endianness. LSB default.
function fgetw($fd, $lsb = TRUE) {
  if($lsb)
    return(fgetb($fd) + (fgetb($fd) << 8));
  else
    return((fgetb($fd) << 8) + fgetb($fd));
}

// array splitbit(uint_8 byte, uint_8 width=5)
// Splits apart bits in a byte and returns as array. Default width of bits
// for the upper nibble is 5. 
function splitbit($byte, $width = 5) {
  $t_upper = $byte >> (8 - $width);
  $t_lower = $byte & bindec(str_pad("", (8 - $width), "1"));
  return(array($t_upper, $t_lower));
}

// string sctxt(string text)
// Sanitizes a formatted game script block
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
            $text);
  
  return($text);
}


// Convert definition lists to arrays
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

if (!file_exists("resources/define/spell.txt"))
  die("Fatal error: Unit definitions not found.\n");
$ar_spell = explode("\n", file_get_contents("resources/define/spell.txt"));

if (!file_exists("resources/define/class.txt"))
  die("Fatal error: Unit definitions not found.\n");
$ar_class = explode("\n", file_get_contents("resources/define/class.txt"));

if (!file_exists("resources/define/team.txt"))
  die("Fatal error: Unit definitions not found.\n");
$ar_team = explode("\n", file_get_contents("resources/define/team.txt"));
$ar_target = array("COMMANDER", "SUBUNIT");
$ar_teleport = array("TELEPORT_OUT", "TELEPORT_TO", "TELEPORT_IN");


// Read event script directory list to an array
$events = array_values(array_diff(scandir("resources/events"), array('..', '.', ".DS_Store")));


// Decompile loop
for($i = 0; $i < count($events); $i++) {
  $pointers = array();
  $fd = fopen("resources/events/" . $events[$i], "rb");
  $fo = fopen("resources/scripts/event/" . substr($events[$i], 0, -3) . "txt", "w");
  
  // Load the matching scenario script
  $ar_talk = explode("{end}", file_get_contents("resources/scripts/$w_lang/sc" . substr($events[$i], 2, 2) . ".txt"));
  
  // Get section pointers
  $section = array(fgetw($fd));
  while(ftell($fd) < $section[0])
    $section[] = fgetw($fd);
  
  // on.move(priority, goto, unit, turn, unk1)
  // uint_8[priority] uint_8[unit] uint_8[turn] uint_8[unk1] uint_16[goto]
  // When unit moves on turn, goto label
  fputs($fo, "# Movement-Triggered Events\n");
  while(ftell($fd) < $section[1]) {
    $t_cmd = fgetb($fd);
    if($t_cmd != 0xff) {
      $t_priority = $t_cmd;
      $t_unit = $ar_unit[fgetb($fd)];
      $t_turn = fgetb($fd);
      $t_unk1 = hexstr(fgetb($fd));
      $t_goto = fgetw($fd); $pointers[] = $t_goto;
      $t_goto = "lbl_" . dechex($t_goto);
      fputs($fo, "on.move($t_priority, $t_goto, $t_unit, $t_turn, $t_unk1)\n");
    }
    else {
      fputs($fo, "break\n\n");
      fseek($fd, 1, SEEK_CUR);
    }
  }


  // on.attack(priority, goto, attacker, defender, unk1, unk2, unk3)
  // uint_8[priority] uint_8[attacker] uint_8[unk1] uint_8[reciever] uint_8[unk2] uint_8[unk3] uint_16[goto]
  // When unit attacker attacks unit defender, goto label
  fputs($fo, "# Attack-Triggered Events\n");
  while(ftell($fd) < $section[2]) {
    $t_cmd = fgetb($fd);
    if($t_cmd != 0xff) {
      $t_priority = $t_cmd;
      $t_attacker = $ar_unit[fgetb($fd)];
      $t_unk1 = hexstr(fgetb($fd));
      $t_reciever = $ar_unit[fgetb($fd)];
      $t_unk2 = hexstr(fgetb($fd));
      $t_unk3 = hexstr(fgetb($fd));
      $t_goto = fgetw($fd); $pointers[] = $t_goto;
      $t_goto = "lbl_" . dechex($t_goto);
      fputs($fo, "on.attack($t_priority, $t_goto, $t_attacker, $t_reciever, $t_unk1, $t_unk2, $t_unk3)\n");
    }
    else {
      fputs($fo, "break\n\n");
      fseek($fd, 1, SEEK_CUR);
    }
  }


  // on.defeat(priority, goto, unit, ...)
  // uint_8[priority] uint_8[quantity] uint_8[unit] * uint_16[goto]
  // If unit* is defeated, goto label
  // or
  // on.damage(priority, goto, attacker, defender, unk1, unk2)
  // uint_8[priority] uint_8[0xff] uint_8[attacker] uint_8[unk1] uint_8[defender] uint_8[unk2] uint_16[goto]
  // After unit attacker damages unit defender, goto label
  fputs($fo, "# Damage-Triggered Events\n");
  while(ftell($fd) < $section[3]) {
    $t_cmd = fgetb($fd);
    if($t_cmd != 0xff) {
      $t_cmd2 = fgetb($fd);
      // If Defeated
      if($t_cmd2 != 0xff) {
        $bytecode = array($t_cmd, $t_cmd2);
        for($k = 0; $k < $t_cmd2; $k++)
          $bytecode[] = fgetb($fd);
        // Grab the goto address
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "on.defeat($bytecode[0], $t_goto");
        // Print out all the units
        for($k = 2; $k < count($bytecode); $k++)
          if($bytecode[$k] != 0)
            fputs($fo, ", {$ar_unit[$bytecode[$k]]}");
        fputs($fo, ")\n");
      }
      // If Damaged
      else {
        $bytecode = array($t_cmd, $t_cmd2, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetw($fd));
        $pointers[] = $bytecode[6];
        $bytecode[6] = "lbl_" . dechex($bytecode[6]);
        fputs($fo, "on.damage($bytecode[0], $bytecode[6], {$ar_unit[$bytecode[2]]}, {$ar_unit[$bytecode[4]]}, " .
                   hexstr($bytecode[3]) . ", " . hexstr($bytecode[5]) . ")\n");
      }
    }
    else {
      fputs($fo, "break\n\n");
      fseek($fd, 1, SEEK_CUR);
    }
  }


  // on.range(priority, goto, unit1, unit2, radius, unk1, unk2, unk3, unk4)
  // uint_8[priority] uint_8[unit] uint_8[unit] uint_8[radius] uint_8[unk1] uint_8[unk2] uint_8[unk3] uint_8[unk4] uint_16[goto]
  // When unit1 is within radius tiles of unit2, goto label
  // or
  // on.bound(priority, goto, unit, x1, y1, x2, y2, unk1, unk2)
  // uint_8[priority] uint_8[unit] uint_8[unk1] uint_8[unk2] uint_8[x1] uint_8[y1] uint_8[x2] uint_8[y2] uint_16[goto]
  // When unit's position is greater than x1,y1 and less than x2,y2, goto label
  fputs($fo, "# Position-Triggered Events\n");
  while(ftell($fd) < $section[4]) {
    $t_cmd = fgetb($fd);
    if($t_cmd != 0xff) {
      $bytecode = array($t_cmd, fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetb($fd), fgetw($fd));
      $pointers[] = $bytecode[8];
      $bytecode[8] = "lbl_" . dechex($bytecode[8]);
      // Bounding Box
      if($bytecode[2] == 0 && $bytecode[3] == 0 )
          fputs($fo, "on.bound($bytecode[0], $bytecode[8], {$ar_unit[$bytecode[1]]}, $bytecode[4], $bytecode[5], $bytecode[6], $bytecode[7], " . 
                     hexstr($bytecode[2]) . ", " . hexstr($bytecode[3]) . ")\n");
      // Range
      else
          fputs($fo, "on.range($bytecode[0], $bytecode[8], {$ar_unit[$bytecode[1]]}, {$ar_unit[$bytecode[2]]}, $bytecode[3], " .
                     hexstr($bytecode[4]) . ", " . hexstr($bytecode[5]) . ", " . hexstr($bytecode[6]) . ", " . hexstr($bytecode[7]) . ")\n");
    }
    else {
      fputs($fo, "break\n\n");
      fseek($fd, 1, SEEK_CUR);
    }
  }

  
  // on.turn(priority, goto, team, turn, unk1)
  // uint_8[priority] uint_8[team] uint_8[turn] uint_8[unk1] uint_16[goto]
  // On team phase of turn, goto label
  fputs($fo, "# Turn-Triggered Events\n");
  while(ftell($fd) < $section[5]) {
    $t_cmd = ord(fgetc($fd));
    if($t_cmd != 0xff) {
      $t_priority = $t_cmd;
      $t_team = $ar_team[fgetb($fd)];
      $t_turn = fgetb($fd);
      $t_unk1 = hexstr(fgetb($fd));
      $t_goto = fgetw($fd); $pointers[] = $t_goto;
      $t_goto = "lbl_" . dechex($t_goto);
      fputs($fo, "on.turn($t_priority, $t_goto, $t_team, $t_turn, $t_unk1)\n");
    }
    else {
      fputs($fo, "break\n\n");
      fseek($fd, 1, SEEK_CUR);
    }
  }

  
  // Core Events
  fputs($fo, "# Core Events\n");
  $t_ptr = fgetw($fd);
  $pointers[] = $t_ptr;
  while(ftell($fd) < filesize("resources/events/" . $events[$i])) {
    // Write a label for any referenced addresses
    if(in_array(ftell($fd), $pointers))
      fputs($fo, "lbl_" . dechex(ftell($fd)) . ":\n");
    $code = fgetb($fd);  
    switch($code) {
      // focus.point(x, y, speed)
      // uint_8[0x00] uint_8[speed] uint_8[x] uint_8[y]
      // Set screen focus to x,y coordinate
      //
      // focus.unit(unit)
      // uint_8[0x00] uint_8[unit] uint_8[0x00] uint_8[0x00]
      // Set screen focus to unit
      //
      // cursor.hide()
      // uint_8[0x00] uint_8[0xfd] uint_8[0x00] uint_8[0x00]
      // Hide game cursor
      //
      // cursor.show()
      // uint_8[0x00] uint_8[0xfd] uint_8[visible] uint_8[0x00]
      // Show game cursor
      case 0x00:
        $t_code = fgetb($fd);
        if($t_code == 0xfd) {
          $t_visible = (fgetb($fd) == 0 ? "show" : "hide");
          fseek($fd, 1, SEEK_CUR);
          fputs($fo, "  cursor.$t_visible()\n");
        }
        else if($t_code == 0xfe) {
          $t_x = fgetb($fd);
          $t_y = fgetb($fd);
          if($t_x == 255) $t_x = "PRESET";
          if($t_y == 255) $t_y = "PRESET";
          fputs($fo, "  focus.point($t_x, $t_y, FAST)\n");
        }
        else if($t_code == 0xff) {
          $t_x = fgetb($fd);
          $t_y = fgetb($fd);
          if($t_x == 255) $t_x = "PRESET";
          if($t_y == 255) $t_y = "PRESET";
          fputs($fo, "  focus.point($t_x, $t_y, SLOW)\n");
        }
        else {
          fseek($fd, 2, SEEK_CUR);
          fputs($fo, "  focus.unit({$ar_unit[$t_code]})\n");
        }
        break;
      
      // msg(speaker, target, portrait, focus, line)
      // uint_8[0x02] uint_8[speaker] uint_8[target] uint_8[portrait] uint_8[focus] uint_8[line]
      // Display dialogue window with speaker facing target, show accompanying
      // portrait and text line
      case 0x02:
        $t_speaker = $ar_unit[fgetb($fd)];
        $t_target = $ar_unit[fgetb($fd)];
        $t_portrait = $ar_portrait[fgetb($fd)];
        $t_focus = (fgetb($fd) == 0 ? "NOFOLLOW" : "FOLLOW");
        $t_line = fgetb($fd);
        fputs($fo, "  msg($t_speaker, $t_target, $t_portrait, $t_focus, $t_line)\n");
        fputs($fo, "# " . sctxt($ar_talk[$t_line - 1]) . "\\0\n");
        break;
      
      // item.add(item)
      // uint_8[0x03] uint_8[item]
      // Add item to inventory
      case 0x03:
        $t_item = $ar_item[fgetb($fd)];
        fputs($fo, "  item.add($t_item)\n");
        break;

      // branch.unit.dead(goto, unit)
      // uint_8[0x04] uint_8[unit] uint_16[goto]
      // If unit is dead goto label
      case 0x04:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.unit.dead($t_goto, $t_unit)\n");
        break;
      
      // branch.unit.alive(goto, unit)
      // uint_8[0x04] uint_8[unit] uint_16[goto]
      // If unit is alive goto label
      case 0x05:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.unit.alive($t_goto, $t_unit)\n");
        break;
      
      // branch.mem.local.ne(goto, address1, address2)
      // uint_8[0x0a] uint_8[value] uint_16[goto]
      // If local memory address1 and address2 are not equal, goto label
      case 0x06:
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa47d0 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.mem.local.ne($t_goto, $t_upper, $t_lower)\n");
        break;
      
      // branch.mem.local.eq(goto, address1, address2)
      // uint_8[0x0a] uint_8[value] uint_16[goto]
      // If local memory address1 and address2 are equal, goto label
      case 0x07:
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa47d0 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.mem.local.eq($t_goto, $t_upper, $t_lower)\n");
        break;
      
      // local.sum(target, variable)
      // uint_8[0x08] uint_8[0x00] uint_8[value]
      // Set local memory location target += memory location 
      //
      // local.sub(target, variable)
      // uint_8[0x08] uint_8[0xff] uint_8[value]
      // Set local memory location target -= memory location 
      case 0x08:
        $t_action = fgetb($fd);
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa47d0 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        if($t_action == 0)
          fputs($fo, "  local.sum($t_upper, $t_lower)\n");
        else if($t_action == 255)
          fputs($fo, "  local.sub($t_upper, $t_lower)\n");
        else
          fputs($fo, "  local.math(UNHANDLED EVAL: $t_action)");
        break;
      
      // branch.mem.global.ne(goto, address1, address2)
      // uint_8[0x0a] uint_8[value] uint_16[goto]
      // If global memory address1 and address2 are not equal, goto label
      case 0x09:
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa4788 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.mem.global.ne($t_goto, $t_upper, $t_lower)\n");
        break;
      
      // branch.mem.global.eq(goto, address1, address2)
      // uint_8[0x0a] uint_8[value] uint_16[goto]
      // If global memory address1 and address2 are equal, goto label
      case 0x0a:
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa4788 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.mem.global.eq($t_goto, $t_upper, $t_lower)\n");
        break;
      
      // global.sum(target, variable)
      // uint_8[0x0b] uint_8[0x00] uint_8[value]
      // Set global memory location target += memory location
      //
      // global.sub(target, variable)
      // uint_8[0x0b] uint_8[0xff] uint_8[value]
      // Set global memory location target -= memory location
      case 0x0b:
        $t_action = fgetb($fd);
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa4788 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        if($t_action == 0)
          fputs($fo, "  global.sum($t_upper, $t_lower)\n");
        else if($t_action == 255)
          fputs($fo, "  global.sub($t_upper, $t_lower)\n");
        else
          fputs($fo, "  global.math(UNHANDLED EVAL: $t_action)\n");
        break;
      
      // bgm(team, track)
      // uint_8[0x0c] uint_8[team] uint_8[song]
      // Set the background unit for team phase to song
      case 0x0c:
        $t_team = $ar_team[fgetb($fd)];
        $t_track = $ar_bgm[fgetb($fd)];
        fputs($fo, "  bgm($t_team, $t_track)\n");
        break;
      
      // unit.deploy(unit, x, y)
      // uint_8[0x0e] uint_8[unit] uint_8[x] uint_8[y]
      // Deploy unit at x,y coordinate
      case 0x0d:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        fputs($fo, "  unit.deploy($t_unit, $t_x, $t_y)\n");
        break;
      
      // unit.retreat(unit)
      // uint_8[0x0e] uint_8[unit]
      // Have unit retreat from battle
      case 0x0e:
        $t_unit = $ar_unit[fgetb($fd)];
        fputs($fo, "  unit.retreat($t_unit)\n");
        break;
      
      // unit.setbyte(unit, attribute, byte)
      // uint_8[0x10] uint_8[unit] uint_8[attribute] uint_8[byte]
      // Set unit attribute to byte
      case 0x10:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_attribute = memstr(fgetb($fd));
        $t_byte = hexstr(fgetb($fd));
        fputs($fo, "  unit.setbyte($t_unit, $t_attribute, $t_byte)\n");
        break;
      
      // unit.setbit(unit, attribute, bit)
      // uint_8[0x11] uint_8[unit] uint_8[position] uint_8[bit]
      // Set bit in unit's attribute byte
      case 0x11:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_attribute = memstr(fgetb($fd));
        $t_bit = fgetb($fd);
        fputs($fo, "  unit.setbit($t_unit, $t_attribute, $t_bit)\n");
        break;
      
      // unit.clearbit(unit, attribute, bit)
      // uint_8[0x12] uint_8[unit] uint_8[position] uint_8[bit]
      // Clear bit in unit's attribute byte
      case 0x12:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_attribute = memstr(fgetb($fd));
        $t_bit = fgetb($fd);
        fputs($fo, "  unit.clearbit($t_unit, $t_attribute, $t_bit)\n");
        break;
      
      // unit.cleanup()
      // uint_8[0x13]
      // Force clear defeated units from the map
      case 0x13:
        fputs($fo, "  unit.cleanup()\n");
        break;
      
      // loadscenario()
      // uint_8[0x14] uint_8[scenario]
      // End current scenario and load scenario
      case 0x14:
        $t_scenario = fgetb($fd);
        fputs($fo, "  loadscenario($t_scenario)\n");
        break;
      
      // gameover()
      // uint_8[0x15]
      // End the game
      case 0x15:
        fputs($fo, "  gameover()\n");
        break;
      
      // goto(goto)
      // uint_8[0x16] uint_16[goto]
      // Goto address
      case 0x16:
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  goto($t_goto)\n");
        break;
      
      // subroutine(goto)
      // uint_8[0x17] uint_16[goto]
      // Jump into subroutine and return when endsub is reached
      case 0x17:
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  subroutine($t_goto)\n");
        break;
      
      // endsub
      // uint_8[0x18]
      // End subroutine and return to wherever it was called
      case 0x18:
        fputs($fo, "endsub\n\n");
        break;
      
      // prompt.ok(goto)
      // uint_8[0x1d] uint_8[0x00] uint_8[0xf4] uint_16[0x0000] uint_8[0x22] uint_16[goto]
      // Display a Yes/No prompt, fork to goto on No, otherwise continue
      case 0x1d:
        $t_null = fread($fd, 5);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  prompt.ok($t_goto)\n");
        break;
      
      // unit.showsub(unit)
      // uint_8[0x1e] uint_8[unit]
      // Reveal any subunits of unit
      case 0x1e:
        $t_unit = $ar_unit[fgetb($fd)];
        fputs($fo, "  unit.showsub($t_unit)\n");
        break;
      
      // screen.map.unlock(x1, y1, x2, y2)
      // uint_8[0x1f] uint_8[x1] uint_8[y1] uint_8[x2] uint_8[y2]
      // Unlock map area bounded by (x1, y1) and (x2, y2)
      case 0x1f:
        $t_x1 = fgetb($fd);
        $t_y1 = fgetb($fd);
        if($t_x1 == 255) $t_x1 = "PRESET";
        if($t_y1 == 255) $t_y1 = "PRESET";
        $t_x2 = fgetb($fd);
        $t_y2 = fgetb($fd);
        if($t_x2 == 255) $t_x2 = "PRESET";
        if($t_y2 == 255) $t_y2 = "PRESET";
        fputs($fo, "  screen.map.unlock($t_x1, $t_y1, $t_x2, $t_y2)\n");
        break;
      
      // randomize.holyrod(pos1, pos2, pos3, pos4)
      // uint_8[0x20] uint_8[pos1] uint_8[pos2] uint_8[pos3] uint_8[pos4]
      // Randomly place the Holy Rod in pos1, pos2, pos3, or pos4
      case 0x20:
        $t_pos1 = fgetb($fd);
        $t_pos2 = fgetb($fd);
        $t_pos3 = fgetb($fd);
        $t_pos4 = fgetb($fd);
        fputs($fo, "  randomize.holyrod($t_pos1, $t_pos2, $t_pos3, $t_pos4)\n");
        break;
      
      // tile.set(type, x, y)
      // uint_8[0x21] uint_8[x] uint_8[y] uint_8[type]
      // Change the tile at coordinate x,y to terrain type
      case 0x21:
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        $t_type = fgetb($fd);
        fputs($fo, "  tile.set($t_type, $t_x, $t_y)\n");
        break;
      
      // burn(x, y)
      // uint_8[0x22] uint_8[x] uint_8[y]
      // Set a fire at coordinates x,y, causing map tiles will become fire
      // terrain type 0xf0. Fire expands with each turn. During ally phase it
      // is turn*2+4-1; during enemy phase it is turn*2+4.
      case 0x22:
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        fputs($fo, "  burn($t_x, $t_y)\n");
        break;
      
      // screen.brightness(value)
      // uint_8[0x23] uint_8[value]
      // Brighten screen by value; 0x00 resets to default
      case 0x23:
        $t_value = fgetb($fd);
        fputs($fo, "  screen.brightness($t_value)\n");
        break;
      
      // unit.expboost(unit, amount)
      // uint_8[0x24] uint_8[unit] uint_16[amount]
      // Gives amount experience points to unit
      case 0x24:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_amount = fgetw($fd);
        fputs($fo, "  unit.expboost($t_unit, $t_amount)\n");
        break;
      
      // cast.fireball(unit, x, y)
      // uint_8[0x25] uint_8[x] uint_8[y]
      // Caster unit targets coordinates x,y with Fireball spell
      case 0x25:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        fputs($fo, "  cast.fireball($t_unit, $t_x, $t_y)\n");
        break;
      
      // cast.teleport(unit1, unit2, mode, x, y)
      // uint_8[0x26] uint_8[unit1] uint_8[unit2] uint_8[mode] uint_8[x] uint_8[y]
      // Caster unit1 targets unit2 to teleport to target coordinates of x,y
      // Teleport has three modes:
      // 0x00 - Teleport out of the scenario
      // 0x01 - Teleport to destination x,y
      // 0x02 - Teleport into scenario at destination x,y
      case 0x26:
        $t_unit1 = $ar_unit[fgetb($fd)];
        $t_unit2 = $ar_unit[fgetb($fd)];
        $t_mode = $ar_teleport[fgetb($fd)];
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        fputs($fo, "  cast.teleport($t_unit1, $t_unit2, $t_mode, $t_x, $t_y)\n");
        break;
      
      // branch.units.dead(goto, unit, *)
      // uint_8[0x27] uint_8[0x00] uint_8[count] uint_8[unit] * uint_16[goto]
      // If all passed units are dead, goto label
      //
      // branch.subunits.dead(goto, unit, *)
      // uint_8[0x27] uint_8[0x01] uint_8[count] uint_8[unit] * uint_16[goto]
      // If any passed units or their subunits are dead, goto label
      case 0x27:
        $t_mode = fgetb($fd);
        $t_count = fgetb($fd);
        $t_units = array();
        for($j=0; $j<$t_count; $j++)
          $t_units[] = $ar_unit[fgetb($fd)];
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        if($t_mode == 0x00)
          fputs($fo, "  branch.units.dead($t_goto");
        elseif($t_mode == 0x01)
          fputs($fo, "  branch.subunits.dead($t_goto");
        for($j=0; $j<$t_count; $j++)
          if($t_units[$j] != "NULL_FF")
            fputs($fo, ", $t_units[$j]");
        fputs($fo, ")\n");
        break;
      
      // unit.refresh()
      // uint_8[0x28]
      // Refresh all available units
      case 0x28:
        fputs($fo, "  unit.refresh()\n");
        break;
      
      // branch.unit.sex(goto, sex)
      // uint_8[0x29] uint_8[sex] uint_16[goto]
      // If unit's sex equals sex, goto label
      case 0x29:
        $t_sex = fgetb($fd);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.unit.sex($t_goto, $t_sex)\n");
        break;
      
      // branch.equipped(goto, team, item, x1, y1, x2, y2)
      // uint_8[0x2a] uint_8[item] uint_8[team] uint[x1] uint[y1] uint[x2] uint[y2] uint_16[goto]
      // If a unit on team equipped with item enters the area between
      // coordinates (x1, y1) and (x2, y2), goto label
      case 0x2a:
        $t_item = $ar_item[fgetb($fd)];
        $t_team = $ar_team[fgetb($fd)];
        $t_x1 = fgetb($fd);
        $t_y1 = fgetb($fd);
        if($t_x1 == 255) $t_x1 = "PRESET";
        if($t_y1 == 255) $t_y1 = "PRESET";
        $t_x2 = fgetb($fd);
        $t_y2 = fgetb($fd);
        if($t_x2 == 255) $t_x2 = "PRESET";
        if($t_y2 == 255) $t_y2 = "PRESET";
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.equipped($t_goto, $t_team, $t_item, $t_x1, $t_y1, $t_x2, $t_y2)\n");
        break;
      
      // branch.unit.ne(goto, unit, unk1)
      // uint_8[0x2b] uint_8[unk1] uint_8[unit] uint_16[goto]
      // If not unit, goto label
      case 0x2b:
        $t_unk1 = hexstr(fgetb($fd));
        $t_unit = $ar_unit[fgetb($fd)];
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.unit.ne($t_goto, $t_unit, $t_unk1)\n");
        break;
      
      // branch.unit.attrib.ne(goto, unit, attribute, value)
      // uint_8[0x2c] uint_8[unit] uint_8[attribute] uint_8[value] uint_16[goto]
      // If unit attribute does not equal value, goto label
      case 0x2c:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_attribute = fgetb($fd);
        $t_value = fgetb($fd);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.unit.attrib.eq($t_goto, $t_unit, $t_attribute, $t_value)\n");
        break;
      
      // holyrodlost()
      // uint_8[0x2d]
      // Trigger Holy Rod Lost event
      case 0x2d:
        fputs($fo, "  holyrodlost()\n");
        break;
      
      // unit.swap(unit1, unit2)
      // uint_8[0x2e] uint_8[unit1] uint_8[unit2]
      // Swaps unit1 with unit2; mainly used to replace an NPC or Enemy that
      // is recruited during scenario
      case 0x2e:
        $t_unit1 = $ar_unit[fgetb($fd)];
        $t_unit2 = $ar_unit[fgetb($fd)];
        fputs($fo, "  unit.swap($t_unit1, $t_unit2)\n");
        break;
      
      // breakbridge()
      // uint_8[0x2f]
      // Collapse the bridge in Scenario 8
      case 0x2f:
        fputs($fo, "  breakbridge()\n");
        break;
      
      // unseal.langrisser()
      // uint_8[0x31]
      // Turns Langrisser item into True Langrisser
      case 0x31:
        fputs($fo, "  unseal.langrisser()\n");
        break;
      
      // screen.fadeout(time)
      // unit_8[0x38] uint_8[time]
      // Fade screen to black over time seconds
      case 0x32:
        $t_time = fgetb($fd);
        fputs($fo, "  screen.fadeout($t_time)\n");
        break;
      
      // unit.flag(unit)
      // uint_8[0x34] uint_8[unit]
      // Marks unit for automated repositioning
      case 0x34:
        $t_unit = $ar_unit[fgetb($fd)];
        fputs($fo, "  unit.flag($t_unit)\n");
        break;
      
      // positionflagged()
      // uint_8[0x35]
      // Repositions all flagged units at preconfigured coordinates
      case 0x35:
        fputs($fo, "  positionflagged()\n");
        break;
      
      // unit.position(unit, x, y)
      // uint_8[0x36] uint_8[unit] uint_8[x] uint_8[y]
      // Position unit at x,y coordinate
      case 0x36:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        fputs($fo, "  unit.position($t_unit, $t_x, $t_y)\n");
        break;
      
      // unit.hide(unit, target)
      // uint_8[0x37] uint_8[unit] uint_8[target]
      // Hide unit
      case 0x37:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_target = $ar_target[fgetb($fd)];
        fputs($fo, "  unit.hide($t_unit, $t_target)\n");
        break;
      
      // screen.fadein(time)
      // unit_8[0x38] uint_8[time]
      // Fade screen from black over time seconds
      case 0x38:
        $t_time = fgetb($fd);
        fputs($fo, "  screen.fadein($t_time)\n");
        break;

      // unit.hideall()
      // uint_8[0x39]
      // Hide all units
      case 0x39:
        fputs($fo, "  unit.hideall()\n");
        break;

      // unit.align(team)
      // uint_8[0x3a] uint_8[unit] uint_8[team]
      // Change unit's alignment to team
      case 0x3a:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_team = $ar_team[fgetb($fd)];
        fputs($fo, "  unit.align($t_unit, $t_team)\n");
        break;
      
      // sfx(sound)
      // uint_8[0x3b] uint_8[sound]
      // Play sound effect sound
      case 0x3b:
        $t_sound = fgetb($fd);
        fputs($fo, "  sfx($t_sound)\n");
        break;
      
      // cusor.set(unit)
      // uint_8[0x3d] uint_8[unit]
      // Move screen cursor to unit
      case 0x3d:
        $t_unit = $ar_unit[fgetb($fd)];
        fputs($fo, "  cursor.set($t_unit)\n");
        break;
      
      // unit.face(unit, direction)
      // uint_8[0x3e] uint_8[unit] uint_8[direction]
      // Have unit face direction, which may be a cardinal direction or the
      // position of another unit
      case 0x3e:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_direction = $ar_unit[fgetb($fd)];
        fputs($fo, "  unit.face($t_unit, $t_direction)\n");
        break;
      
      // unit.move(unit, x, y)
      // uint_8[0x3f] uint_8[unit] uint_8[x] uint_8[y]
      // Move unit to x,y coordinate
      case 0x3f:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        fputs($fo, "  unit.move($t_unit, $t_x, $t_y)\n");
        break;
      
      // unit.statboost(stat, amount)
      // uint_8[0x40] uint_8[stat] uint_8[amount]
      // Raise the current unit's stat by amount
      case 0x40:
        $t_stat = fgetb($fd);
        $t_amount = fgetb($fd);
        fputs($fo, "  unit.statboost($t_stat, $t_amount)\n");
        break;
      
      // screen.mask(r, g, b)
      // uint_8[0x42] uint_8[b] uint_8[g] uint_8[r]
      // Tint the screen with an RGB mask
      case 0x42:
        $t_b = fgetb($fd);
        $t_g = fgetb($fd);
        $t_r = fgetb($fd);
        fputs($fo, "  screen.mask($t_r, $t_g, $t_b)\n");
        break;
      
      // prompt.options(goto1, goto2, line1, line2, line3, unk1, unk2, unk3, unk4)
      // uint_8[0x43] uint_8[unk1] uint_8[unk2] uint_8[unk3] uint_8[unk4] uint_8[line1] uint_8[line2] uint_8[line3] uint_8[goto1] uint_8[goto2]
      // Display three-option prompt using the text from line1, line2 and line3,
      // continue on option1, goto address1 on option2, goto address2 on option3
      case 0x43:
        $t_unk1 = hexstr(fgetb($fd));
        $t_unk2 = hexstr(fgetb($fd));
        $t_unk3 = hexstr(fgetb($fd));
        $t_unk4 = hexstr(fgetb($fd));
        $t_line1 = fgetb($fd);
        $t_line2 = fgetb($fd);
        $t_line3 = fgetb($fd);
        $t_goto1 = fgetw($fd); $pointers[] = $t_goto1;
        $t_goto2 = fgetw($fd); $pointers[] = $t_goto2;
        $t_goto1 = "lbl_" . dechex($t_goto1);
        $t_goto2 = "lbl_" . dechex($t_goto2);
        fputs($fo, "  prompt.options($t_goto1, $t_goto2, $t_line1, $t_line2, $t_line3, $t_unk1, $t_unk2, $t_unk3, $t_unk4)\n");
        fputs($fo, "# " . sctxt($ar_talk[$t_line1 - 1]) . "\\0\n");
        fputs($fo, "# " . sctxt($ar_talk[$t_line2 - 1]) . "\\0\n");
        fputs($fo, "# " . sctxt($ar_talk[$t_line3 - 1]) . "\\0\n");
        break;
      
      // unit.reset(unit)
      // uint_8[0x45] uint_8[unit]
      // Resets the HP and status of unit to default
      case 0x45:
        $t_unit = $ar_unit[fgetb($fd)];
        fputs($fo, "  unit.reset($t_unit)\n");
        break;
      
      // delay(time)
      // uint_8[0x46] uint_8[time]
      // Wait time seconds
      case 0x46:
        $t_time = fgetb($fd);
        fputs($fo, "  delay($t_time)\n");
        break;
      
      // screen.map.set(map)
      // uint_8[0x47] uint_8[map]
      // Replace current map set with map
      case 0x47:
        $t_map = fgetb($fd);
        fputs($fo, "  screen.map.set($t_map)\n");
        break;
      
      // loadlushiris()
      // uint_8[0x48] uint_8[0x00]
      // Loads Lushiris's backdrop for the character creation quiz
      case 0x48:
        fgetc($fd);
        fputs($fo, "  loadlushiris()\n");
        break;
      
      // setclass(class)
      // uint_8[0x49] uint_8[0x00] uint_8[class]
      // Sets Erwin's starting class in character creation quiz
      case 0x49:
        fgetc($fd);
        $t_class = $ar_class[fgetb($fd)];
        fputs($fo, "  setclass($t_class)\n");
        break;
      
      // unit.attributeboost(attrib, value)
      // uint_8[0x4a] uint_8[attrib] uint_8[value]
      // Raises unit's attribute by value
      case 0x4a:
        $t_attrib = fgetb($fd);
        $t_value = fgetb($fd);
        fputs($fo, "  unit.attributeboost($t_attrib, $t_value)\n");
        break;
      
      // enablehire(unit)
      // uint_8[0x4b] uint_8[unit]
      // Enable the hiring of unit during the character creation quiz
      case 0x4b:
        $t_unit = $ar_class[fgetb($fd)];
        fputs($fo, "  enablehire($t_unit)\n");
        break;
      
      // enablemagic(spell)
      // uint_8[0x4c] uint_8[spell]
      // Enable the use of magic during the character creation quiz
      case 0x4c:
        $t_spell = $ar_spell[fgetb($fd)];
        fputs($fo, "  enablespell($t_spell)\n");
        break;
      
      // entername()
      // uint_8[0x4d]
      // Loads the name entry box during the character creation quiz
      case 0x4d:
        fputs($fo, "  entername()\n");
        break;
      
      // money.add(money)
      // uint_8[0x4e] uint_16[money]
      // Add money to the player's stash: actual game value is divided by 10
      case 0x4e:
        // Game money's lowest increment is 10
        $t_money = (fgetw($fd)) * 10;
        fputs($fo, "  money.add($t_money)\n");
        break;
      
      // scenarioclear()
      // uint_8[0x4f]
      // Display Scenario Clear animation
      case 0x4f:
        fputs($fo, "  scenarioclear()\n");
        break;
      
      // attack(unit1, unit2)
      // uint_8[0x51] uint_8[unit1] uint_8[unit2]
      // Have unit1 attack unit2
      case 0x51:
        $t_unit1 = $ar_unit[fgetb($fd)];
        $t_unit2 = $ar_unit[fgetb($fd)];
        fputs($fo, "  attack($t_unit1, $t_unit2)\n");
        break;
      
      // cast.thunder(unit1, unit2)
      // uint_8[0x54] uint_8[unit1] uint_8[unit2]
      // Caster unit1 targets unit2 with Thunder spell
      case 0x54:
        $t_unit1 = $ar_unit[fgetb($fd)];
        $t_unit2 = $ar_unit[fgetb($fd)];
        fputs($fo, "  cast.thunder($t_unit1, $t_unit2)\n");
        break;
      
      // break
      // uint_16[0xffff]
      // Break execution of the event script until next trigger
      case 0xff:
        $next = fgetb($fd);
        if($next == 0xff)
          fputs($fo, "break\n\n");
        else
          fputs($fo, "  rawwrite(" . hexstr(0xff00 + $next) . ")\n");
        break;
      
      // Catch and print unsupported codes
      default:
        echo "Caught unhandled exception: \$code==" . hexstr($code) . " at 0x" . dechex(ftell($fd)-1) . "\n";
        die();
        break;
    }
  }
  unset($pointers);
}

?>