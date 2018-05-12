#!/usr/bin/php -q
<?php

echo "Der Langrisser Event Dumper (cli)\n";
echo "Copyright (c) 2018 Derrick Sobodash\n";

set_time_limit(6000000);

$bgm_array = array(
  "Neo Holy War",
  "Knights Errant",
  "No Surrender",
  "Fight it Out",
  "The Legend of the Sword",
  "Soldier",
  "Last Battle",
  "Ally Backup 1",
  "Ally Backup 2",
  "Ally Backup 3",
  "Leon",
  "Vargas",
  "Morgan",
  "Evil Enemy",
  "Egbert",
  "Imelda",
  "Dark Princess",
  "Boser",
  "Bernhardt",
  "Enemy Reinforcements 1",
  "Enemy Reinforcements 2",
  "Shop",
  "Story",
  "Liana",
  "Aniki",
  "Requiem",
  "Ending 1",
  "Ending 2",
  "Inside of a Cave",
  "Quiet Ending",
  "Ending Completion",
  "Scenario Clear",
  "Opening 1",
  "Requiem Lushiris",
  "Opening 2",
  "Beach Ambience",
  "Night Ambience",
  "Jessica",
  "Staff Roll");

$wpn_array = array(
  "Dagger",
  "War Hammer",
  "Long Sword",
  "Magic Wand",
  "Inferno Lance",
  "Devil Axe",
  "Dragon Slayer",
  "Langrisser",
  "Langrisser (True)",
  "Iron Dumbbell",
  "Masayan Sword",
  "Orb",
  "Holy Rod",
  "Holy Rod",
  "Dark Rod",
  "Long Bow",
  "Arbalest",
  "Alhazard",
  "Alhazard (True)",
  "Odin's Buckler",
  "Buckler",
  "Shield",
  "Chainmail",
  "Platemail",
  "Assault Suit",
  "Cloak",
  "Dragon Scale",
  "Wizard's Robe",
  "Amulet",
  "Cross",
  "Necklace",
  "Swift Boots",
  "Crown",
  "Gleipnir",
  "Rune Stone");

$events = array(
  "resources/events/ev01.dat",
  "resources/events/ev02.dat",
  "resources/events/ev03.dat",
  "resources/events/ev04.dat",
  "resources/events/ev05.dat",
  "resources/events/ev06.dat",
  "resources/events/ev07.dat",
  "resources/events/ev08.dat",
  "resources/events/ev09.dat",
  "resources/events/ev10.dat",
  "resources/events/ev11.dat",
  "resources/events/ev12.dat",
  "resources/events/ev13.dat",
  "resources/events/ev14.dat",
  "resources/events/ev15.dat",
  "resources/events/ev16.dat",
  "resources/events/ev17.dat",
  "resources/events/ev18.dat",
  "resources/events/ev19.dat",
  "resources/events/ev20.dat",
  "resources/events/ev21.dat",
  "resources/events/ev22.dat",
  "resources/events/ev23.dat",
  "resources/events/ev24.dat",
  "resources/events/ev25.dat",
  "resources/events/ev26.dat",
  "resources/events/ev27.dat",
  "resources/events/ev28.dat",
  "resources/events/ev29.dat",
  "resources/events/ev30.dat",
  "resources/events/ev31.dat",
  "resources/events/ev32.dat",
  "resources/events/ev37.dat",
  "resources/events/ev38.dat",
  "resources/events/ev39.dat",
  "resources/events/ev40.dat",
  "resources/events/ev41.dat",
  "resources/events/ev42.dat",
  "resources/events/ev43.dat",
  "resources/events/ev44.dat",
  "resources/events/ev45.dat",
  "resources/events/ev46.dat",
  "resources/events/ev47.dat",
  "resources/events/ev48.dat",
  "resources/events/ev49.dat",
  "resources/events/ev50.dat",
  "resources/events/ev51.dat",
  "resources/events/ev52.dat",
  "resources/events/ev53.dat",
  "resources/events/ev54.dat",
  "resources/events/ev55.dat",
  "resources/events/ev56.dat",
  "resources/events/ev57.dat",
  "resources/events/ev58.dat",
  "resources/events/ev59.dat",
  "resources/events/ev60.dat",
  "resources/events/ev61.dat",
  "resources/events/ev62.dat",
  "resources/events/ev63.dat",
  "resources/events/ev64.dat",
  "resources/events/ev65.dat",
  "resources/events/ev66.dat",
  "resources/events/ev67.dat",
  "resources/events/ev68.dat",
  "resources/events/ev69.dat",
  "resources/events/ev70.dat",
  "resources/events/ev71.dat",
  "resources/events/ev72.dat",
  "resources/events/ev73.dat",
  "resources/events/battle.dat",
  "resources/events/lushiris.dat");

if (!file_exists("resources/events/units.txt"))
	die("Fatal error: Units list not found.\n");
$units = explode("\n", "resources/events/units.txt");
if (!file_exists("resources/events/items.txt"))
	die("Fatal error: Units list not found.\n");
$items = explode("\n", "resources/events/items.txt");
if (!file_exists("resources/events/music.txt"))
	die("Fatal error: Units list not found.\n");
$music = explode("\n", "resources/events/music.txt");

// Make sure all events are present
foreach($events as $file)
	if(!file_exists($file))
		die("Fatal error: $file not found.\n");

for($i = 0; $i < count($events); $i++) {
	$fd = fopen($events[$i], "rb");
	$fo = fopen(substr($events[$i], 0, -3) . "dls", "w");
	
	// Read section jumps
	$begin = ord(fgetc($fd)) + (ord(fgetc($fd)) << 8);
	fputs($fo, "0 JUMP $begin\n");
	while(ftell($fd) < $begin)
	  fputs($fo, ftell($fd) . " JUMP " . ord(fgetc($fd)) + (ord(fgetc($fd)) << 8) . "\n");
	
	// Begin dumping events
	fputs($fo, ftell($fd) . " ");
	$code = ord(fgetc($fd));
	switch($code) {
	  case(0xff):
	    if(ord(fgetc($fd)) == 0xff)
	      fputs($fo, "END\n");
	  case(0x04):
	    
	}
	
}

?>