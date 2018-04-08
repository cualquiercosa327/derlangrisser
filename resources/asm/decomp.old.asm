;
; Der Langrisser Decompressed Blocks
;
; This file contains Super Nintendo code to bypass the game's compression
; system and insert several decompressed scenario data blocks. We use this to
; correct several bugs in the game where the programmers displayed the wrong
; character portraits.
;
; Version:   1.0
; Author:    byuu
; Copyright: (c) 2006, 2016 DL Team
; Website:   https://github.com/sobodash/derlangrisser/
; License:   BSD License <http://opensource.org/licenses/bsd-license.php>
;

lorom

org $00bcb0 : jml decomp : nop #2

;Avatar fix from yugisokubodai
org $07a3e9 : jml fix_avatar_06

loadpc build/dl.xpc

decomp() {

; e008
  lda $04 : cmp #$2e : bne +
  lda $03 : cmp #$b7 : bne +
  lda $02 : cmp #$b8 : bne +
  jml decomp_e008
+

; e024
  lda $04 : cmp #$2f : bne +
  lda $03 : cmp #$87 : bne +
  lda $02 : cmp #$4e : bne +
  jml decomp_e024
+

; e072
  lda $04 : cmp #$30 : bne +
  lda $03 : cmp #$ec : bne +
  lda $02 : cmp #$ee : bne +
  jmp decomp_e072
+

  lda $08 : pha
  lda $09 : pha
  jml $00bcb6
}

decomp_e008() {
  php : rep #$30 : pha : phx : phy
  sep #$20
  ldx #$0000

- lda e008,x : sta [$05]
  inc $05 : bne + : inc $06
+ inx : cpx.w #e008_end-e008 : bcc -

  rep #$30 : ply : plx : pla : plp : rtl
}

decomp_e024() {
  php : rep #$30 : pha : phx : phy
  sep #$20
  ldx #$0000

- lda e024,x : sta [$05]
  inc $05 : bne + : inc $06
+ inx : cpx.w #e024_end-e024 : bcc -

  rep #$30 : ply : plx : pla : plp : rtl
}

decomp_e072() {
  php : rep #$30 : pha : phx : phy
  sep #$20
  ldx #$0000

- lda e072,x : sta [$05]
  inc $05 : bne + : inc $06
+ inx : cpx.w #e072_end-e072 : bcc -

  rep #$30 : ply : plx : pla : plp : rtl
}


;------------------------------------------------------------------------------
;Avatar fix from yugisokubodai

fix_avatar_06:
  lda [$02],y : bne emu_a405
  lda $1d88
  jml $07a3f0
fix_avatar_06_end:

;If Avatar = 36 (Angry Vargas) then jump to Scenario check
emu_a405:
  cmp #$36 : beq check_scenario_06
  jml $07a405
emu_a405_end:

;If Scenario = 6 then jump to check which Vargas' avatar
check_scenario_06:
  pha
  lda $7e1a00 : cmp #$06 : beq check_vargas
  pla : jml $07a405
check_scenario_06_end:

;If pointer = #$c272 then correct the avatar
check_vargas:
  lda $02 : cmp #$72 : beq right_avatar_06
  pla : jml $07a405
check_vargas_end:

right_avatar_06:
  pla
  lda #$01
  jml $07a405
right_avatar_06_end:

;------------------------------------------------------------------------------


; $0547 = 0d->25
; Correct Aaron's portrait (was Cherie)
e008:
  incbin resources/data/e008.bin
e008_end:

; $0372 = eb->ec [43]
; Correct Vampire Lord portrait
; $038d = eb->f7 [102]
; $039a = eb->f7 (untested)
; Correct Esto portrait
; $03fe = f0->f7 [53]
; Correct Esto portrait
e024:
  incbin resources/data/e024.bin
e024_end:

; $02d3 = ed->ee
; Correct Serpent's portrait (was Dullahan)
e072:
  incbin resources/data/e072.bin
e072_end:

savepc build/dl.xpc
