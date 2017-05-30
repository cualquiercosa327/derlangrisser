;
; Der Langrisser 8x8 Proportional Font
;
; This files contains Super Nintendo code for hooking an 8x8 proportional font
; renderer to use in place of the game's normal 8x8 monospaced font.
;
; Version:   1.0
; Author:    byuu
; Copyright: (c) 2006, 2016 DL Team
; Website:   https://github.com/sobodash/derlangrisser/
; License:   BSD License <http://opensource.org/licenses/bsd-license.php>
;

lorom


; ----------------------------------------------------------------------------
; Set up our variables

!bit_pos      = $7fffe0 ; Bit position
!chr_len      = $7fffe2 ; Length of current char
!map_pos      = $7fffe4 ; Index into RAM tilemap data
!tile_pos     = $7fffe6 ; Index into VRAM tilemap
!temp_pos     = $7fffe8 ; Used for right alignment of class names in battles
!chr_val      = $7fffea ; Current char value
!stile_pos    = $7fffec
!vwf_flag     = $7fffee
!page_num     = $7ffff0 ; 0 - 23
!line_num     = $7ffff2 ; 0 - 2
!unit_num     = $7ffff4 ; page_num * 3 + line_num
!unit_count   = $7ffff6 ; Total number of units available
!class_align  = $7ffff8 ; 0 = left, 1 = right
!vram_pos     = $7ffffa ; Must match !vram_pos in intro.asm!


; ----------------------------------------------------------------------------
; Hook all program locations that should use the new 8x8 proportional font

; Battle class name positions, left/right
org $04ef8e : lda #$69
org $04ef97 : lda #$88

; Expand battle class names from 8 to 16 letters
org $04efa8 : lda #$0010
org $04efba : cpx #$20
org $04efc8 : lda #$0010
org $04efda : cpx #$20
org $04efc1 : nop : nop : nop

; Disable the game's default right alignment of second class name in battles
org $04ef94 : nop : nop : nop

; Cache battle class name alignment (left/right)
org $04ee29 : jml save_class_alignment : nop #2

; Main proportional font routine hook
org $02bc3e : jsl font8vwf0 : jmp $bcae
org $02bcaf : bra $02 ; Disable an excess write to the tilemap

; 3 class names font hooks
org $03b605 : jsl init_tilemap
org $0499f4 : jsl font8vwfhook

; Prevent the last 2 letters in class names from being overwritten
org $03b60e : nop : nop
org $0491bd : nop : nop
org $0491c3 : nop : nop

; Purchase class name hook
org $03afce : jsl font8vwf2

; Commander screen class name hook
org $03a657 : jsl font8vwf3

; Reposition text on commander screen
org $03a541 : lda #$0a03 ; x #units/max #units
org $03a631 : lda #$0901 ; class_name
org $03a6fd : lda #$0a09 ; xxxxP

; Spoils of War screen font hook
org $01f980 : jsl font8vwf4 : jmp $f990

loadpc build/dl.xpc


;*****
; The following two routines are extremely important!
; If an irq fires when using DMA or mul/div registers, they will be thrashed
; before the irq returns. Therefore, it is imperative that any function that
; uses registers $4300-$437f or $4202-$4217 first call disable_irq(),
; and then when finished, call enable_irq()

disable_irq() {
  php : sep #$20 : pha : lda #$01 : sta $004200 : pla : plp : rts
}

; $d3 = internal game variable, holds status of $004200
enable_irq() {
  php : sep #$20 : pha : lda $d3 : sta $004200 : pla : plp : rts
}


fontdata:
  incbin build/font8vwf.bin

fontlen:
  db $04,$06,$06,$06,$06,$06,$06,$06,$06,$06,$05,$06,$06,$06,$06,$06
  db $06,$06,$06,$06,$06,$06,$06,$06,$06,$06,$06,$06,$06,$06,$06,$06
  db $06,$06,$06,$04,$04,$05,$04,$06,$06,$06,$06,$06,$06,$06,$05,$06
  db $06,$06,$06,$06,$06,$08,$04

fonttable_raw:
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00

fonttable:
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $00,$01,$02,$03,$04,$05,$06,$07,$08,$09,$0a,$0b,$0c,$0d,$0e,$0f
  db $10,$11,$12,$13,$14,$15,$16,$17,$18,$19,$1a,$1b,$1c,$1d,$1e,$1f
  db $20,$21,$22,$23,$24,$25,$26,$27,$28,$29,$2a,$2b,$2c,$2d,$35,$00
  db $00,$00,$2e,$2f,$30,$31,$32,$33,$34,$00,$00,$00,$00,$00,$00,$00
  db $00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00

convert_char() {
  php : sep #$20
  cmp #$a7 : bcs +
  lda #$00 : rep #$20 : plp : rts
+ cmp #$b1 : bcc +
  sec : sbc #$b0 : rep #$20 : plp : rts
+ sec : sbc #$79
  plp : rts
}

initvwfsh() {
  lda #$0000 : sta !bit_pos : sta !chr_len
  lda $17 : and #$00ff : clc : adc $08 : sta !map_pos
  lda #$0000
  sta $7ff000 : sta $7ff002 : sta $7ff004 : sta $7ff006
  sta $7ff008 : sta $7ff00a : sta $7ff00c : sta $7ff00e
  rts
}

initvwf0() {
  php : rep #$30 : pha
  lda #$0001 : sta !vwf_flag
  lda #$0041 : sta !tile_pos : sta !stile_pos
  jsr initvwfsh
  pla : plp : rts
}

initvwf1() {
  lda #$0000 : sta !vwf_flag
  dec $05 : sep #$20 : lda #$35 : sta $03,s : rep #$20 : rts
}

;*****
; Initialize proportional font for battle class names.
; Perform left/right alignment of class names.

initvwf2() {
  php : rep #$30 : pha
  lda #$0000 : sta !vwf_flag
  lda !class_align : bne .right
.left {
    lda #$0041 : sta !tile_pos : sta !stile_pos
    jsr initvwfsh
    pla : plp : rts
  }
.right {
    dec $05
    pha : lda #$0000 : sta !temp_pos : pla
    sep #$20
    ldy #$0000
-   { lda [$05],y
      cmp #$ff : beq +
      jsr convert_char
      tax : lda fontlen,x
      clc : adc !temp_pos : sta !temp_pos
      iny
      bra -
    }
+   rep #$20
    inc $05
    jsr initvwfsh
    lda #$0051 : sta !tile_pos : sta !stile_pos
    lda #$0077 : sec : sbc !temp_pos
    pha : and #$0007 : sta !bit_pos : pla
    lsr #3 : asl
    clc : adc !map_pos : sta !map_pos
    pla : plp : rts
  }
}

;*****
; Der Langrisser overwrites class alignment variable $19 (00=left, 01=right)
; and uses it for temporary storage. This value is needed to properly align
; battle text, so cache the value before it is overwritten.
;
; 04ee29 lda $19
; 04ee2b pha
; 04ee2c lda $05
; 04ee2e pha

save_class_alignment() {
  lda $19 : pha
  sta !class_align
  lda $05 : pha
  jml $04ee2f
}

vwf_ret() {
  pla : plp
  ldy $17
  sta [$08],y
  iny
  lda #$20
  sta [$08],y
  rtl
}

endvwf() { plp : pla : pla : pla : jml $02bcb5 }

font8vwf0() {
  php : rep #$30
  lda #$0002 : clc : adc $08 : sta $08
  lda [$05] : inc $05 : and #$00ff
  cmp #$00ff : bne $03 : jmp endvwf
  jsr convert_char
  pha
  lda $17
  and #$00ff
  cmp #$0010 : beq .ts0
  cmp #$0040 : beq .ts1
  bra vwf_ret

.ts0 {
    lda $08
    cmp #$0202 : bcc vwf_ret
    cmp #$0282 : bcs vwf_ret
    cmp #$0202 : bne $03 : jsr initvwf2 : bra .endts
  }

.ts1 {
    lda $08
    cmp #$01d8 : bcc vwf_ret
    cmp #$0258 : bcs vwf_ret
    cmp #$01d8 : bne $05 : jsr initvwf1 : bra .endts
    cmp #$0218 : bne $03 : jsr initvwf0
  }

.endts
  pla : pha : phx
  and #$00ff : pha : tax
  lda fontlen,x : and #$00ff : sta !chr_len
  pla : asl : asl : asl : asl : tay
  lda !tile_pos : sec : sbc !stile_pos : asl #4 : tax
  lda #$0000 : sta !temp_pos
  phk : plb

.mainloop {
    phx : lda !bit_pos : tax
    lda fontdata,y
    and #$00ff : xba
    cpx #$0000 : beq $04 : lsr : dex : bra $fa
    plx
    sep #$20 : xba
    ora $7ff000,x : sta $7ff000,x
    xba : sta $7ff010,x
    rep #$20
    iny

    phx : lda !bit_pos : tax
    lda fontdata,y
    and #$00ff : xba
    cpx #$0000 : beq $04 : lsr : dex : bra $fa
    plx
    sep #$20 : xba
    ora $7ff001,x : sta $7ff001,x
    xba : sta $7ff011,x
    rep #$20
    iny

    inx : inx
    lda !temp_pos : inc : sta !temp_pos
    cmp #$0008 :  bcc .mainloop
  }
  sep #$20 : lda #$00 : pha : plb : rep #$20
  lda !tile_pos : asl #3
  clc : adc !chr_len
  clc : adc !bit_pos
  pha : and #$0007 : sta !bit_pos : pla
  lsr : lsr : lsr
  sta !tile_pos
  rep #$30 : plx : pla : plp
  lda [$05] : cmp #$ff : bcs + : jmp font8vwf0

+ rep #$10
  lda !vwf_flag : bne $06
  jsr write_tiles0
  jsr store_tiles
  sep #$10
  rtl
}

;*****
; Initialize the tilemap to point to proportional font tiles for the
; 8x8 unit selection window.
;
; r [$05] = character name
; w [$02] = tilemap output (start = $7f7560)
; w $7ffa00+ = class names from script, used by vwf routine

init_tilemap() {
  jsr disable_irq
  php : rep #$30 : pha : phx : phy

;determine which line number current unit class name is on
;WRAM tilemap starts at $7f7560, so remove this first
;each entry is #$6d bytes, so subtract from remainder until zero
  lda $02 : sec : sbc #$7560
  sta $4204
  sep #$20 : lda #$6d : sta $4206 : rep #$20
  nop #8

;$4214 = a / #$6d
;$4216 = a % #$6d

  lda $4214 : sta !unit_count
  sta $4204
  sep #$20 : lda #$03 : sta $4206 : rep #$20
  nop #8

;$4214 = line# / 3
;$4216 = line# % 3

  lda $4214 : sta !page_num
  lda $4216 : sta !line_num

;allow up to ten 8x8 tiles (8x80 range) for vwf class names
;start writing tiles to tilemap #$0110 w/priority bit set
  asl #3
  clc : adc !line_num
  clc : adc !line_num
  clc : adc #$2110

;a = #$2110 + !line_num * 10

  pha
  lda !unit_count : asl #4 : tax

;max # of units (with hacked SRAM file) is 64
;64 * 16 = $400 bytes, $7ffa00+$400=$7ffe00
;max width of class name is 15 (for 'Serpent Knight',0)
;x = !unit_count * 16

;copy class name into WRAM, to be used by font8vwf1 routine
  ldy #$0000 : sep #$20
- lda [$05],y
  cmp #$ff : beq + : jsr convert_char
+ sta $7ffa00,x
  inx : iny
  cmp #$ff : bne -

  rep #$20
  pla

;store tilemap data in WRAM, will be loaded into VRAM later ...
  ldy #$0000
- sta [$02],y : inc
  iny : iny : cpy #$0014 : bcc -

;store blank space after unit class name
  lda #$2040 : sta [$02],y

;finally, update tilemap data offset and return
  lda $02 : clc : adc #$0010 : sta $02
  rep #$30 : ply : plx : pla : plp
  jsr enable_irq
  rtl
}

;*****
;clear WRAM used by vwf routine
;*****
clear_tiles_vwf1() {
  pha : phx
  lda #$0000 : tax
- sta $7ff000,x : sta $7ff002,x
  inx #4 : cpx #$01e0 : bcc -
  plx : pla : rts
}

;*****
;this hooks the text routine, and checks where the tilemap
;data is being read from. this can be compared to certain
;values to determine if it is from 8x8 text that needs to
;be rendered to the screen ...
;*****
;r [$02] = tilemap data
;*****
font8vwfhook() {
  jsr disable_irq
  php : rep #$30 : pha : phx : phy

;verify the current tilemap read location is within
;valid range for unit class name text ...
  lda $02
  cmp #$7560 : bcc .end
  cmp #$9800 : bcs .end

;verify exact position by dividing by #$6d, or the
;length of exactly one class name entry ...
  sec : sbc #$7560 : sta $4204
  sep #$20 : lda #$6d : sta $4206 : rep #$20
  nop #8

;$4214 = a / #$6d
;$4216 = a % #$6d

  lda $4216 : bne .end
  lda $4214 : sta !unit_num

;if class entry % 3 == 0, then this is the first entry
;of the current window being drawn ... need to clear out
;WRAM before drawing new tiledata. otherwise, the tiledata
;is already clear, just draw the new tiledata ...
  lda $4214 : sta $4204 ;$4214 = a / #$6d
  sep #$20 : lda #$03 : sta $4206 : rep #$20
  nop #8

;$4214 = line# / 3
;$4216 = line# % 3

  lda $4214 : sta !page_num
  lda $4216 : sta !line_num : bne +
  jsr clear_tiles_vwf1
+ jsr font8vwf1

.end
  rep #$30 : ply : plx : pla : plp
  lda [$02] : sta $17

  jsr enable_irq
  rtl
}

font8vwf1() {
  lda #$0000 : sta !bit_pos : sta !stile_pos
  lda !unit_num : asl #4 : tax

.mainloop {
    lda $7ffa00,x : and #$00ff : inx
    cmp #$00ff : bne + : jmp .endmainloop
  + phx
    pha
    tax
    lda fontlen,x : and #$00ff
    sta !chr_len
    pla
    asl #4 : tay
    lda !line_num
    asl : sta !temp_pos
    asl #2 : clc : adc !temp_pos
    clc : adc !stile_pos
    asl #4 : tax
    phb : sep #$20 : lda #$40 : pha : plb : rep #$20
    lda #$0000 : sta !temp_pos
   .write_char {
      lda fontdata,y : and #$00ff : xba
      phx
      pha : lda !bit_pos : tax : pla
      cpx #$0000
    - beq + : lsr : dex : bra -
    + plx
      sep #$20
      xba : ora $7ff000,x : sta $7ff000,x
      xba : sta $7ff010,x
      rep #$20
      iny

      lda fontdata,y : and #$00ff : xba
      phx
      pha : lda !bit_pos : tax : pla
      cpx #$0000
    - beq + : lsr : dex : bra -
    + plx
      sep #$20
      xba : ora $7ff001,x : sta $7ff001,x
      xba : sta $7ff011,x
      rep #$20
      iny

      inx : inx
      lda !temp_pos : inc : sta !temp_pos
      cmp #$0008 : bcc .write_char
    }
    plb
    plx
    lda !stile_pos : asl #3
    clc : adc !bit_pos
    clc : adc !chr_len
    pha : and #$0007 : sta !bit_pos : pla
    lsr : lsr : lsr : sta !stile_pos
    jmp .mainloop
  }
.endmainloop

;draw tiles if this is the last unit from all windows
  lda !unit_num : cmp !unit_count : bne +
  jsr write_tiles1 : rts

;draw tiles if this is the last unit in this window
+ lda !line_num : cmp #$0002 : bne +
  jsr write_tiles1

+ rts
}

;*****
;8x8 vwf font routine for the unit purchase window
;the unit selection window is *always* visible while
;this window is open. this window pops open on top
;of the selection window. as such, there already
;exists vwf tiledata in VRAM for the unit purchase
;class name. all that is needed is to grab the
;currently active line number, and then mirror the
;tiledata used by the unit selection window here.
;*****
;r $1f74 = page number
;r $1f75 = line number in current page (0-2)
;*****
font8vwf2() {
  php : rep #$30 : phy

  lda #$0000
  sep #$20
  lda $1f75 : asl #3
  clc : adc $1f75
  clc : adc $1f75
  rep #$20
  clc : adc #$2110

;a = #$2110 + (line number * 10),
;index into tilemap data for the unit class selection window

;mirror tilemap data from selection window
  sta [$02] : inc : inc $02 : inc $02
  sta [$02] : inc : inc $02 : inc $02
  sta [$02] : inc : inc $02 : inc $02
  sta [$02] : inc : inc $02 : inc $02
  sta [$02] : inc : inc $02 : inc $02
  sta [$02] : inc : inc $02 : inc $02
  sta [$02] : inc : inc $02 : inc $02
  sta [$02] : inc : inc $02 : inc $02
  sta [$02] : inc : inc $02 : inc $02
  sta [$02] : inc : inc $02 : inc $02

  rep #$30 : plx : plp : rtl
}

font8vwf3init() {
  lda #$212e
  sta [$02] : inc $02 : inc $02 : inc
  sta [$02] : inc $02 : inc $02 : inc
  sta [$02] : inc $02 : inc $02 : inc
  sta [$02] : inc $02 : inc $02 : inc
  sta [$02] : inc $02 : inc $02 : inc
  sta [$02] : inc $02 : inc $02 : inc
  sta [$02] : inc $02 : inc $02 : inc
  sta [$02] : inc $02 : inc $02 : inc
  sta [$02] : inc $02 : inc $02 : inc
  sta [$02] : inc $02 : inc $02

  lda #$2040 : ldy #$0000
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y : iny : iny
  sta [$02],y

  lda #$0000 : tax
- sta $7ff000,x : sta $7ff010,x : sta $7ff020,x : sta $7ff030,x
  sta $7ff040,x : sta $7ff050,x : sta $7ff060,x : sta $7ff070,x
  sta $7ff080,x : sta $7ff090,x : sta $7ff0a0,x : sta $7ff0b0,x
  sta $7ff0c0,x : sta $7ff0d0,x : sta $7ff0e0,x : sta $7ff0f0,x
  inx : inx : cpx #$0010 : bcc -

  rts
}

font8vwf3() {
  php
  rep #$30
  jsr font8vwf3init
  lda $00 : pha
  lda $02 : pha
  lda #fontdata : sta $00
  lda #$0040    : sta $02
  lda #$0000 : sta !bit_pos : sta !tile_pos
.mainloop {
    lda [$05] : and #$00ff : inc $05
    cmp #$00ff : bne $03 : jmp .endmainloop
    jsr convert_char
    tax : asl : asl : asl : asl : tay
    lda fontlen,x
    and #$00ff
    sta !chr_len
    lda !tile_pos : asl : asl : asl : asl : tax
    lda #$0000 : sta !stile_pos
  .charloop {
      phx : lda !bit_pos : tax
      lda [$00],y : and #$00ff : xba
      cpx #$0000 : beq $04 : lsr : dex : bra $fa
      plx
      sep #$20
      xba : ora $7ff000,x : sta $7ff000,x
      xba : sta $7ff010,x
      rep #$20
      iny

      phx : lda !bit_pos : tax
      lda [$00],y : and #$00ff : xba
      cpx #$0000 : beq $04 : lsr : dex : bra $fa
      plx
      sep #$20
      xba : ora $7ff001,x : sta $7ff001,x
      xba : sta $7ff011,x
      rep #$20
      iny

      inx : inx
      lda !stile_pos : inc : sta !stile_pos
      cmp #$0008 : bcc .charloop
    }
    lda !tile_pos : asl #3
    clc : adc !bit_pos
    clc : adc !chr_len
    pha : and #$0007 : sta !bit_pos : pla
    lsr : lsr : lsr : sta !tile_pos
    jmp .mainloop
  }
.endmainloop
  pla : sta $02
  pla : sta $00
  lda #$6970 : sta !map_pos
  sep #$20 : jsr write_tiles3
  plp : rtl
}

initf84tiles() {
  ldx $97
  lda !line_num
  asl : asl : asl
  clc : adc #$2100
  sta $7e2018,x : inx : inx : inc
  sta $7e2018,x : inx : inx : inc
  sta $7e2018,x : inx : inx : inc
  sta $7e2018,x : inx : inx : inc
  sta $7e2018,x : inx : inx : inc
  sta $7e2018,x : inx : inx : inc
  sta $7e2018,x : inx : inx : inc
  sta $7e2018,x : inx : inx
  stx $97
  lda #$0000 : tax
  sta $7ffd00,x : inx : inx : cpx #$0080 : bcc $f5
  rts
}

font8vwf4() {
  php : phb : rep #$30 : pha : phx : phy
  jsr initf84tiles : phk : plb
  lda #$0000 : sta !bit_pos : sta !tile_pos
.textloop {
    lda [$05] : inc $05 : and #$00ff
    cmp #$00ff : bne $03 : jmp .end
    jsr convert_char
    pha : tax : lda fontlen,x : and #$00ff : sta !chr_len : pla
    asl : asl : asl : asl : tay
    lda !tile_pos : asl : asl : asl : asl : tax
    lda #$0000 : sta !temp_pos
  .wr {
      phx : lda !bit_pos : tax
      lda fontdata,y : and #$00ff : xba
      cpx #$0000 : beq $04 : lsr : dex : bra $fa : plx
      sep #$20
      xba : ora $7ffd00,x : sta $7ffd00,x
      xba : sta $7ffd10,x
      rep #$20
      iny

      phx : lda !bit_pos : tax
      lda fontdata,y : and #$00ff : xba
      cpx #$0000 : beq $04 : lsr : dex : bra $fa : plx
      sep #$20
      xba : ora $7ffd01,x : sta $7ffd01,x
      xba : sta $7ffd11,x
      rep #$20
      iny

      inx : inx
      lda !temp_pos : inc : sta !temp_pos
      cmp #$0008 : bcc .wr
    }
    lda !tile_pos : asl #3
    clc : adc !bit_pos
    clc : adc !chr_len
    pha : and #$0007 : sta !bit_pos : pla
    lsr : lsr : lsr : sta !tile_pos
    jmp .textloop
  }
.end
  jsr write_tiles4
  lda !line_num : inc : sta !line_num
  rep #$30 : ply : plx : pla : plb : plp : rtl
}

store_tiles() {
  php
  rep #$30
  pha : lda !map_pos : sta $08 : pla
  ldy #$0000
  lda !stile_pos
  ora #$2000
.loop {
    and #$00ff
    cmp !tile_pos
    bcs .end
    ora #$2000
    sta [$08],y
    iny : iny : inc
    bra .loop
  }
.end
  ora #$2000
  sta [$08],y
  plp
  rts
}

write_tiles0() {
  jsr disable_irq

  sep #$20
- lda $4212 : bmi -
- lda $4212 : bpl -
  lda #$01   : sta $4300
  lda #$18   : sta $4301
  ldx #$f000 : stx $4302
  lda #$7f   : sta $4304
  rep #$20
  lda !tile_pos : sec : sbc !stile_pos
  inc : asl #4
  sta $4305
  lda !stile_pos : asl : asl : asl
  clc : adc !vram_pos
  sta $2116
  sep #$20
  lda #$01   : sta $420b

  jsr enable_irq
  rts
}

write_tiles1() {
  jsr disable_irq

  sep #$20
- lda $4212 : bmi -
- lda $4212 : bpl -
  lda #$01   : sta $4300
  lda #$18   : sta $4301
  ldx #$f000 : stx $4302
  lda #$7f   : sta $4304
  ldx #$01e0 : stx $4305
  ldx #$6880 : stx $2116
  lda #$01   : sta $420b
  rep #$20

  jsr enable_irq
  rts
}

write_tiles3() {
  jsr disable_irq

  sep #$20
- lda $4212 : bmi -
- lda $4212 : bpl -
  lda #$01   : sta $4300
  lda #$18   : sta $4301
  ldx #$f000 : stx $4302
  lda #$7f   : sta $4304
  ldx #$0100 : stx $4305
  rep #$20 : lda !map_pos : sta $2116 : sep #$20
  lda #$01   : sta $420b

  jsr enable_irq
  rts
}

write_tiles4() {
  jsr disable_irq

  sep #$20
  lda #$00 : pha : plb
- lda $4212 : bmi -
- lda $4212 : bpl -
  lda #$01   : sta $4300
  lda #$18   : sta $4301
  ldx #$fd00 : stx $4302
  lda #$7f   : sta $4304
  ldx #$0080 : stx $4305
  rep #$20
  lda !line_num : asl #6
  clc : adc #$6800 : sta $2116
  sep #$20
  lda #$01   : sta $420b
  rep #$20

  jsr enable_irq
  rts
}

savepc build/dl.xpc
