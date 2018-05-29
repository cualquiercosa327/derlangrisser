#!/usr/bin/php -q
<?php

echo "Der Langrisser Script Scanner (cli)\n";
echo "Copyright (c) 2007, 2016 Derrick Sobodash\n";

if(!isset($argv[1])) { ?>

Search files for string:
  scscan.php "(needle)"

Target "needle" should be enclosed in quotes.

<?php
	die();
}

echo "Scanning scripts for: \"{$argv[1]}\"\n\n";
echo "--------------------------------------------------------------------------------\n";

$argv[1] = str_replace(array("\\n", "\\r", "\\t"), array("\n", "\r", "\t"), $argv[1]);

for($i = 0; $i < 94; $i ++) {
	if(!file_exists("resources/scripts/en/sc".str_pad($i, 2, "0", STR_PAD_LEFT).".txt"))
		continue;

	$en_file = explode("{end}\n\n", file_get_contents("resources/scripts/en/sc".str_pad($i, 2, "0", STR_PAD_LEFT).".txt"));

	for($k = 0; $k < count($en_file); $k ++) {
		if(@strpos(strtolower($en_file[$k]), strtolower($argv[1])) !== FALSE) {
			echo "Script #$i:".($k+1)."\n{$en_file[$k]}\n";
			echo "--------------------------------------------------------------------------------\n";
		}
	}
}

?>
