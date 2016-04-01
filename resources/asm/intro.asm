lorom

org $008020 : jml intro

;update header information
org $ffc0 : db 'Der Langrisser       '
org $ffd7 : db $0c ;24mbit ROM
org $ffd9 : db $01 ;North American release

;patch version signature
;org $24811d
;  db $00,$72,$78,$9c,$4b,$f5,$2a,$f7,$09,$2e,$36,$f4,$0a,$09,$ae,$f0
;  db $2f,$09,$f3,$0a,$74,$76,$2d,$2e,$f1,$2e,$36,$f7,$ad,$32,$f4,$2d
;  db $37,$74,$09,$48,$77,$2e,$f3,$31,$0e,$0b,$cc,$2c,$f0,$71,$74,$f4
;  db $f6,$d7,$4e,$cb,$73,$2b,$0d,$ce,$ce,$29,$0c,$aa,$f4,$8f,$0a,$cb
;  db $35,$2d,$08,$cc,$37,$f5,$4f,$89,$f2,$8e,$c8,$0c,$33,$2d,$0c,$2d
;  db $0c,$f7,$4f,$cf,$77,$77,$ca,$8e,$cc,$f5,$2f,$06,$d2,$05,$51,$ee
;  db $41,$e9,$f9,$5e,$fe,$91,$ce,$8e,$6e,$19,$81,$6e,$49,$a1,$b6,$00
;  db $d7,$15,$24,$cc

org $00a65a : jsl init_decomp
org $088000 : incbin build/ctable.bin
org $408000 : fill $100000
org $418000 : incbin build/font16.bin

org $428000
  incbin build/c000.bin
  incbin build/c002.bin
  incbin build/c004.bin
  incbin build/c244.bin
  incbin build/c245.bin
  incbin build/c464.bin

org $258000
intro1:
  incbin build/intro1.bin

org $278000
intro2:
  incbin build/intro2.bin

;write signature in ROM footer
;this must exist or intro will freeze
org $5ffb20 : db $62
org $5ffc86 : db $79
org $5ffe12 : db $75
org $5fff67 : db $75
org $5ffffc : db $62,$79,$75,$75

org $408000

intro()
;init_snes
  sei : stz $4200
  clc : xce
  rep #$10 : ldx #$01ff : txs
  sep #$30

;  lda $5ffb20 : cmp #$62 : beq + : stp
;+ lda $5ffc86 : cmp #$79 : beq + : stp
;+ lda $5ffe12 : cmp #$75 : beq + : stp
;+ lda $5fff67 : cmp #$75 : beq + : stp
;+ lda $5ffffc : cmp #$62 : beq + : stp
;+ lda $5ffffd : cmp #$79 : beq + : stp
;+ lda $5ffffe : cmp #$75 : beq + : stp
;+ lda $5fffff : cmp #$75 : beq + : stp
;+

  lda #$00 : pha : plb
  pea $0000 : pld

  lda #$8f : sta $2100
  ldx #$01
- stz $2100,x : inx : cpx #$0d : bcc -
- stz $2100,x : stz $2100,x : inx : cpx #$15 : bcc -
- stz $2100,x : inx : cpx #$1b : bcc -
- stz $2100,x : stz $2100,x : inx : cpx #$21 : bcc -
- stz $2100,x : inx : cpx #$34 : bcc -
  stz $4200
  ldx #$02
- stz $4200,x : inx : cpx #$0e : bcc -

  lda #$80 : sta $2115 ;increment on $2119 write
  lda #$01 : sta $420d ;enable fastrom
  rep #$10

;clear VRAM
  lda #$00   : sta $7fffff
  lda #$09   : sta $4300
  lda #$18   : sta $4301
  lda #$ff   : sta $4302
  lda #$ff   : sta $4303
  lda #$7f   : sta $4304
  ldx #$0000 : stx $4305
  lda #$01   : sta $420b

;clear OAM
  stz $2102 : stz $2103
  ldx #$0080
- stz $2104
  lda #$e0 : sta $2104
  stz $2104
  stz $2104
  dex : bne -
  ldx #$0020
- stz $2104
  dex : bne -

;clear CGRAM
  stz $2121
  ldx #$0200
- stz $2122 : dex : bne -
  stz $2121

;intro
  lda #$03 : sta $2105 ;mode3
  lda #$78 : sta $2107 ;bg1 tilemap  [$f000]
  lda #$00 : sta $210b ;bg1 tiledata [$0000]
  lda #$01 : sta $212c ;enable bg1
  lda #$ff : sta $210e ;bg1vofs
  lda #$ff : sta $210e ;bg1vofs

  sep #$30

;intro1
  lda #$8f : sta $2100
  lda #$00 : sta $20
  lda #$80 : sta $21
  lda #$25 : sta $22

  jsr write_palette
  jsr write_tiledata
  jsr write_tilemap

  rep #$10
  jsr half_wait
  jsr fadein
  jsr wait
  jsr fadeout

;intro2
  lda #$8f : sta $2100
  lda #$00 : sta $20
  lda #$80 : sta $21
  lda #$27 : sta $22

  jsr write_palette
  jsr write_tiledata
  jsr write_tilemap

  rep #$10
  jsr half_wait
  jsr fadein
  jsr wait
  jsr wait
  jsr fadeout

;end
  lda #$0f : sta $2100
  jsr clear_palette
  cld : sei
  jml $008024
}

write_palette() {
  php : rep #$10
  ldy #$0000
  tya : sta $2121

- jsr decrypt_read : sta $2122 : iny
  jsr decrypt_read : sta $2122 : iny
  cpy #$0200 : bcc -

  plp : rts
}

write_tiledata() {
  stz $2116 : stz $2117

  php : rep #$10
  ldx #$e000

- jsr decrypt_read : sta $2118
  jsr decrypt_read : sta $2119
  dex #2 : bne -

  plp : rts
}

write_tilemap() {
  lda #$00 : sta $2116
  lda #$78 : sta $2117
  stz $24 : stz $25
- {
    lda $24 : sta $2118
    lda $25 : sta $2119
    inc $24 : bne +
    inc $25
  + lda $25 : cmp #$03 : bne +
    lda $24 : cmp #$80 : bne +
    rts
  + bra -
  }
}

clear_palette() {
  lda #$00 : sta $2121
- sta $2122 : inx
  sta $2122 : inx
  bne -
- sta $2122 : inx
  sta $2122 : inx
  bne -
  rts
}

fadein() {
  php : sep #$20
  ldy #$0000
--{
    ldx #$0004
    sty $2100
    tya : asl #4
    eor #$f0 : ora #$01
    sta $2106
  .loop
  - lda $4212 : bmi -
  - lda $4212 : bpl -
    dex : bne .loop
    iny : cpy #$0010 : bcc --
  }
  plp : rts
}

fadeout() {
  php : sep #$20
  ldy #$000f
--{
    ldx #$0004
    sty $2100
    tya : asl #4
    eor #$f0 : ora #$01
    sta $2106
  .loop
  - lda $4212 : bmi -
  - lda $4212 : bpl -
    dex : bne .loop
    dey : bpl --
  }
  plp : rts
}

wait() {
  ldx #$0080
.loop
- lda $4212 : bmi -
- lda $4212 : bpl -
  dex : bne .loop
  rts
}

half_wait() {
  ldx #$0020
.loop
- lda $4212 : bmi -
- lda $4212 : bpl -
  dex : bne .loop
  rts
}

decrypt_read() {
  lda [$20]

  ;php : rep #$10 : phx
  ;ldx $20
  ;eor $400000,x
  ;plx : plp
  ;sec : sbc $20

  inc $20 : bne +
  inc $21 : bne +
  pha : lda #$80 : sta $21 : pla
  inc $22

+ rts
}

!vram_pos = $7ffffa ;must match font8.asm:!vram_pos

;*****
;font8.asm needs to know where the 8x8 font is stored in VRAM
;hook the decompression routine, if decompressing the 8x8 font,
;the VRAM location to write to is stored in $29, so copy this
;to a temporary variable that will be used by 8x8 vwf routine.
;*****
init_decomp() {
  cpx #$0000 : bne +
  rep #$20
  lda $29 : and #$007f : xba : sta !vram_pos
  sep #$20
+ lda $088000,x : rtl
}

savepc build/dl.xpc
