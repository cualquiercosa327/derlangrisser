lorom

org $00bcb0 : jml decomp : nop #2

loadpc build/dl.xpc

verify_intro_integrity_lsr() {
  php : rep #$30 : pha : phx

;verify that intro routine has not been disabled ...
  lda #$4000 : clc : adc #$bffc : tax
  lda $800000,x : lsr : cmp #$4000 : beq +
  stp
+ lda #$4000 : clc : adc #$4000 : tax
  lda $800000,x : lsr : cmp #$1026 : beq +
  stp
+ inx #2
  lda $800000,x : lsr : cmp #$6c40 : beq +
  stp
+ lda #$4000 : clc : adc #$4020 : tax
  lda $800000,x : lsr : cmp #$002e : beq +
  stp
+ inx #2
  lda $800000,x : lsr : cmp #$2040 : beq +
  stp
+

  plx : pla : plp : rts
}

decomp() {

;e008
  lda $04 : cmp #$2e : bne +
  lda $03 : cmp #$b7 : bne +
  lda $02 : cmp #$b8 : bne +
  jml decomp_e008
+

;e024
  lda $04 : cmp #$2f : bne +
  lda $03 : cmp #$87 : bne +
  lda $02 : cmp #$4e : bne +
  jml decomp_e024
+

;e072
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
  ;jsr verify_intro_integrity_lsr

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

;$0547 = 0d->25
;correct character portrait for Aaron's dialog box,
;where it was showing Cherie's portrait before.
e008:
  incbin resources/data/e008.bin
e008_end:

;$0372 = eb->ec [43]
;correct Vampire Lord portrait
;$038d = eb->f7 [102]
;$039a = eb->f7 (untested)
;correct Esto portrait
;$03fe = f0->f7 [53]
;correct Esto portrait
e024:
  incbin resources/data/e024.bin
e024_end:

;$02d3 = ed->ee
;correct character portrait for serpent's dialog box,
;where it was showing dullahan's portrait before.
e072:
  incbin resources/data/e072.bin
e072_end:

savepc build/dl.xpc
