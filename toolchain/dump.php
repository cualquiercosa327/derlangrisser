#!/usr/bin/php -q
<?php

echo "Der Langrisser Script Dumper (cli)\n";
echo "Copyright (c) 2007, 2017 Derrick Sobodash\n";

set_time_limit(6000000);

$tables = array("resources/tables/00.tbl",
                "resources/tables/f7.tbl",
                "resources/tables/f8.tbl",
                "resources/tables/f9.tbl",
                "resources/tables/fa.tbl",
                "resources/tables/fb.tbl",
                "resources/tables/name.dat",
                "resources/tables/word.dat" );

// Make sure all tables are present
foreach($tables as $file)
	if(!file_exists($file))
		die("Fatal error: $file not found.\n");

// Check md5 of game binary
if (!file_exists("resources/dl.rom"))
	die("Fatal error: dl.smc not found.\n");
if(md5(file_get_contents("resources/dl.rom")) != "91d62c4cb790fc2fb38b10b68616e228")
	die("Fatal error: dl.rom md5 mismatch, expected 91d62c4cb790fc2fb38b10b68616e228\n");

// Fetch tables to an array
$fddump = file_get_contents($tables[0]);
$tbl00 = explode ("\n", $fddump);
//for($l = 0; $l < count($tbl00); $l++) {
//	$tbl00[$l] = trim($tbl00[$l]);
//}

$fddump = file_get_contents($tables[1]);
$tblf7 = explode ("\n", $fddump);
//for($l = 0; $l < count($tblf7); $l++) {
//	$tblf7[$l] = trim($tblf7[$l]);
//}

$fddump = file_get_contents($tables[2]);
$tblf8 = explode ("\n", $fddump);
//for($l = 0; $l < count($tblf8); $l++) {
//	$tblf8[$l] = trim($tblf8[$l]);
//}

$fddump = file_get_contents($tables[3]);
$tblf9 = explode ("\n", $fddump);
//for($l = 0; $l < count($tblf9); $l++) {
//	$tblf9[$l] = trim($tblf9[$l]);
//}

$fddump = file_get_contents($tables[4]);
$tblfa = explode ("\n", $fddump);
//for($l = 0; $l < count($tblfa); $l++) {
//	$tblfa[$l] = trim($tblfa[$l]);
//}

$fddump = file_get_contents($tables[5]);
$tblfb = explode ("\n", $fddump);
//for($l = 0; $l < count($tblfb); $l++) {
//	$tblfb[$l] = trim($tblfb[$l]);
//}

$fddump = file_get_contents($tables[6]);
$names = explode ("\n", $fddump);
//for($l = 0; $l < count($names); $l++) {
//	$names[$l] = trim($names[$l]);
//}

$fddump = file_get_contents($tables[7]);
$words = explode ("\n", $fddump);
//for($l = 0; $l < count($words); $l++) {
//	$words[$l] = trim($words[$l]);
//}

// Special Cases
$tbl00[0] = "{end}\n\n";  $tbl00[1] = "{01}";
$tbl00[2] = "{02}";       $tbl00[3] = "{03}";
$tbl00[4] = "{04}";       $tbl00[6] = "{06}";
$tbl00[7] = "{07}\n";     $tbl00[8] = "\n";
$tbl00[9] = "{09}";       $tbl00[252] = "{FC}";
$tbl00[253] = "{FD}";     $tbl00[254] = "{FE}";
$tbl00[255] = "{FF}";

$fd = fopen("resources/dl.rom", "rb");
$s = 0;

for($i = 0x120000; $i < 0x12011d; $i = $i + 3) {
	fseek($fd, $i, SEEK_SET);
	$pointer = strrev(fread($fd, 3));
	$pointertable[$s] = 0x120000 + hexdec(bin2hex($pointer));
	$s++;
}
unset($fixpointer, $s, $pointer);

@mkdir("resources/scripts");
@mkdir("resources/scripts/jp");
@mkdir("resources/scripts/en");

for ($i = 0; $i < (count($pointertable) - 1); $i++) {
	print "Dumping script #" . str_pad($i, 2, "0", STR_PAD_LEFT) . "... ";
	
	$output = "";
	$pointer = $pointertable[$i];
	
	while($pointer < $pointertable[$i + 1]) {
		fseek($fd, $pointer, SEEK_SET);
	
		$charchar = fread($fd, 1);
		
		if(ord($charchar) == 0x01) {
			$pointer++;
			fseek($fd, $pointer, SEEK_SET);
			$output .= "{01}{" . bin2hex(fread($fd, 1)) . "}";
		}
		else if(ord($charchar) == 0x09) {
			$pointer++;
			fseek($fd, $pointer, SEEK_SET);
			//$output .= "{09}{" . bin2hex(fread($fd, 1)) . "}";
			$output .= $names[ord(fread($fd, 1))];
		}
		else if(ord($charchar) == 0xf7) {
			$pointer++;
			fseek($fd, $pointer, SEEK_SET);
			$output .= $tblf7[ord(fread($fd, 1))];
		}
		else if(ord($charchar) == 0xf8) {
			$pointer++;
			fseek($fd, $pointer, SEEK_SET);
			$output .= $tblf8[ord(fread($fd, 1))];
		}
		else if(ord($charchar) == 0xf9) {
			$pointer++;
			fseek($fd, $pointer, SEEK_SET);
			$output .= $tblf9[ord(fread($fd, 1))];
		}
		else if(ord($charchar) == 0xfa) {
			$pointer++;
			fseek($fd, $pointer, SEEK_SET);
			$output .= $tblfa[ord(fread($fd, 1))];
		}
		else if(ord($charchar) == 0xfb) {
			$pointer++;
			fseek($fd, $pointer, SEEK_SET);
			$output .= $tblfb[ord(fread($fd, 1))];
		}
		else {
			if($tbl00[ord($charchar)] == "{04}") {
				$pointer++;
				fseek($fd, $pointer, SEEK_SET);
				$output .= $words[ord(fread($fd, 1))];
			}
			else if($tbl00[ord($charchar)] == "{02}") {
				//$pointer++;
				//fseek($fd, $pointer, SEEK_SET);
				//$output .= $names[ord(fread($fd, 1))];
				$output .= "{02}";
			}
			else {
				$output .= $tbl00[ord($charchar)];
			}
		}
		$pointer++;
	}

	$fo = fopen("resources/scripts/jp/sc" . str_pad($i, 2, "0", STR_PAD_LEFT) . ".txt", "w");
	fputs($fo, $output);
	fclose($fo);
	print "done!\n";
	unset ($fo, $output, $cast, $charchar);
}

?>
