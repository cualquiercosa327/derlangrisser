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

; ----------------------------------------------------------------------------
; Erwin's name in the ending credits loads from the 8x8 name list,
; rather than from the name the player enters at the intro prompt.
; The current name to load is stored in $19, and to display the player's name,
; #$01 is used.
;
; During the ending credits, Erwin's name is stored as #$c9, rather than #$01.
; The original game detected anything >= #$c8 as a special case and ignored
; the test to see if this was Erwin's name by subtracting #$c9 from $19
; after the test for if $19 was #$01.
;
; The below code reverses the order of tests to subtract #$c8 if $19 >= #$c8
; first, and then afterwards tests for #$01 to see if it should use
; the player's name instead. It appears this code existed as a way for always
; displaying 'Erwin' no matter what during the ending. However, every other
; reference to Erwin in the ending uses the player's name, so we have modified
; it below for the sake of consistency.
;
; The player's name is always stored at $001cb9 in WRAM, hence it is hardcoded.
; The routine is also very sensitive to modifications: it is not optimized
; because the game appears to use hard jumps into the middle of these functions.

org $02bc12
  lda $19
  cmp #$c8 : bcc +
  sbc #$c8 : sta $19
+ lda $19
  cmp #$01 : bne +
  lda #$b9 : sta $05
  lda #$1c : sta $06
  lda #$00 : sta $07
  bra ++
+ stz $1a
  rep #$30
  lda $19 : asl : tay
  lda [$05],y : sta $05
  sep #$30
++


; ----------------------------------------------------------------------------
; Reposition unit name in vram on unit purchase screen so that expanded
; unit names previously written do not write into the space previously
; expected to be empty and thus avoid corrupted unit names on previous screen
; while purchase screen is open.
;
; math:     name + length * max.units
; previous: 8 + 3 * 6 = 26 (cast to 32) 7000->7200
; new     : 8 + 8 * 6 = 56 (cast to 64) 7000->7400
org $03ae3c
  lda #$00 : sta $60
  lda #$74 : sta $61


; ----------------------------------------------------------------------------
; This code switches the order of printing from class and character name to
; character and class name. This creates room to add a dash between them.

; Reposition classes
org $02b82a
  ldy #$01 : lda [$11],y : sta $19 ; 00=class, 01=name
  lda.b #names     : sta $05
  lda.b #names>>8  : sta $06
  lda.b #names>>16 : sta $07
  lda #$16 : sta $08
  lda #$02 : sta $09
  lda #$00 : sta $0a
  lda #$40 : sta $17
  jsl $02bbf0

org $02b850
  ldy #$00 : lda [$11],y : sta $19 ; 00=class, 01=name
  lda.b #classes     : sta $05
  lda.b #classes>>8  : sta $06
  lda.b #classes>>16 : sta $07
  lda #$d6 : sta $08
  lda #$01 : sta $09
  lda #$00 : sta $0a
  lda #$40 : sta $17
  jsl $02bbf0

; Class pointers
org $04ef4a : db classes
org $04ef4e : db classes>>8
org $04ef52 : db classes>>16
org $01fb68 : dl classes
org $01fb70 : db classes>>16
org $03a64c : dl classes
org $03a654 : db classes>>16
org $03afb6 : dl classes
org $03afbe : db classes>>16
org $03b5e6 : dl classes
org $03b5ee : db classes>>16

; Spoils of War Screen
org $01f916
  lda names,x
  sta $05
  sep #$30
  lda.b #names>>16
  sta $07

org $02bc09 : db names
org $02bc0f : db names>>8

; Opening Text
org $02b8bb
opening_text:
  ldx #$0c
loop_ot:
  lda s_opening,x
  sta $025a,x
  dex
  dex
  bpl loop_ot
  sep #$20
  jmp $b9ad ; Skip . and " from 'opuningu'

;Ending Text
org $02b8db
  ldx #$0a
loop_et:
  lda s_ending,x
  sta $025a,x
  dex
  dex
  bpl loop_et
  sep #$20
  jmp $b9ad ; Skip 2 "s from 'endeingu'

; Range/Bonus Text
org $02f2b6 : dw $0030,$0031,$0032,$0033,$0040
org $0380dd : dw $0040,$0030,$0031,$0032,$0033
org $03822b : dw $0040,$0030,$0031,$0032,$0033
org $03810f : dw $0034,$0035,$0036,$0037,$0040
org $03826f : dw $0034,$0035,$0036,$0037,$0040

; Position of unit text on char info screen
org $038d52 : lda #$0506

; Position of weapon icon on char info screen
org $038fd0 : lda #$050f

; Position of armor icon on char info screen
org $03900e : lda #$0511

; Reposition soldier assignment window
org $03a88a : lda #$02 ; x position
org $03a88e : lda #$09 ; y position
org $03a892 : lda #$1c ; width
org $03a87e : lda #$1c ; width

; Reposition invisible soldier assignment window
org $03a961 : lda #$03 ; x position
org $03a966 : lda #$0a ; y position
org $03a96b : lda #$1a ; width
org $03a975 : lda #$19 ; x cursor position

; Reposition soldier purchase window
org $03aed6 : lda #$08 ; x position
org $03aeda : lda #$0d ; y position
org $03aede : lda #$13 ; width
org $03aeca : lda #$13 ; width

org $5e8000
classes:
  incbin build/text8ce.ptr

org $5e8200
  incbin build/text8ce.bin

org $5ec000
names:
  incbin build/text8ne.ptr

org $5ec200
  incbin build/text8ne.bin

loadpc build/dl.xpc

s_opening:
  dw $200b,$200c,$200d,$200e,$200f,$20ab,$2000
s_ending:
  dw $2000,$20ac,$20ad,$20ae,$20af,$2000

savepc build/dl.xpc
