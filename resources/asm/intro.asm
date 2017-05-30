;
; Der Langrisser Intro
;
; This files contains Super Nintendo code for displaying splash intros before
; the game begins. It also loads in most of the new graphics and passes their
; pointers to the rest of the new asm files.
;
; Version:   1.0
; Author:    byuu
; Copyright: (c) 2006, 2016 DL Team
; Website:   https://github.com/sobodash/derlangrisser/
; License:   BSD License <http://opensource.org/licenses/bsd-license.php>
;

lorom

org $008020 : jml intro


; ----------------------------------------------------------------------------
; Update internal header information

org $ffc0 : db 'Der Langrisser       '
org $ffd7 : db $0c ; 24Mbit ROM
org $ffd9 : db $01 ; North American release


; ----------------------------------------------------------------------------
; Patch in new graphics

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

org $408000


; ----------------------------------------------------------------------------
; Display new splash introduction

intro()
  ; Initialize Super Nintendo
  sei : stz $4200
  clc : xce
  rep #$10 : ldx #$01ff : txs
  sep #$30

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

  lda #$80 : sta $2115 ; Increment on $2119 write
  lda #$01 : sta $420d ; Enable fastrom
  rep #$10

  ; Clear VRAM
  lda #$00   : sta $7fffff
  lda #$09   : sta $4300
  lda #$18   : sta $4301
  lda #$ff   : sta $4302
  lda #$ff   : sta $4303
  lda #$7f   : sta $4304
  ldx #$0000 : stx $4305
  lda #$01   : sta $420b

  ; Clear OAM
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

  ; Clear CGRAM
  stz $2121
  ldx #$0200
- stz $2122 : dex : bne -
  stz $2121

  ; Begin Intro
  lda #$03 : sta $2105 ; Mode 3
  lda #$78 : sta $2107 ; BG1 Tilemap  [$f000]
  lda #$00 : sta $210b ; BG1 Tiledata [$0000]
  lda #$01 : sta $212c ; Enable BG1
  lda #$ff : sta $210e ; BG1vofs
  lda #$ff : sta $210e ; BG1vofs

  sep #$30

  ; Intro 1
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

  ; Intro 2
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
  jsr half_wait
  jsr wait
  jsr fadeout

  ; End
  lda #$0f : sta $2100
  jsr clear_palette
  cld : sei
  jml $008024
}

write_palette() {
  php : rep #$10
  ldy #$0000
  tya : sta $2121

- jsr img_read : sta $2122 : iny
  jsr img_read : sta $2122 : iny
  cpy #$0200 : bcc -

  plp : rts
}

write_tiledata() {
  stz $2116 : stz $2117

  php : rep #$10
  ldx #$e000

- jsr img_read : sta $2118
  jsr img_read : sta $2119
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
    ; sta $2106 ; Use mosaic effect
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
    ; sta $2106 ; Use mosaic effect
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

img_read() {
  lda [$20]
  inc $20 : bne +
  inc $21 : bne +
  pha : lda #$80 : sta $21 : pla
  inc $22

+ rts
}

!vram_pos = $7ffffa ; Must match in !vram_pos font8.asm


; font8.asm needs to know where the 8x8 font is stored in VRAM. Hook the
; decompression routine. If decompressing the 8x8 font, the VRAM location
; to write to is stored in $29, so copy this to a temporary variable that
; will be used by 8x8 proportional font routine.

init_decomp() {
  cpx #$0000 : bne +
  rep #$20
  lda $29 : and #$007f : xba : sta !vram_pos
  sep #$20
+ lda $088000,x : rtl
}

savepc build/dl.xpc
