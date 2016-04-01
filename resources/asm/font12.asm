lorom

org $00ad46 : nop : nop
org $00b1e0 : db $80    ;bra $b20b
org $00b336 : db $80    ;bra $b33b
org $00b3b0 : db $80
org $00b5fc : nop : nop
org $00b71f : db $80    ;bra $b724
org $00b798 : db $80    ;bra $b7b8
org $048f2a : db $80    ;bra $8f2f

;disable normal text mode [force fast text mode]
;-vwf does not support normal text mode because
;-it renders the entire line at once rather than
;-tile by tile, as the normal mode draws to screen
org $00adcd : nop : nop ;disable prologue normal text mode
org $00add2 : db $80    ;disable battle normal text mode

;center sc84 battle window text, this is actually
;the exact vram location to write a tilemap based in
;rom... 0660 = left most, skipping 4 tiles on each side
;(256 - 32 = 192 available pixels)
org $06de37
  lda #$64 : sta $7f7005
  lda #$06 : sta $7f7006

;vwf implementation
org $00b320 : jsl loop_vwf : bra $0a
org $00b709 : jsl loop_vwf : bra $0a

;tilemap wraps within 256 tiles due to 8-bit addition
;when incrementing tilemap line. tiledata is rendered
;correctly, however. modify tilemap increment routine
;to use 16-bit math so that multiple pages of tiles can
;be used ...
org $048f5d : jsl tilemap_inc_page : nop #3

org $5f8000
fontvwf0: incbin build/fontv0.bin
fontvwf1: incbin build/fontv1.bin
fontvwf2: incbin build/fontv2.bin
fontvwf3: incbin build/fontv3.bin
fontvwf4: incbin build/fontv4.bin
fontvwf5: incbin build/fontv5.bin

loadpc build/dl.xpc

;tags
;-80=hook loop
;-81=pop location from 011c-011e
;-82=function 03

!bit_pos   = $7ffe00 ;bit position
!chr_len   = $7ffe02 ;character length
!ram_pos   = $7ffe04 ;ram position
!y_pos     = $7ffe06 ;y position
!clear     = $7ffe08 ;clear vwf data/ram
!dec_pos   = $7ffe0a ;decrement text position
!bits_left = $7ffe0c ;bits left on current tile
!hook_loop = $7ffe0e ;hook loop
!tile_pos  = $7ffe10 ;tile position
!num0      = $7ffe16
!num1      = $7ffe17
!num2      = $7ffe18
!num3      = $7ffe19
!num4      = $7ffe1a
!num5      = $7ffe1b
!cnum      =  #$fe16
!hl_ptrw   = $7ffe1c
!hl_ptrb   = $7ffe1e
!hl_cc     = $7ffe1f
!chl_cc    =  #$fe1f
!font_ptr  = $7fff20

lookup_table:
  db $01, $02, $03, $04, $05, $06, $07, $08, $09, $0a, $0b, $0c, $0d, $0e, $0f, $10
  db $11, $12, $13, $14, $15, $16, $17, $18, $19, $1a, $1b, $1c, $1d, $1e, $1f, $20
  db $21, $22, $23, $24, $25, $26, $27, $28, $29, $2a, $2b, $2c, $2d, $00, $00, $00
  db $00, $00, $00, $00, $00, $00, $00, $00, $00, $00, $00, $00, $00, $00, $00, $00
  db $00, $00, $00, $00, $00, $00, $3f, $40, $41, $42, $43, $2e, $2f, $30, $31, $32
  db $33, $34, $00, $00, $00, $00, $00, $35, $36, $37, $38, $39, $3a, $3b, $3c, $3d
  db $3e, $44, $45, $46, $47, $48, $49, $4a, $4b, $4c, $4d, $4e, $4f

;*****
;lda $17
;clc : adc #$10
;sta $17
;*****
tilemap_inc_page() {
  rep #$20
  lda $17
  clc : adc #$0010
  sta $17
  sep #$20
  rtl
}

load_5d() {
  php : sep #$20
  lda #$00 : xba ;clear high byte so Z flag set is always based on low byte only

  lda $5f
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $5e  : bpl +

;decryption
;  rep #$10 : phx
;  ldx $5d
;  lda [$5d]
;  eor $400000,x
;  plx
;  sec : sbc $5d
;  plp : pha : pla : rts ;set Z flag and return

;no decryption
+ lda [$5d]
  plp : pha : pla : rts ;set Z flag and return
}

inc_5d() {
  php : rep #$20 : pha
  inc $5d : bne +
  lda #$8000
  sta $5d
  inc $5f
+ pla : plp : rts
}

dec_5d() {
  php : rep #$20 : pha
  dec $5d
  lda $5d
  cmp #$7fff : bne +
  lda #$ffff
  sta $5d
  dec $5f
+ pla : plp : rts
}

clear_vwf_data() {
  php
  lda $45   : and #$00ff : cmp #$0002 : beq .loadfont1
  lda $1f4a : and #$00ff : cmp #$000c : beq .loadfont2
.loadfont0
  {
    lda #fontvwf0 : sta !font_ptr
    bra .endloadfont
  }
.loadfont1
  {
    lda #fontvwf1 : sta !font_ptr
    bra .endloadfont
  }
.loadfont2
  {
    lda #fontvwf2 : sta !font_ptr
  }
.endloadfont
  lda !font_ptr : clc : adc #$0080 : sta !font_ptr ;skip length table
  lda #$0000 : tax
  sta !bit_pos : sta !chr_len : sta !clear : sta !dec_pos
  sta !bits_left : sta !hook_loop : sta !tile_pos
- {
    sta $7e3400,x : sta $7e3800,x
    inx #2
    cpx #$0400 : bcc -
  }
  plp : rts
}

set_font0() { pha : lda #fontvwf0 : clc : adc #$0080 : sta !font_ptr : pla : rts }
set_font1() { pha : lda #fontvwf1 : clc : adc #$0080 : sta !font_ptr : pla : rts }
set_font2() { pha : lda #fontvwf2 : clc : adc #$0080 : sta !font_ptr : pla : rts }
set_font3() { pha : lda #fontvwf3 : clc : adc #$0080 : sta !font_ptr : pla : rts }
set_font4() { pha : lda #fontvwf4 : clc : adc #$0080 : sta !font_ptr : pla : rts }
set_font5() { pha : lda #fontvwf5 : clc : adc #$0080 : sta !font_ptr : pla : rts }

text_ret() {
  lda !hl_ptrw : sta $5d : sep #$20
  lda !hl_ptrb : sta $5f : rep #$20
  lda #$0000  : sta !dec_pos

  jsr load_5d : rts
}

load_number() {
  lda #$0000 : sta !num0 : sta !num2
;81 used to return $5d to next byte
  lda #$8100 : sta !num4
  lda $2b : inc $2b ;$2b=pointer to number
  and #$00ff : asl : tay
  lda $07ce,y
.l5
  cmp.w #10000 : bcc .l4
  sec : sbc #$2710 : pha : lda !num0 : inc : sta !num0 : pla : bra .l5
.l4
  cmp.w #1000 : bcc .l3
  sec : sbc #$03e8 : pha : lda !num1 : inc : sta !num1 : pla : bra .l4
.l3
  cmp.w #100 : bcc .l2
  sec : sbc #$0064 : pha : lda !num2 : inc : sta !num2 : pla : bra .l3
.l2
  cmp.w #10 : bcc .l1
  sec : sbc #$000a : pha : lda !num3 : inc : sta !num3 : pla : bra .l2
.l1
  sep #$20 : sta !num4 : rep #$20
  lda $5d   : sta !hl_ptrw
  lda !cnum : sta $5d : sep #$20
  lda $5f   : sta !hl_ptrb
  lda #$7f  : sta $5f
  jsr load_5d : bne .endt : jsr inc_5d
  jsr load_5d : bne .endt : jsr inc_5d
  jsr load_5d : bne .endt : jsr inc_5d
  jsr load_5d : bne .endt : jsr inc_5d
.endt
  lda !num0 : clc : adc #$61 : sta !num0
  lda !num1 : clc : adc #$61 : sta !num1
  lda !num2 : clc : adc #$61 : sta !num2
  lda !num3 : clc : adc #$61 : sta !num3
  lda !num4 : clc : adc #$61 : sta !num4

  rep #$20 : jsr load_5d : jsr inc_5d
  rts
}

loop_vwf() {
  php : rep #$30 : pha : phx : phy
  lda $fa
  cmp #$3400 : bne + ;if we are at $3400 then we are at the start of a new line
  jsr clear_vwf_data

  lda !clear : beq +
  jsr clear_vwf_data

+ lda !hook_loop : beq .charloop
  jsr dec_5d
  lda $fa : and #$01f0           ;get current tile position
  lsr #4
  cmp #$0017 : bcs .end_ret_hook ;if >  192 pixels, stop
  cmp !tile_pos : bcc .ret_hook  ;if >= string length, stop

.end_ret_hook
  lda #$0000 : sta !hook_loop
  lda !hl_ptrw : sta $5d : sep #$20
  lda !hl_ptrb : sta $5f : rep #$20

.ret_hook
  ply : plx : pla : plp : rtl

  .charloop {
    lda !tile_pos : asl #4 : sta !ram_pos
    lda #$0000 : sta !y_pos : tax : tay
    jsr dec_5d : jsr load_5d : jsr inc_5d
    cmp #$0081 : bne +
    {
      jsr text_ret
      cmp #$000a : bcc + ;control codes immediately after {03} break [$5d] load tricks ...
      jsr inc_5d         ;however, text needs to increment normally to avoid being doubled
    }
+   cmp #$0082 : bne + : jsr load_number
+   cmp #$0090 : bne + : jsr set_font0 : jsr inc_5d : jmp .charloop
+   cmp #$0091 : bne + : jsr set_font1 : jsr inc_5d : jmp .charloop
+   cmp #$0092 : bne + : jsr set_font2 : jsr inc_5d : jmp .charloop
+   cmp #$0093 : bne + : jsr set_font3 : jsr inc_5d : jmp .charloop
+   cmp #$0094 : bne + : jsr set_font4 : jsr inc_5d : jmp .charloop
+   cmp #$0095 : bne + : jsr set_font5 : jsr inc_5d : jmp .charloop
+   cmp #$009f : bne +
    {
      jsr load_5d
      sta !chr_len
      jsr inc_5d
      jmp .end_line
    }
+   cmp #$0001 : bne + : jsr inc_5d : jmp .charloop
+   cmp #$0004 : bne + : jsr inc_5d : jmp .charloop
+   cmp #$0005 : bne + : jsr inc_5d : jmp .charloop
+   cmp #$0009 : bne + : jsr inc_5d : jmp .charloop
+   cmp #$000a : bcs + : jmp .end
+   sec : sbc #$000a : tax
    lda lookup_table,x : and #$00ff
    pha
      clc : adc !font_ptr : sec : sbc #$0080 : tax
      lda $5f0000,x : and #$00ff : sta !chr_len
    pla
    asl #6 : tay
    lda #$0000 : sta !dec_pos
    lda !ram_pos : tax
   .wl {
      phx : tya : clc : adc !font_ptr : tax
      lda $5f0000,x
      pha : lda !bit_pos : tax : pla
      and #$00ff : xba
      cpx #$0000
-     beq + : lsr : dex : bra -
+     plx
      sep #$20
        xba : ora $7e3400,x : sta $7e3400,x
        xba : sta $7e3410,x
      rep #$20

      phx : tya : clc : adc !font_ptr : tax
      lda $5f0001,x
      pha : lda !bit_pos : tax : pla
      and #$00ff : xba
      cpx #$0000
-     beq + : lsr : dex : bra -
+     plx
      sep #$20
        xba : ora $7e3401,x : sta $7e3401,x
        xba : sta $7e3411,x
      rep #$20

      lda !chr_len : cmp #$0008 : bcc .lt8

      phx : tya : clc : adc !font_ptr : tax
      lda $5f0020,x
      pha : lda !bit_pos : tax : pla
      and #$00ff : xba
      cpx #$0000
-     beq + : lsr : dex : bra -
+     plx
      sep #$20
        xba : ora $7e3410,x : sta $7e3410,x
        xba : sta $7e3420,x
      rep #$20

      phx : tya : clc : adc !font_ptr : tax
      lda $5f0021,x
      pha : lda !bit_pos : tax : pla
      and #$00ff : xba
      cpx #$0000
-     beq + : lsr : dex : bra -
+     plx
      sep #$20
        xba : ora $7e3411,x : sta $7e3411,x
        xba : sta $7e3421,x
      rep #$20

    .lt8
      inx #2 : iny #2
      lda !y_pos : inc : sta !y_pos
      cmp #$0008 : beq .next_line
      cmp #$0010 : bcs .end_line
      jmp .wl
    }
  .next_line {
      txa : clc : adc #$03f0 : tax : jmp .wl
    }
  .end_line {        ;update pixel position
      lda !tile_pos : asl : asl : asl
      clc : adc !bit_pos
      clc : adc !chr_len
      pha : and #$0007 : sta !bit_pos : pla
      lsr : lsr : lsr
      cmp #$0018 : bcc + ;if > 192 pixels, wrap tile_pos back below
      lda #$0017         ;192 pixels, to avoid writing too far...
+     sta !tile_pos
      jsr inc_5d
      lda #$0001 : sta !dec_pos
      jmp .charloop
    }
  }
.end
  rep #$30
  lda !dec_pos : beq + : jsr dec_5d

+ jsr load_5d
  cmp #$0000 : beq +
  cmp #$0006 : beq +
  cmp #$0008 : beq +
  bra ++

+ lda #$0001  : sta !hook_loop
  lda $5d     : sta !hl_ptrw
  lda !chl_cc : sta $5d
  sep #$20
  lda $5f     : sta !hl_ptrb
  lda #$7f    : sta $5f
  lda #$80    : sta !hl_cc
  rep #$20

++
  ply : plx : pla : plp : rtl
}

savepc build/dl.xpc
