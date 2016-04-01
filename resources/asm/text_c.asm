lorom

;*****
;commander summary has absolutely no support for negative
;numbers, and the menu tilemap is completely hardcoded to
;display '+' no matter what. requires significant modifications
;to support negative numbers ...
;*****
org $038f03 : jsl load_number ;jsl $0390c2 -- A
org $038f2a : jsl load_number ;jsl $0390c2 -- D
org $048cb1 : jsl copy_number : nop #2

;*****
;subroutine to load numbers for battles and map bottom panel
;starts at $02bccc. it is possible to skip over the negative
;number processing by jumping to $02bcd6, which is what the
;battle system does. this is not ideal, so modify the battle
;system to not jump over negative number parsing.
;below change only affects NN+NN values for A and D, for both
;unit types. tactical advantage numbers are not affected at all.
;*****
org $04f1c7 : jsl $02bccc ;jsl $02bcd6
org $04f129 : jsl $02bccc ;jsl $02bcd6
org $04f17f : jsl $02bccc ;jsl $02bcd6

;*****
;number loading routine supports both '0N' and ' N' formatted numbers.
;if $1f == 0, format is ' N'
;if $1f != 0, format is '0N'
;*****
;02bd03 lda $1f
;02bd05 beq $02bd0f
;*****
;enable below line to force ' N' formatted numbers everywhere.
;org $02bd05 : db $80

loadpc build/dl.xpc

;*****
;this function completely replaces subroutine $0390c2,
;whose purpose was to load number from $17 and store in [$02]
;this function adds support for negative numbers by adding a
;flag onto the tilemap entry that is then parsed by copy_number
;*****
;$17 = 8-bit number
;[$02],0 = output high number
;[$02],2 = output low number
;*****
load_number() {
  php : rep #$30 : pha : phx : phy

;clear number, used to clear negative flag
  lda #$0000 : sta [$02]

;test if number is negative
  ldy $16 : bpl +

;it is, set negative flag
  lda #$fe00 : sta [$02]

;$17 = abs($17)
  sep #$20
  lda #$00
  sec : sbc $17
  sta $17
  rep #$20

+ lda $17 : and #$00ff
  ldx #$0000

- sec : sbc #$000a : bmi +
  inx : bra -
+ clc : adc #$001c ;#$000a + #$0012
  ldy #$0002 : sta [$02],y

  txa : ldx #$0000

- sec : sbc #$000a : bmi +
  inx : bra -
+ clc : adc #$001c ;#$000a + #$0012
  cmp #$0012 : bne + ;replace '0N' with ' N'
  lda #$0000
+ ora [$02] : sta [$02]

  lda $02 : clc : adc #$0004 : sta $02

+ rep #$30 : ply : plx : pla : plp : rtl
}

;*****
;load current value that is to be copied to tilemap,
;test for negative flag. if found, test previous
;value for '+'. if both conditions are true, then
;turn '+' to '-', clear negative flag from tilemap.
;if false, load tilemap entry normally and return.
;*****
;m=0,x=1
;lda $17
;clc : adc $1f48
;*****
copy_number() {
  lda $17
  and #$ff00
  cmp #$fe00 : beq +
  lda $17 : clc : adc $1f48 : rtl

+ dec $05 : dec $05
  lda [$05]
  cmp #$2010 : beq +
  inc $05 : inc $05
  lda $17 : clc : adc $1f48 : rtl

+ inc : sta [$05]
  inc $05 : inc $05
  lda $17
  and #$00ff
  clc : adc $1f48
  rtl
}

savepc build/dl.xpc
