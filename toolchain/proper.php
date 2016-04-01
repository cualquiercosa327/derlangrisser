#!/usr/bin/php -q
<?php

for($i = 0; $i < 94; $i++) {
	if(!file_exists("../resources/scripts/en/sc".str_pad($i, 2, "0", STR_PAD_LEFT).".txt"))
		continue;
	$junk = file_get_contents("../resources/scripts/en/sc".str_pad($i, 2, "0", STR_PAD_LEFT).".txt");
	if(strpos($junk, "{{"))
		echo "Found {{ in script $i\n";
	if(strpos($junk, "["))
		echo "Found [ in script $i\n";
	if(strpos($junk, "]"))
		echo "Found ] in script $i\n";
	if(substr_count($junk, "{") !== substr_count($junk, "}"))
		echo "OH SHIT! { != } in script $i :(\n";
}


$fl_scr = "scripts";
$failed = 0;

for($i = 0; $i < 94; $i ++)
{

	if(!file_exists("$fl_scr/en/sc".str_pad($i, 2, "0", STR_PAD_LEFT).".txt"))
		continue;

	$en_file = explode("{end}\n\n", file_get_contents("$fl_scr/en/sc".str_pad($i, 2, "0", STR_PAD_LEFT).".txt"));
	$jp_file = explode("{end}\n\n", file_get_contents("$fl_scr/jp/sc".str_pad($i, 2, "0", STR_PAD_LEFT).".txt"));

	echo "  Comparing block ".str_pad($i, 2, "0", STR_PAD_LEFT)." ... ";

	if(count($en_file) != count($jp_file))
	{

		echo "FAIL! (JP=".count($jp_file)." EN=".count($en_file).")\n";
		$failed ++;

		$fo = fopen("$fl_scr/track/sc".str_pad($i, 2, "0", STR_PAD_LEFT).".htm", "w");
		fputs($fo, "<table>\n");
		if(count($en_file) > count($jp_file))
			$lele = count($en_file);
		else if(count($en_file) < count($jp_file))
			$lele = count($jp_file);
		for($k = 0; $k < $lele; $k ++)
			fputs($fo, "<tr>\n<td>{$jp_file[$k]}</td>\n<td>{$en_file[$k]}</td>\n</tr>\n");
		fputs($fo, "</table>\n");

	}
	else
		echo "OK\n";

}

if($failed > 0)
	die("\nNumber of failed scripts: $failed.\n\nThe build process has halted! Please resolve these failures and try again.\n\n");
else
	echo ("All scripts are valid.\n\n");



echo "exit";
?>
