#!/usr/bin/php -q
<?php

echo "IPS Patch Creator (cli)\n";
echo "Copyright (c) 2007, 2019 Derrick Sobodash\n";

create_ips("./resources/dl.rom", "./build/dl.sfc", "./dl.ips");


/******************************************************************************
** ppack()
*******************************************************************************
** syntax: string ppack(string value[, int bits, char endian])
*******************************************************************************
** Packs an integer value to a binary string. If bits is not specified, it is
** assumed to be 16 bit--bits must be a multiple of 8. Endian format is
** assumed to be little endian unless endian equals "b".
*******************************************************************************
** Example: echo bin2hex(ppack(0x19945, 24));
** Return : 459901
******************************************************************************/

function ppack($value, $bits = 16, $endian = "l")
{

  if(is_numeric($value) && $value <= 0xffffffff)
  {

    if($endian == "b")
      return(substr(pack("N", $value), 4 - $bits / 8));

    else
      return(substr(pack("V", $value), 0, $bits / 8));

  }

  else if(extension_loaded('gmp'))
  {

    $high = gmp_strval(gmp_div($value, "0x100000000"), 16);
    $low  = gmp_strval(gmp_mod($value, "0x100000000"), 16);

    if(strlen($high) % 2 > 0)
      $high = "0" . $high;
    
    if($endian == "b")
      return(substr(pack("H*", $high) . pack("H*", $low), 8 - $bits / 8));

    else
      return(substr(strrev(pack("H*", $low)) . strrev(pack("H*", $high)), 0, $bits / 8));

  }

  else
    die("Error: ppack(): GMP is required for integers over 32-bits\n\n");

}


/******************************************************************************
** punpack()
*******************************************************************************
** syntax: int punpack(string value[, char endian])
*******************************************************************************
** Unpacks a binary string value to a decimal. Endian format is assumed to
** be little endian unless endian equals "b".
*******************************************************************************
** Example: echo punpack(ppack(0xedbeef, 24));
** Return : 15580911
******************************************************************************/

function punpack($value, $endian = "l")
{

  if($endian != "b")
    $value = strrev($value);

  if(strlen($value) <= 4)
    return(hexdec(bin2hex($value)));

  else if(extension_loaded('gmp'))
  {

    $low  = hexdec(bin2hex((substr($value, -4))));
    $high = hexdec(bin2hex((substr($value, 0, strlen($value) - 4))));

    return(gmp_strval(gmp_add(gmp_mul("$high", "0x100000000"), "$low")));

  }

  else
    die("Error: punpack(): GMP is required for integers over 32-bits\n\n");

}



/******************************************************************************
** create_ips()
*******************************************************************************
** syntax: void create_ips(string source_file, string modified_file[,
**         string ips_file])
*******************************************************************************
** Creates an IPS file by comparing the source file and modified file and
** writes the output to ips_file. If ips_file is not specified,
** modified_file + ".ips" will be used. Compatable with IPS RLE and the
** LunarIPS CUT command.
******************************************************************************/

function create_ips($source_file, $modified_file, $ips_file = null)
{

  // Set a default IPS filename if none was specified
  if($ips_file == null)
    $ips_file = $modified_file . ".ips";

  // Make sure the input is all valid
  if(!file_exists($source_file))
    die("Error: create_ips(): Unable to open $source_file\n\n");
  if(!file_exists($modified_file))
    die("Error: create_ips(): Unable to open $modified_file\n\n");

  // Open the files for comparing
  $src = fopen($source_file, "rb");
  $mod = fopen($modified_file, "rb");

  $patch = "PATCH";

  if(filesize($modified_file) < filesize($source_file))
    $end_at = filesize($modified_file);
  else
    $end_at = filesize($source_file);

  while(ftell($mod) < $end_at - 1)
  {

    $src_char = fgetc($src);
    $mod_char = fgetc($mod);
    $bytes = "";
    $rle_time = 0;

    if($src_char != $mod_char)
    {

      $patch .= ppack(ftell($mod) - 1, 24, "b");

      while($src_char != $mod_char && ftell($mod) != $end_at + 1)
      {

        if(strlen($bytes) > 4 && punpack(substr($bytes, -4, 4) ^ (substr($bytes, -4, 1).substr($bytes, -4, 1).substr($bytes, -4, 1).substr($bytes, -4, 1)), "b") == 0)
        {

          $rle_time = 1;
          $bytes = substr($bytes, 0, -4);

          fseek($mod, ftell($mod) - 5);
          fseek($src, ftell($mod));

          $rle_off = ftell($mod);
          $rle_char = fgetc($mod);
          $rle_len = 0;

          while($mod_char == $rle_char && $rle_len < 0xffff)
          {

            $rle_len ++;
            $mod_char = fgetc($mod);

          }

          fseek($mod, ftell($mod) - 1);
          fseek($src, ftell($mod));

          break 1; // Exit the while()

        }

        $bytes .= $mod_char;
        $src_char = fgetc($src);
        $mod_char = fgetc($mod);

      }

      $patch .= ppack(strlen($bytes), 16, "b") . $bytes;

      if($rle_time == 1)
      {

        $patch .= ppack($rle_off, 24, "b") . ppack(0, 16, "b") . ppack($rle_len, 16, "b") . $rle_char;
        $rle_time = 0;

      }

      else
      {

        fseek($src, ftell($src)-1, SEEK_SET);
        fseek($mod, ftell($mod)-1, SEEK_SET);

      }

    }

  }


  if(filesize($modified_file) > $end_at)
  {

    fseek($mod, $end_at, SEEK_SET);

    while(ftell($mod) < filesize($modified_file) - 1)
    {

      $mod_char = fgetc($mod);
      $bytes = $mod_char;

      $patch .= ppack(ftell($mod) - 1, 24, "b");

      while(ftell($mod) < filesize($modified_file) && strlen($bytes) < 0xffff)
      {

        if(strlen($bytes) > 4 && punpack(substr($bytes, -4, 4) ^ (substr($bytes, -4, 1).substr($bytes, -4, 1).substr($bytes, -4, 1).substr($bytes, -4, 1)), "b") == 0)
        {

          $rle_time = 1;
          $bytes = substr($bytes, 0, -4);
          fseek($mod, ftell($mod) - 4);

          $rle_off = ftell($mod);
          $rle_char = fgetc($mod);
          $rle_len = 0;

          while($mod_char == $rle_char && $rle_len < 0xffff)
          {

            $rle_len ++;
            $mod_char = fgetc($mod);

          }

          fseek($mod, ftell($mod) - 1);

          break 1; // Exit the while()

        }

        $mod_char = fgetc($mod);
        $bytes .= $mod_char;

      }

      $patch .= ppack(strlen($bytes), 16, "b") . $bytes;

      if($rle_time == 1)
      {

        $patch .= ppack($rle_off, 24, "b") . ppack(0, 16, "b") . ppack($rle_len, 16, "b") . $rle_char;
        $rle_time = 0;

      }

    }

    $patch .= "EOF";

  }

  else if(filesize($modified_file) == filesize($source_file))
    $patch .= "EOF";

  else
    $patch .= "EOF" . ppack(filesize($modified_file), 24, "b");

  fclose($mod);
  fclose($src);

  $fo = fopen($ips_file, "w");
  fputs($fo, $patch);
  fclose($fo);

}

?>
