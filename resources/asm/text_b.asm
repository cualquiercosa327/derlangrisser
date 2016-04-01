lorom

;---16x16->8x16--------------------------
org $048cf8 : adc #$0002
org $00ab40 : lda #$0010
org $00ab60 : lda #$10
org $048cce : nop
org $048ce7 : nop : nop : nop
org $048cf1 : nop : nop
org $00abb4 : nop
;----------------------------------------

org $05ef7a : jsl store_map_num : jmp $efcd
org $03c1d8 : jsl store_xnum : jmp $c20f
org $0490e1 : jml store_num : nop : nop
org $0490e8 : nop : nop : nop : nop
org $049101 : nop : nop : nop : nop
org $049106 : nop : nop : nop : nop
org $0490ef : adc #$0020
org $0490f6 : lda #$0002
org $0490da : lda #$0002
org $049100 : nop
org $0492eb : jsl convert_num : nop
org $0492fa : lda #$01
org $21ed3f : ldx #$68 : sec : inx : inx : nop : nop
org $21edad : ldx #$68 : sec : inx : inx : nop : nop
org $21ed4a : adc #$3f : asl : nop
org $21edb8 : adc #$3f : asl : nop
org $21edbd : adc #$1f : asl : nop : ldx #$30
org $21ecf6 : adc #$0004
org $21ecc8 : jsl write_num0 : jmp $ece6
org $21ed69 : jsl write_num1 : jmp $ed87

;----------------------------------------
org $00ab27 : jsl load_tiles : bra $04

;original routine: 25 bytes, new routine: 22 bytes
;load 16x16 (now 8x16) text from end of rom, and
;also use words to address more tiles
org $00aba8
  rep #$20
  lda $17
  asl #5
  clc : adc #$8000
  sta $08
  sep #$20
  lda #$41
  sta $0a
  rts

org $038c59 : jml loc_test
org $048c83 : jsl set_tilepos
org $00a5ef : jml sloc_test
org $03a0a2 : jsl text_ram_load0 : jmp $a0ae
org $03a0b2 : lda #$8158
org $03a1da : jsl text_ram_load1 : jmp $a1e6
org $03a1ed : adc #$0002 ;adc #$0004 - sortie fix
org $03a08c : jsl text_ram_load2 : jmp $a096
org $03ae8c : jsl text_ram_load3 : jmp $aebc

;---Move/Attack/Etc Window---------------
org $0386b4 : lda #$02 ;x position
org $0386b8 : lda #$04 ;y position
org $0386bc : lda #$08 ;width
org $03869d : lda #$08 ;width
org $038af4 : lda #$02 ;spacing (text)
org $03870f : lda #$02 ;spacing (cursor)
org $038c82 : adc #$02 ;spacing (window)

;---Save/Load/Etc Window-----------------
org $03b7b8 : lda #$05 ;x position
org $03b7bc : lda #$04 ;y position
org $03b7c0 : lda #$0d ;width
org $03b7a2 : lda #$0d ;width
org $0382e7 : db $0a   ;chars per line

;---Orders Window------------------------
org $038800 : lda #$05 ;x position
org $038804 : lda #$08 ;y position
org $038808 : lda #$08 ;width
org $0387ea : lda #$08 ;width
org $03880c : lda #$0a ;height
org $0387ee : lda #$0a ;height
org $0382bb : db $02   ;spacing (text)
org $038850 : lda #$02 ;spacing (cursor)

;---Options Window-----------------------
org $03b7b8 : lda #$02 ;x position
org $03b7bc : lda #$06 ;y position
org $03b7c0 : lda #$0b ;width
org $03b7a2 : lda #$0b ;width
org $03b7c4 : lda #$0c ;height
org $03b7a6 : lda #$0c ;height
org $0382eb : db $02   ;spacing (text)
org $03b809 : lda #$02 ;spacing (cursor)
org $03848b : db $18   ;chars per line

;---Sub-Options Window-------------------
org $03bf0f : lda #$04 ;x position
org $03bf13 : lda #$06 ;y position
org $03bf17 : lda #$18 ;width
org $03bf03 : lda #$18 ;width
org $03bf1b : lda #$0a ;height
org $03bf07 : lda #$0a ;height
org $03848f : db $02   ;spacing (text)
org $03bf67 : lda #$02 ;spacing (cursor)
org $03c048 : adc #$02 ;spacing (right cursors)
org $03bfff : lda #$07 ;y position for right cursors

org $03c01f
  beq +
  lda #$06
  nop
  nop
+ adc #$0e

org $03c034
  beq +
  lda #$06
  nop
  nop
+ adc #$0e

;---Scenario/Turn Number Fix-------------
org $0492a4 : lda #$01

;---Scenario Window----------------------
org $03c15d : lda #$0b   ;x position
org $03c161 : lda #$0b   ;y position
org $03c165 : lda #$0a   ;width
org $03c151 : lda #$0a   ;width
org $03c18f : lda #$0e   ;position of scenario #
org $03c20f : lda #$0592 ;position of scenario ?#

;---Turn Window--------------------------
org $03c255 : lda #$0c ;x position
org $03c259 : lda #$0b ;y position
org $03c25d : lda #$07 ;width
org $03c249 : lda #$07 ;width
org $03c287 : lda #$0c ;position of turn #

;---Commander Window---------------------
org $039c48 : lda #$01 ;x position
org $039c4c : lda #$01 ;y position
org $039c50 : lda #$0c ;width
org $039c31 : lda #$0c ;width

;---Commander Options Window-------------
org $039e75 : lda #$05 ;x position
org $039e79 : lda #$06 ;y position
org $039e7d : lda #$0b ;width
org $039e5f : lda #$0b ;width

;---Item Equip Window--------------------
org $03e049 : lda #$01 ;x position
org $03e04d : lda #$01 ;y position
org $03e051 : lda #$09 ;width
org $03e03d : lda #$09 ;width

;---Item Menu Window---------------------
org $03e165 : lda #$0c ;x position
org $03e169 : lda #$01 ;y position
org $03e16d : lda #$07 ;width
org $03e159 : lda #$07 ;width

;---Item Submenu Windows-----------------
org $03e488 : lda #$0c ;x position
org $03e48c : lda #$01 ;y position
org $03e490 : lda #$07 ;width
org $03e47c : lda #$07 ;width
org $03d6b9 : db $01   ;position of buy text
org $03d6d1 : db $01   ;position of sell text
org $03d6e9 : db $01   ;position of equip text

;---Enemy Phase Window-------------------
org $03c2e0 : lda #$0b ;x position
org $03c2e4 : lda #$0b ;y position
org $03c2e8 : lda #$0b ;width
org $03c2d4 : lda #$0b ;width

;---First Class Change Window------------
org $02f3ef : lda #$0a ;x position
org $02f3f3 : lda #$09 ;y position
org $02f3f7 : lda #$0c ;width
org $02f3e3 : lda #$0c ;width

;---Second Class Change Window-----------
org $02f4c1 : lda #$08 ;x position
org $02f4c5 : lda #$06 ;y position
org $02f4c9 : lda #$10 ;width
org $02f4a3 : lda #$10 ;width

;---Third Class Change Window------------
org $02f517 : lda #$07 ;x position
org $02f51b : lda #$03 ;y position
org $02f51f : lda #$0b ;width
org $02f50b : lda #$0b ;width

;---Load Game Window---------------------
org $03bcc5 : lda #$0d ;x position
org $03bcc9 : lda #$06 ;y position
org $03bccd : lda #$11 ;width
org $03bcaf : lda #$11 ;width
org $03bcd1 : lda #$0c ;height
org $03bcb3 : lda #$0c ;height
org $03842f : db $02,$03,$fc,$ff,$02 ;spacing (text x/y/-/-/l)
org $03bd50 : lda #$02 ;spacing (cursor)
org $03bd37 : lda #$01 ;x cursor
org $03bd3c : lda #$03 ;y cursor
org $03842b : db $0d   ;chars per line
org $03bc33 : jsl lr_space    : jmp $bc49
org $03bc23 : jsl lr_scenario : nop : nop : nop : nop : jsr $be2d
org $03bc4f : jsl lr_scenario : nop : nop : nop : nop : jsr $be2d
org $03bc6a : jsl lr_resume   : nop : nop : nop : nop : jsr $be2d
org $03bc79 : jsl lr_nodata   : nop : nop : nop : nop : jsr $be2d
org $03bc5a : jmp $bc67
org $03be5f : jsl lr_num      : rts
;----------------------------------------

loadpc build/dl.xpc

lr_space() {
  lda #$0000
  sta [$05] : inc $05 : inc $05
  sta [$05] : inc $05 : inc $05
  sta [$05] : inc $05 : inc $05
  sta [$05] : inc $05 : inc $05
  sta [$05] : inc $05 : inc $05
  rtl
}

lr_nodata() {
  rep #$20
  lda #l_nodata
  bra lr_set
}

lr_resume() {
  rep #$20
  lda #l_resume
  bra lr_set
}

lr_scenario() {
  rep #$20
  lda #l_scenario
}

lr_set() {
  sta $02
  sep #$20
  phk
  pla
  sta $04
  rtl
}

;34 = ?
;35 = 0
;36 = 1
lr_num() {
  lda $077fff,x

  cmp #$16 : bcc +
  cmp #$19 : bcs +
;?1 - ?3 (22 - 24)
  lda #$34 : sta [$05] : rep #$20 : inc $05 : sep #$20
  lda #$00 : sta [$05] : rep #$20 : inc $05 : sep #$20
  lda $077fff,x
  sec : sbc #$16
  clc : adc #$36
  sta [$05] : rep #$20 : inc $05 : sep #$20
  lda #$00 : sta [$05] : rep #$20 : inc $05 : sep #$20
  rtl
+

;01 - 21
  phx
  ldx #$ff
  sec
- inx : sbc #$0a : bcs -
  clc : adc #$0a
  clc : adc #$35
  pha
  txa
  clc : adc #$35
  sta [$05]
  rep #$20 : inc $05 : sep #$20
  lda #$00
  sta [$05]
  rep #$20 : inc $05 : sep #$20
  pla
  sta [$05]
  rep #$20 : inc $05 : sep #$20
  lda #$00
  sta [$05]
  rep #$20 : inc $05 : sep #$20
  plx
  rtl
}

verify_intro_integrity() {
  php : rep #$30 : pha

;verify that intro routine has not been disabled ...
  lda $80fffc : cmp #$8000 : beq +
  stp
+ lda $808000 : cmp #$204c : beq +
  stp
+ lda $808002 : cmp #$d880 : beq +
  stp
+ lda $808020 : cmp #$005c : beq +
  stp
+ lda $808022 : cmp #$4080 : beq +
  stp
+

  pla : plp : rts
}

store_x1() {
  lda #$68 : sta $7e4012
  inc      : sta $7e402e
  lda #$6c : sta $7e4014
  inc      : sta $7e4030
  rtl
}

store_x2() {
  jsr verify_intro_integrity

  lda #$68 : sta $7e4012
  inc      : sta $7e402e
  lda #$6e : sta $7e4014
  inc      : sta $7e4030
  rtl
}

store_x3() {
  lda #$68 : sta $7e4012
  inc      : sta $7e402e
  lda #$70 : sta $7e4014
  inc      : sta $7e4030
  rtl
}

store_map_num() {
  lda $077fff,x
  cmp.b #22 : bne + : jmp store_x1
+ cmp.b #23 : bne + : jmp store_x2
+ cmp.b #24 : bne + : jmp store_x3
+ ldx #$ff
  sec

- inx : sbc #$0a
  bcs -
  clc : adc #$0a
  asl
  clc : adc #$6a
  sta $7e4014
  inc
  sta $7e4030
  txa
  bne + : rtl
+ asl
  clc : adc #$6a
  sta $7e4012
  inc
  sta $7e402e
  rtl
}

store_xnum() {
  rep #$20
  and #$00ff
  sec : sbc #$0015
  asl
  clc : adc #$212a
  sta $7e2002
  inc
  sta $7e200a
  lda #$2128
  sta $7e2000
  inc
  sta $7e2008
  rtl
}

write_num0() {
  stx $4014
  inx
  stx $4038
  sta $4016
  inc
  sta $403a
  lda #$00
  sta $4018
  sta $401a
  sta $403c
  sta $403e
  rtl
}

write_num1() {
  stx $4000
  inx
  stx $4008
  sta $4002
  inc
  sta $400a
  lda #$00
  sta $4004
  sta $4006
  sta $400c
  sta $400e
  rtl
}

convert_num() {
  and #$00ff
  clc
  adc #$0035
  asl
  rtl
}

store_num() {
  lda $17
  sta $7f2004
  inc
  sta $7f200c
  lda #$2000
  sta $7f2006
  sta $7f200e
  jml $0490e7
}

tile_list:
  dw $0000,$0001,$0002,$0003,$0004,$0005,$0006,$0007,$0008,$0009,$000a,$000b,$000c,$000d,$000e,$000f
  dw $0010,$0011,$0012,$0013,$0014,$0015,$0016,$0017,$0018,$0019,$001a,$001b,$001c,$001d,$001e,$001f
  dw $0020,$0021,$0022,$0023,$0024,$0025,$0026,$0027,$0028,$0029,$002a,$002b,$002c,$002d,$002e,$002f
  dw $0030,$0031,$0032,$0033,$0034,$0035,$0036,$0037,$0038,$0039,$003a,$003b,$003c,$003d,$003e

table resources/data/table16a.tbl

tile_list_name:
  db $11
  dw $0000,$0127,$0128,$0129,$012a,$012b,$012c,$012d,$012e,$012f,$0130,$0131,$0132,$0133,$0134,$0135
  dw $0136

tile_list_spoils:
  db $0e
  dw $0000,$011a,$011b,$011c,$011d,$011e,$011f,$0120,$0121,$0122,$0123,$0124,$0125,$0126

tile_list_map:
  db $40
  dw $0000,$00c8,$00c9,$00ca,$00cb,$00cc,$00cd,$010e,$010f,$0110,$0111,$0112,$0113,$0114,$0115,$0116
  dw $0117,$0118,$0119,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000
  dw $0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000,$0000
  dw $0000,$0000,$0000,$0000,$0018,$0035,$0036,$0037,$0038,$0039,$003a,$003b,$003c,$003d,$003e,$0000

tile_list_prepare:
  db $28
  dw $0000,$0070,$0071,$0072,$0073,$0074,$0075,$0076,$0077,$0078,$0079,$007a,$007b,$007c,$007d,$007e
  dw $007f,$0080,$0081,$0082,$0083,$0084,$0085,$0086,$0087,$0088,$0089,$008a,$008b,$008c,$008d,$008e
  dw $008f,$0090,$0091,$0092,$0093,$0094,$0095,$0096

tile_list_commands:
  db $30
  dw $0040,$0041,$0042,$0043,$0044,$0045,$0046,$0047,$0048,$0049,$004a,$004b,$004c,$004d,$004e,$004f
  dw $0050,$0051,$0052,$0053,$0054,$0055,$0056,$0057,$0058,$0059,$005a,$005b,$005c,$005d,$005e,$005f
  dw $0060,$0061,$0062,$0063,$0064,$0065,$0066,$0067,$0068,$0069,$006a,$006b,$006c,$006d,$006e,$006f

tile_list_options:
  db $7f
  dw $0000,$00a1,$00a2,$00a3,$00a4,$00a5,$00a6,$00a7,$00a8,$00a9,$00aa,$00ab,$00ac,$00ad,$00ae,$00af
  dw $00b0,$00b1,$00b2,$00b3,$00b4,$00b5,$00b6,$00b7,$00b8,$00b9,$00ba,$00bb,$00bc,$00bd,$00be,$00bf
  dw $00c0,$00c1,$00c2,$00c3,$00c4,$00c5,$00c6,$00c7,$00c8,$00c9,$00ca,$00cb,$00cc,$00cd,$00ce,$00cf
  dw $00d0,$00d1,$00d2,$0000,$0018,$0035,$0036,$0037,$0038,$0039,$003a,$003b,$003c,$003d,$003e,$00d3
  dw $00d4,$00d5,$00d6,$00d7,$00d8,$00d9,$00da,$00db,$00dc,$00dd,$00de,$00df,$00e0,$00e1,$00e2,$00e3
  dw $00e4,$00e5,$00e6,$00e7,$00e8,$00e9,$00ea,$00eb,$00ec,$00ed,$00ee,$00ef,$00f0,$00f1,$00f2,$00f3
  dw $00f4,$00f5,$00f6,$00f7,$00f8,$00f9,$00fa,$00fb,$0100,$0101,$0102,$0103,$0104,$0105,$0106,$0107
  dw $0108,$0109,$010a,$010b,$010c,$010d,$013b,$013c,$013d,$013e,$013f,$0140,$0141,$0142,$0143

tile_list_equipment:
  db $11
  dw $0084,$0085,$0086,$0087,$0088,$0089,$008a,$0097,$0098,$0099,$009a,$009b,$009c,$009d,$009e,$009f
  dw $00a0

tile_list_magic:
  db $04
  dw $0137,$0138,$0139,$013a;Cast

;this resets the line # for the spoils of war 8x8 vwf,
;since the 16x16 is drawn before any of the 8x8 lines
initfont8vwf4() {
  lda #$0000
  sta $7ffff4
  rts
}

load_tiles() {
  lda $0d : cmp #$40 : bne $03 : jmp .end
  rep #$20
  lda $0b
  cmp #$dbab : bne + : lda #tile_list_name      : jmp .load_list ;$01dbab
+ cmp #$e769 : bne + : jsr initfont8vwf4 : lda #tile_list_spoils : jmp .load_list ;$01e769
+ cmp #$8000 : bne + : lda #tile_list_map       : jmp .load_list ;$038000
+ cmp #$801a : bne + : lda #tile_list_prepare   : jmp .load_list ;$03801a
+ cmp #$8039 : bne + : lda #tile_list_commands  : jmp .load_list ;$038039
+ cmp #$8056 : bne + : lda #tile_list_options   : jmp .load_list ;$038056
+ cmp #$807e : bne + : lda #tile_list_options   : jmp .load_list ;$03807e
+ cmp #$d615 : bne + : lda #tile_list_equipment : jmp .load_list ;$03d615
+ cmp #$a742 : bne + : lda #tile_list_magic     : jmp .load_list ;$04a742
+ jmp .e1
.load_list
  rep #$10
  tax
  inc
  sta $0b
  lda $400000,x
  sep #$30
  sta $ca
  lda #$40
  sta $0d
  bra .end
.e1
  rep #$20
  lda #tile_list
  sta $0b
  sep #$20
  lda #$40
  sta $0d
  lda #$3f
  sta $ca
.end
  rep #$20
  lda [$0b]
  sta $17
  inc $0b
  inc $0b
  sep #$20
  rtl
}

sloc_test() {
  phb
  pla
  cmp #$22
  bne .end
  rep #$20
  lda $f7
  cmp #$a892 : bne + : jmp .t0
+ cmp #$a932 : bne + : jmp .t1
+ cmp #$a9ce : bne + : jmp .t2
+ cmp #$aa02 : bne + : jmp .t3
+ cmp #$aa2c : bne + : jmp .t4
+ cmp #$aa56 : bne + : jmp .t5
+
.end
  sep #$20
  lda ($f7),y
  iny
.loop
  pha
  sta $2180
  lda $2180
  pla
  inc
  dex
  bne .loop
  jml $00a5fe
.t0
  cpy #$000b : bne + : rep #$20 : lda #t_save0      : jsr .write : jml $00a5fe
+ cpy #$0015 : bne + : rep #$20 : lda #t_save1      : jsr .write : jml $00a5fe
+ cpy #$0021 : bne + : rep #$20 : lda #t_slota0     : jsr .write : jml $00a5fe
+ cpy #$0025 : bne + : rep #$20 : lda #t_slota1     : jsr .write : jml $00a5fe
+ cpy #$0029 : bne + : rep #$20 : lda #t_slotb0     : jsr .write : jml $00a5fe
+ cpy #$002d : bne + : rep #$20 : lda #t_slotb1     : jsr .write : jml $00a5fe
+ cpy #$0031 : bne + : rep #$20 : lda #t_slotc0     : jsr .write : jml $00a5fe
+ cpy #$0035 : bne + : rep #$20 : lda #t_slotc1     : jsr .write : jml $00a5fe
+ cpy #$0039 : bne + : rep #$20 : lda #t_next0      : jsr .write : jml $00a5fe
+ cpy #$0049 : bne + : rep #$20 : lda #t_next1      : jsr .write : jml $00a5fe
+ jmp .end
.t1
  cpy #$000b : bne + : rep #$20 : lda #t_load0      : jsr .write : jml $00a5fe
+ cpy #$0015 : bne + : rep #$20 : lda #t_load1      : jsr .write : jml $00a5fe
+ cpy #$0021 : bne + : rep #$20 : lda #t_resume0    : jsr .write : jml $00a5fe
+ cpy #$002f : bne + : rep #$20 : lda #t_resume1    : jsr .write : jml $00a5fe
+ cpy #$003d : bne + : rep #$20 : lda #t_slota0     : jsr .write : jml $00a5fe
+ cpy #$0041 : bne + : rep #$20 : lda #t_slota1     : jsr .write : jml $00a5fe
+ cpy #$0045 : bne + : rep #$20 : lda #t_slotb0     : jsr .write : jml $00a5fe
+ cpy #$0049 : bne + : rep #$20 : lda #t_slotb1     : jsr .write : jml $00a5fe
+ cpy #$004d : bne + : rep #$20 : lda #t_slotc0     : jsr .write : jml $00a5fe
+ cpy #$0051 : bne + : rep #$20 : lda #t_slotc1     : jsr .write : jml $00a5fe
+ jmp .end
.t2
  cpy #$0003 : bne + : rep #$20 : lda #t_scenario0a : jsr .write : jml $00a5fe
+ cpy #$000d : bne + : rep #$20 : lda #t_scenario0b : jsr .write : jml $00a5fe
+ cpy #$0013 : bne + : rep #$20 : lda #t_scenario1a : jsr .write : jml $00a5fe
+ cpy #$001d : bne + : rep #$20 : lda #t_scenario1b : jsr .write : jml $00a5fe
+ jmp .end
.t3
  cpy #$0003 : bne + : rep #$20 : lda #t_broken     : jsr .write : jml $00a5fe
+ jmp .end
.t4
  cpy #$0003 : bne + : rep #$20 : lda #t_nodata     : jsr .write : jml $00a5fe
+ jmp .end
.t5
  cpy #$0005 : bne + : rep #$20 : lda #t_skip0      : jsr .write : jml $00a5fe
+ cpy #$000d : bne + : rep #$20 : lda #t_skip1      : jsr .write : jml $00a5fe
+ jmp .end
.write
  phx
  tax
  sep #$20
.wloop
  lda $400000,x
  cmp #$fe
  bcs .endw
  sta $2180
  lda $2180
  inx
  iny
  bra .wloop
.endw
  plx
  dey
  rts
}

text_ram_load0() {
  lda r_commander,x
  sta $7f5fcc,x
  inx
  cpx #$2f
  bcc text_ram_load0
  dex
  ldx #$00
  rtl
}

text_ram_load1() {
  phx
  ldx #$00
.loop
  lda r_sortie,x
  sta [$08],y
  inx : inx : iny : iny
  cpx #$0c
  bcc .loop
  plx
  rtl
}

text_ram_load2() {
  phx
  ldx #$00
.loop
  lda r_cmd_ops,x
  sta [$08],y
  inx : iny
  cpy #$52
  bcc .loop
  plx
  rtl
}

text_ram_load3() {
  phx
  ldx #$00
.loop
  lda r_confirm,x
  sta [$02]
  inc $02 : inc $02 : inx : inx
  cmp #$ffff
  bne .loop
  plx
  rtl
}

set_tilepos() {
  lda $04
  and #$00ff
  cmp #$0040 : bcc + : jmp .end
+ lda $02
  cmp #$82c2 : bne + : lda #w_orders     : jmp .write
+ cmp #$82ec : bne + : lda #w_saveload   : jmp .write
+ cmp #$8383 : bne + : lda #w_scenario   : jmp .write
+ cmp #$83a2 : bne + : lda #w_turn       : jmp .write
+ cmp #$83b9 : bne + : lda #w_enemyphase : jmp .write
+ cmp #$8490 : bne + : lda #w_options    : jmp .write
+ cmp #$d676 : bne + : lda #w_itemequip  : jmp .write
+ cmp #$d699 : bne + : lda #w_itemmenu   : jmp .write
+ cmp #$d6bd : bne + : lda #w_buy        : jmp .write
+ cmp #$d6d5 : bne + : lda #w_sell       : jmp .write
+ cmp #$d6ed : bne + : lda #w_equip      : jmp .write
+ cmp #$dbc4 : bne + : lda #w_name       : jmp .write
+ cmp #$e788 : bne + : lda #w_summary    : jmp .write
+ jmp .end
.end
  lda [$02]
  sta $17
  rtl
.write
  sta $02
  sep #$20
  lda #$40
  sta $04
  rep #$20
  bra .end
}

loc_test() {
;add on one line for 8x24->8x16 hack
  lda $1f0d
  and #$00ff
  cmp #$0001
  bne $03
  inc $1f0d
  lda $08
  cmp #$829a : bne + : lda #s_move   : jmp .write
+ cmp #$829e : bne + : lda #s_attack : jmp .write
+ cmp #$82a2 : bne + : lda #s_magic  : jmp .write
+ cmp #$82a6 : bne + : lda #s_summon : jmp .write
+ cmp #$82aa : bne + : lda #s_heal   : jmp .write
+ cmp #$82ae : bne + : lda #s_orders : jmp .write
+ lda [$08]
  sta [$02]
  jml $038c5d
.write
  phx
  rep #$10
  ldy #$0000
  tax
  sep #$20
.loop
  lda $400000,x
  cmp #$ff
  beq .end
  sta [$02],y
  iny
  lda #$00
  sta [$02],y
  iny
  inx
  bra .loop
.end
  rep #$20
  lda #$fffe
  sta [$02],y
  iny : iny
  lda $02
  sty $02
  clc : adc $02
  sta $02
  sep #$10
  plx
  jml $038c72
}

table resources/data/table16a.tbl

l_nodata:
 db $22,$23,$24,$25,$26,$27,$00,$00,$00,$00,$00,$00,$00,$ff;No Data

l_resume:
 db $2e,$2f,$30,$31,$32,$ff;Resume

l_scenario:
 db $28,$29,$2a,$2b,$2c,$2d,$ff;Scenario

r_cmd_ops:
 db $f9,$ff,$01,$fa,$ff,$c0,$20,$fc
 db $ff,$02,$fb,$ff,$02,$03,$f1,$ff
 dw $000d,$000e,$000f,$0010,$0011,$0012,$0013,$0014,$fffe;Hire Troops
 dw $0015,$0016,$0017,$0018,$0019,$001a,$001b,$fffe      ;Item Equip
 dw $001c,$001d,$001e,$001f,$0020,$0021,$0022,$fffe      ;Placement
 dw $ffff

r_commander:
 db $f9,$ff,$01,$fa,$ff,$c0,$20,$fc
 db $ff,$02,$fb,$ff,$02,$01,$f1,$ff
 dw $0001,$0002,$0003,$0004,$0005,$0006,$0007,$0008,$0000,$fffe;Commander
 db $fa,$ff,$00,$20,$f9,$ff,$00,$fb
 db $ff,$02,$03

r_confirm:
 dw $0702
 dw $0023,$0024,$0025,$0026,$0027,$ffff;Confirm

r_sortie:
 dw $0009,$000a,$000b,$000c,$0000,$0000;Sortie

s_move:
 db $00,$01,$02,$03,$ff        ;Move
s_attack:
 db $04,$05,$06,$07,$08,$ff    ;Attack
s_magic:
 db $09,$0a,$0b,$0c,$ff        ;Magic
s_summon:
 db $0d,$0e,$0f,$10,$11,$12,$ff;Summon
s_heal:
 db $13,$14,$15,$16,$17,$ff    ;Health
s_orders:
 db $18,$19,$1a,$1b,$1c,$ff    ;Orders

w_buy:
 dw $0007,$0008,$0009,$ffff;Buy

w_enemyphase:
 dw $000a,$000b,$000c,$000d,$000e,$000f,$0010,$0011,$0012,$ffff;Enemy Phase

w_equip:
 dw $000d,$000e,$000f,$0010,$ffff;Equip

w_itemequip:
 dw $0000,$0001,$0002,$0003,$0004,$0005,$0006,$ffff;Equipment

w_itemmenu:
 dw $000d,$000e,$000f,$0010,$fffe;Equip
 dw $0007,$0008,$0009,$fffe      ;Buy
 dw $000a,$000b,$000c,$fffe      ;Sell
 dw $ffff

w_orders:
 dw $001d,$001e,$001f,$0020,$fffe      ;Battle
 dw $0021,$0022,$0023,$0024,$0025,$fffe;Assault
 dw $0026,$0027,$0028,$0029,$002a,$fffe;Defend
 dw $002b,$002c,$002d,$002e,$002f,$fffe;Manual
 dw $ffff

w_saveload:
 dw $0001,$0002,$0003,$0004,$0005,$0006,$0007,$0000,$0000,$0000;Save Game
 dw $0008,$0009,$000a,$000b,$000c,$000d,$000e,$0000,$0000,$0000;Load Game
 dw $000f,$0010,$0011,$0012,$0013,$0014,$0015,$0000,$0000,$0000;Objectives
 dw $0016,$0017,$0018,$0019,$001a,$0000,$0000,$0000,$0000,$0000;Options
 dw $001b,$001c,$001d,$001e,$001f,$0020,$0021,$0000,$0000,$0000;End Phase
 dw $ffff

w_scenario:
 dw $0001,$0002,$0003,$0004,$0005,$0006,$ffff;Scenario

w_sell:
 dw $000a,$000b,$000c,$ffff;Sell

w_summary:
 dw $0001,$0002,$0003,$0004,$0005,$0006,$0007,$0008,$0009;Spoils of War
 dw $0000,$000a,$000b,$000c,$000d;Money
 db $fa,$ff,$00,$20,$f9,$ff,$00,$fb,$ff,$16,$02,$09,$0c,$ff,$ff

w_turn:
 dw $0007,$0008,$0009,$ffff;Turn

w_options:
 dw $003f,$0040,$0041,$0042,$0043,$0044,$0045,$0046 ;Game Speed
 dw $0000,$005b,$005c,$005d,$0000,$0000             ;Fast
 dw $0000,$005e,$005f,$0060,$0061,$0062,$0000       ;Normal
 dw $0000,$0000,$0000

 dw $0047,$0048,$0049,$004a,$004b,$004c,$004d,$0000 ;Windows
 dw $0000,$0076,$0077,$0078,$0079,$0000             ;Remix
 dw $0000,$007a,$007b,$007c,$007d,$007e,$0000       ;Classic
 dw $0000,$0000,$0000

 dw $004e,$004f,$0050,$0051,$0052,$0053,$0054,$0055 ;Sound Mode
 dw $0000,$0063,$0064,$0065,$0066,$0067             ;Stereo
 dw $0000,$0068,$0069,$006a,$006b,$006c,$006d       ;Monoraul
 dw $0000,$0000,$0000

 dw $0056,$0057,$0058,$0059,$005a,$0000,$0000,$0000 ;Battles
 dw $0000,$006e,$006f,$0070,$0071,$0072             ;Display
 dw $0000,$0073,$0074,$0075                         ;Skip
 dw $0000,$0000,$0000
 dw $ffff

;Please enter your name
w_name:
 dw $0000,$0001,$0002,$0003,$0004,$0005,$0006,$0007,$0008
 dw $0009,$000a,$000b,$000c,$000d,$000e,$000f,$0010,$0000
 dw $ffff

;' Corrupt data     .'
t_broken:
  db $00,$06,$52,$58,$58,$5E,$54,$5C,$00,$3c,$36,$5C,$36,$00,$00,$00,$00,$00
  db $01,$07,$53,$59,$59,$5F,$55,$5D,$01,$3d,$37,$5D,$37,$01,$01,$01,$01,$01
  db $FE

;'  Load  .'
t_load0:
  db $00,$00,$18,$52,$36,$3C,$00,$00,$FE
t_load1:
  db $01,$01,$19,$53,$37,$3D,$01,$01,$FF

;'Next scenario .'
t_next0:
  db $1C,$3E,$64,$5C,$00,$5a,$3A,$3E,$50,$36,$58,$46,$52,$00,$FE
t_next1:
  db $1D,$3F,$65,$5D,$01,$5b,$3B,$3F,$51,$37,$59,$47,$53,$01,$FF

;' No data          .'
t_nodata:
  db $00,$1C,$52,$00,$3c,$36,$5C,$36,$00,$00,$00,$00,$00,$00,$00,$00,$00,$00
  db $01,$1D,$53,$01,$3d,$37,$5D,$37,$01,$01,$01,$01,$01,$01,$01,$01,$01,$01
  db $fe

;'Resume      .'
t_resume0:
  db $24,$3E,$5A,$5E,$4E,$3E,$00,$00,$00,$00,$00,$00,$FE
t_resume1:
  db $25,$3F,$5B,$5F,$4F,$3F,$01,$01,$01,$01,$01,$01,$FF

;'  Save  .'
t_save0:
  db $00,$00,$26,$36,$60,$3E,$00,$00,$FE
t_save1:
  db $01,$01,$27,$37,$61,$3F,$01,$01,$FF

;'Scenario.'
t_scenario0a:
  db $26,$3A,$3E,$50,$36,$58,$46,$52,$FE

;'xxxx.'
t_scenario0b:
  db $64,$64,$64,$64,$FE

;'Scenario.'
t_scenario1a:
  db $27,$3B,$3F,$51,$37,$59,$47,$53,$FF

;'xxxx.'
t_scenario1b:
  db $65,$65,$65,$65,$FF

;'Skip  .'
t_skip0:
  db $26,$4A,$46,$54,$00,$00,$FE
t_skip1:
  db $27,$4B,$47,$55,$01,$01,$FF

;'01.'
t_slota0:
  db $6A,$6C,$FE
t_slota1:
  db $6B,$6D,$FF

;'02.'
t_slotb0:
  db $6A,$6E,$FE
t_slotb1:
  db $6B,$6F,$FF

;'03.'
t_slotc0:
  db $6A,$70,$FE
t_slotc1:
  db $6B,$71,$FF

savepc build/dl.xpc

;reorder tiles for battle screen [vs/cast]
org $02c3fb : db $f0,$20,$f2,$20,$f4,$20,$f6,$20
org $02c407 : db $f1,$20,$f3,$20,$f5,$20,$f7,$20

;scenario for the location map
org $05ef20
 db $8b,$52,$0c,$00
 dw $2100,$2150,$2152,$2154,$2156,$2158,$215a,$2100,$2100,$2100,$2100,$2100
 db $ab,$52,$0c,$00
 dw $2100,$2151,$2153,$2155,$2157,$2159,$215b,$2100,$2100,$2100,$2100,$2100
