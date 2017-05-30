;
; Der Langrisser Window Patches
;
; This files contains Super Nintendo code for repositioning and resizing
; all the menus and windows in Der Langrisser's user interface.
;
; Version:   1.0
; Author:    byuu
; Copyright: (c) 2006, 2016 DL Team
; Website:   https://github.com/sobodash/derlangrisser/
; License:   BSD License <http://opensource.org/licenses/bsd-license.php>
;

lorom

;---Window Backgrounds (Variant)---------
  ;Color Subtract Mode
  org $42d4 : db $83

  ;Make the main windows light blue
  org $fba5 : dw $3063

  ;Window Shades
  org $437a
  db $3c,$4f,$8c  ;Ally Window
  db $30,$46,$8e  ;NPC Window
  db $25,$4e,$8e  ;Enemy Window

;---Format Description-------------------
; db $RR,$GG,$BB
; For each $XX = bgriiiii
; Example: $30 = 00110000 (Green: 50%)
;
; R-Range - 0x20 - 0x3f
; G-Range - 0x40 - 0x5f
; B-Range - 0x80 - 0x9f
