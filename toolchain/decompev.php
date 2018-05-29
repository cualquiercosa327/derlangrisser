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

if (!file_exists("resources/define/unit.txt"))
  die("Fatal error: Unit definitions not found.\n");
$ar_unit = explode("\n", file_get_contents("resources/define/unit.txt"));

if (!file_exists("resources/define/team.txt"))
  die("Fatal error: Unit definitions not found.\n");
$ar_team = explode("\n", file_get_contents("resources/define/team.txt"));
$ar_target = array("COMMANDER", "SUBUNIT");


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
  // Movement Triggered Events
  // uint_8[priority] uint_8[unit] uint_8[turn] uint_8[unk1] uint_16[jump]
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
  // Attack-Triggered Events
  // uint_8[priority] uint_8[attacker] uint_8[unk1] uint_8[reciever] uint_8[unk2] uint_8[unk3] uint_16[goto]
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
  // Defeat-Triggered Events
  // uint_8[priority] uint_8[quantity] uint_8[unit] * uint_16[jump]
  // or
  // on.damage(priority, goto, attacker, defender, unk1, unk2)
  // Damage-Triggered Events
  // uint_8[priority] uint_8[0xff] uint_8[attacker] uint_8[unk1] uint_8[defender] uint_8[unk2] uint_16[jump]
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


  // on.bound(priority, goto, unit, unit, radius, unk1, unk2, unk3, unk4)
  // Bound-Triggered Events
  // uint_8[priority] uint_8[unit] uint_8[unit] uint_8[radius] uint_8[unk1] uint_8[unk2] uint_8[unk3] uint_8[unk4] uint_16[jump]
  // or
  // on.range(priority, goto, unit, x1, y1, x2, y2, unk1, unk2)
  // Range-Triggered Events
  // uint_8[priority] uint_8[unit] uint_8[unk1] uint_8[unk2] uint_8[x1] uint_8[y1] uint_8[x2] uint_8[y2] uint_16[jump]
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
  // Turn-Triggered Events
  // uint_8[priority] uint_8[team] uint_8[turn] uint_8[unk1] uint_16[jump]
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
      
      // Dialogue
      // msg(speaker, target, portrait, focus, line)
      // uint_8[0x02] uint_8[speaker] uint_8[target] uint_8[portrait] uint_8[focus] uint_8[line]
      case 0x02:
        $t_speaker = $ar_unit[fgetb($fd)];
        $t_target = $ar_unit[fgetb($fd)];
        $t_portrait = $ar_portrait[fgetb($fd)];
        $t_focus = (fgetb($fd) == 0 ? "NOFOLLOW" : "FOLLOW");
        $t_line = fgetb($fd);
        fputs($fo, "  msg($t_speaker, $t_target, $t_portrait, $t_focus, $t_line)\n");
        fputs($fo, "# " . sctxt($ar_talk[$t_line - 1]) . "\\0\n");
        break;
      
      // Recieve Item
      // item.add(item)
      // uint_8[0x03] uint_8[item]
      case 0x03:
        $t_item = $ar_item[fgetb($fd)];
        fputs($fo, "  item.add($t_item)\n");
        break;

      // ifDead Conditional Jump
      // branch.ifdead(goto, unit)
      // uint_8[0x04] uint_8[unit] uint_16[goto]
      case 0x04:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.ifdead($t_goto, $t_unit)\n");
        break;
      
      // ifAlive Conditional Jump
      // branch.ifalive(goto, unit)
      // uint_8[0x04] uint_8[unit] uint_16[goto]
      case 0x05:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.ifalive($t_goto, $t_unit)\n");
        break;
      
      // Branch if Local RAM Not Equal
      // branch.ne.local(goto, address1, address2)
      // uint_8[0x0a] uint_8[value] uint_16[goto]
      case 0x06:
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa47d0 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.ne.global($t_goto, $t_upper, $t_lower)\n");
        break;
      
      // Branch if Local RAM Equal
      // branch.eq.local(goto, address1, address2)
      // uint_8[0x0a] uint_8[value] uint_16[goto]
      case 0x07:
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa47d0 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.eq.global($t_goto, $t_upper, $t_lower)\n");
        break;
      
      // Local RAM Add/Sub
      // local.sum(target, variable)
      // local.sub(target, variable)
      // uint_8[0x08] uint_8[action] uint_8[value]
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
      
      // Branch if Global RAM Not Equal
      // branch.ne.global(goto, address1, address2)
      // uint_8[0x0a] uint_8[value] uint_16[goto]
      case 0x09:
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa4788 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.ne.global($t_goto, $t_upper, $t_lower)\n");
        break;
      
      // Branch if Global RAM Equal
      // branch.eq.global(goto, address1, address2)
      // uint_8[0x0a] uint_8[value] uint_16[goto]
      case 0x0a:
        list($t_upper, $t_lower) = splitbit(fgetb($fd));
        $t_upper = memstr(0xa4788 + $t_upper);
        $t_lower = memstr(0x7eb58 + $t_lower);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  branch.eq.global($t_goto, $t_upper, $t_lower)\n");
        break;
      
      // Global RAM Add/Sub
      // global.sum(target, variable)
      // global.sub(target, variable)
      // uint_8[0x0b] uint_8[action] uint_8[value]
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
      
      // Change Music
      // bgm(team, track)
      // uint_8[0x0c] uint_8[unit] uint_8[song]
      case 0x0c:
        $t_team = $ar_team[fgetb($fd)];
        $t_track = $ar_bgm[fgetb($fd)];
        fputs($fo, "  bgm($t_team, $t_track)\n");
        break;
      
      // Deploy
      // unit.deploy(unit, x, y)
      // uint_8[0x0e] uint_8[unit] uint_8[x] uint_8[y]
      case 0x0d:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        fputs($fo, "  unit.deploy($t_unit, $t_x, $t_y)\n");
        break;
      
      // Retreat
      // unit.retreat(unit)
      // uint_8[0x0e] uint_8[unit]
      case 0x0e:
        $t_unit = $ar_unit[fgetb($fd)];
        fputs($fo, "  unit.retreat($t_unit)\n");
        break;
      
      // Set Unit Byte
      // unit.setbyte(unit, position, byte)
      // uint_8[0x10] uint_8[unit] uint_8[position] uint_8[byte]
      case 0x10:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_position = memstr(fgetb($fd));
        $t_byte = hexstr(fgetb($fd));
        fputs($fo, "  unit.setbyte($t_unit, $t_position, $t_byte)\n");
        break;
      
      // Set Unit Bit
      // unit.setbit(unit, position, bit)
      // uint_8[0x11] uint_8[unit] uint_8[position] uint_8[bit]
      case 0x11:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_position = memstr(fgetb($fd));
        $t_bit = fgetb($fd);
        fputs($fo, "  unit.setbit($t_unit, $t_position, $t_bit)\n");
        break;
      
      // Clear Unit Bit
      // unit.clearbit(unit, position, bit)
      // uint_8[0x12] uint_8[unit] uint_8[position] uint_8[bit]
      case 0x12:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_position = memstr(fgetb($fd));
        $t_bit = fgetb($fd);
        fputs($fo, "  unit.clearbit($t_unit, $t_position, $t_bit)\n");
        break;
      
      // Clean Dead Units
      // unit.cleanup()
      // uint_8[0x13]
      case 0x13:
        fputs($fo, "  unit.cleanup()\n");
        break;
      
      // Set Next Scenario
      // loadscenario()
      // uint_8[0x14] uint_8[scenario]
      case 0x14:
        $t_scenario = fgetb($fd);
        fputs($fo, "  loadscenario($t_scenario)\n");
        break;
      
      // Game Over
      // gameover()
      // uint_8[0x15]
      case 0x15:
        fputs($fo, "  gameover()\n");
        break;
      
      // Goto
      // goto(goto)
      // uint_8[0x16] uint_16[goto]
      case 0x16:
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  goto($t_goto)\n");
        break;
      
      // Subroutine
      // subroutine(goto)
      // uint_8[0x17] uint_16[goto]
      case 0x17:
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  subroutine($t_goto)\n");
        break;
      
      // End Subroutine
      // endsub()
      // uint_8[0x18]
      case 0x18:
        fputs($fo, "endsub\n\n");
        break;
      
      // Prompt Yes/No
      // prompt.ok(goto)
      // uint_8[0x1d] uint_8[0x00] uint_8[0xf4] uint_16[0x0000] uint_8[0x22] uint_16[goto]
      case 0x1d:
        $t_null = fread($fd, 5);
        $t_goto = fgetw($fd); $pointers[] = $t_goto;
        $t_goto = "lbl_" . dechex($t_goto);
        fputs($fo, "  prompt.ok($t_goto)\n");
        break;
      
      // Deploy Unit
      // unit.showsub(unit)
      // uint_8[0x1e] uint_8[unit]
      case 0x1e:
        $t_unit = $ar_unit[fgetb($fd)];
        fputs($fo, "  unit.showsub($t_unit)\n");
        break;
      
      // Refresh Available Units
      // unit.refresh()
      // uint_8[0x28]
      case 0x28:
        fputs($fo, "  unit.refresh()\n");
        break;
      
      // Fade Out
      // screen.fadeout(time)
      // unit_8[0x38] uint_8[time]
      case 0x32:
        $t_time = fgetb($fd);
        fputs($fo, "  screen.fadeout($t_time)\n");
        break;
      
      // Position Unit
      // unit.position(unit, x, y)
      // uint_8[0x36] uint_8[unit] uint_8[x] uint_8[y]
      case 0x36:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        fputs($fo, "  unit.position($t_unit, $t_x, $t_y)\n");
        break;
      
      // Hide Unit
      // unit.hide(unit, target)
      // uint_8[0x37] uint_8[unit] uint_8[target]
      case 0x37:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_target = $ar_target[fgetb($fd)];
        fputs($fo, "  unit.hide($t_unit, $t_target)\n");
        break;
      
      // Fade In
      // screen.fadein(time)
      // unit_8[0x38] uint_8[time]
      case 0x38:
        $t_time = fgetb($fd);
        fputs($fo, "  screen.fadein($t_time)\n");
        break;

      // Hide All Characters
      // unit.hideall()
      // uint_8[0x39]
      case 0x39:
        fputs($fo, "  unit.hideall()\n");
        break;

      // Move Cusor to Commander
      // cusor.set(commander)
      // uint_8[0x3d] uint_8[unit]
      case 0x3d:
        $t_unit = $ar_unit[fgetb($fd)];
        fputs($fo, "  cursor.set($t_unit)\n");
        break;
      
      // Face Unit
      // unit.face(unit, direction)
      // uint_8[0x3e] uint_8[unit] uint_8[direction]
      case 0x3e:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_direction = $ar_unit[fgetb($fd)];
        fputs($fo, "  unit.face($t_unit, $t_direction)\n");
        break;
      
      // Move Unit
      // unit.move(unit, x, y)
      // uint_8[0x3f] uint_8[unit] uint_8[x] uint_8[y]
      case 0x3f:
        $t_unit = $ar_unit[fgetb($fd)];
        $t_x = fgetb($fd);
        $t_y = fgetb($fd);
        if($t_x == 255) $t_x = "PRESET";
        if($t_y == 255) $t_y = "PRESET";
        fputs($fo, "  unit.move($t_unit, $t_x, $t_y)\n");
        break;
      
      // Set Mask
      // screen.mask(r, g, b)
      // uint_8[0x42] uint_8[b] uint_8[g] uint_8[r]
      case 0x42:
        $t_b = fgetb($fd);
        $t_g = fgetb($fd);
        $t_r = fgetb($fd);
        fputs($fo, "  screen.mask($t_r, $t_g, $t_b)\n");
        break;
      
      // Three Option Prompt
      // prompt.options(goto1, goto2, line1, line2, line3, unk1, unk2, unk3, unk4)
      // uint_8[0x43] uint_8[unk1] uint_8[unk2] uint_8[unk3] uint_8[unk4] uint_8[line1] uint_8[line2] uint_8[line3] uint_8[goto1] uint_8[goto2]
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
      
      // Set Map
      // screen.map(map)
      // uint_8[0x47] uint_8[map]
      case 0x47:
        $t_map = fgetb($fd);
        fputs($fo, "  screen.map($t_map)\n");
        break;
      
      // Receive Money
      // money.add(money)
      // uint_8[0x4e] uint_16[money]
      case 0x4e:
        // Game money's lowest increment is 10
        $t_money = (fgetw($fd)) * 10;
        fputs($fo, "  money.add($t_money)\n");
        break;
      
      // Scenario Clear
      // scenarioclear()
      // uint_8[0x4f]
      case 0x4f:
        fputs($fo, "  scenarioclear()\n");
        break;
      
      // Raise Stat
      // unit.raisestat(stat, amount)
      // uint_8[0x40] uint_8[stat] uint_8[amount]
      case 0x40:
        $t_stat = fgetb($fd);
        $t_amount = fgetb($fd);
        fputs($fo, "  unit.raisestat($t_stat, $t_amount)\n");
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
  unset($pointers);
}

?>