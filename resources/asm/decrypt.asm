lorom

;*****
;script decryption routines
;note that there is one additional decryption routine
;located in font12.asm, in function load_5d()
;*****

;*****
;lda [$f7],y : inc $f7
;*****
org $00ac13 : jsl decrypt0 ;used to seek to character names

;*****
;lda [$f7] : sta [$08],y
;*****
org $07a44f : jsl decrypt1 ;used to load script
org $01fa8b : jsl decrypt1 ;used to load character names
org $02f792 : jsl decrypt1 ;used to load class change script
org $02f7e6 : jsl decrypt1 ;used to load class change script

;*****
;lda [$f7] : sta $7f6001,x
;*****
org $0085d2 : jsl decrypt2 : nop #2 ;used to load menu script

;*****
;lda [$5d] : sta $66
;*****
org $00acce : jsl decrypt3 ;used to load scenario overview script
org $00b588 : jsl decrypt3 ;used to load menu option script
org $00ae91 : jsl decrypt3 ;used to load item list script

;*****
;lda [$5d],y : sta $66
;*****
org $00b86e : jsl decrypt4 ;used to load equipment shop script

;*****
;lda [$f7] : sta [$05],y
;*****
org $01cf3d : jsl decrypt5 ;used to load level up script

;*****
;lda [$f7] : sta $7f6000,x
;*****
org $06d7dd : jsl decrypt6 : nop #2 ;used to load ending character summary script
org $1efa1f : jsl decrypt6 : nop #2 ;used to load intro (attract 1) script
org $06de62 : jsl decrypt6 : nop #2 ;used to load intro (attract 2) formation script
org $07d263 : jsl decrypt6 : nop #2 ;used to load item drop script

;*****
;lda [$f7],y : sta [$08],y
;*****
org $1bfeae : jsl decrypt7 ;used to load intro (attract 1) dialogue script

;*****
;lda [$f7] : sta [$0b],y
;*****
org $07bdd0 : jsl decrypt8 ;used to load 'equipped holy rod' item drop script

loadpc build/dl.xpc

;lda [$f7],y : inc $f7
decrypt0() {
  lda $f9
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $f8  : bpl +

;decryption
  php : rep #$30 : phx
  tya : clc : adc $f7 : ora #$8000 : sta $7ffffe : tax
  sep #$20 : lda [$f7],y
  eor $400000,x
  plx : plp
  sec : sbc $7ffffe
  inc $f7
  rtl

;no decryption
+ lda [$f7],y
  inc $f7
  rtl
}

;lda [$f7] : sta [$08],y
decrypt1() {
  lda $f9
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $f8  : bpl +

;decryption
  php : rep #$10 : phx
  ldx $f7
  lda [$f7]
  eor $400000,x
  plx : plp
  sec : sbc $f7
  sta [$08],y
  rtl

;no decryption
+ lda [$f7]
  sta [$08],y
  rtl
}

;lda [$f7] : sta $7f6001,x
decrypt2() {
  lda $f9
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $f8  : bpl +

;decryption
  php : rep #$10 : phx
  ldx $f7
  lda [$f7]
  eor $400000,x
  plx : plp
  sec : sbc $f7
  sta $7f6001,x
  rtl

;no decryption
+ lda [$f7]
  sta $7f6001,x
  rtl
}

;lda [$5d] : sta $66
decrypt3() {
  lda $5f
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $5e  : bpl +

;decryption
  php : rep #$10 : phx
  ldx $5d
  lda [$5d]
  eor $400000,x
  plx : plp
  sec : sbc $5d
  sta $66
  rtl

;no decryption
+ lda [$5d]
  sta $66
  rtl
}

;lda [$5d],y : sta $66
decrypt4() {
  lda $5f
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $5e  : bpl +

;decryption
  php : rep #$30 : phx
  tya : clc : adc $5d : ora #$8000 : sta $7ffffe : tax
  sep #$20 : lda [$5d],y
  eor $400000,x
  plx : plp
  sec : sbc $7ffffe
  sta $66
  rtl

;no decryption
+ lda [$5d],y
  sta $66
  rtl
}

;lda [$f7] : sta [$05],y
decrypt5() {
  lda $f9
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $f8  : bpl +

;decryption
  php : rep #$10 : phx
  ldx $f7
  lda [$f7]
  eor $400000,x
  plx : plp
  sec : sbc $f7
  sta [$05],y
  rtl

;no decryption
+ lda [$f7]
  sta [$05],y
  rtl
}

;lda [$f7] : sta $7f6000,x
decrypt6() {
  lda $f9
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $f8  : bpl +

;decryption
  php : rep #$10 : phx
  ldx $f7
  lda [$f7]
  eor $400000,x
  plx : plp
  sec : sbc $f7
  sta $7f6000,x
  rtl

;no decryption
+ lda [$f7]
  sta $7f6000,x
  rtl
}

;lda [$f7],y : sta [$08],y
decrypt7() {
  lda $f9
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $f8  : bpl +

;decryption
  php : rep #$30 : phx
  tya : clc : adc $f7 : ora #$8000 : sta $7ffffe : tax
  sep #$20 : lda [$f7],y
  eor $400000,x
  plx : plp
  sec : sbc $7ffffe
  sta [$08],y
  rtl

;no decryption
+ lda [$f7],y
  sta [$08],y
  rtl
}

;lda [$f7] : sta [$0b],y
decrypt8() {
  lda $f9
  cmp #$40 : bcc +
  cmp #$60 : bcs +
  bit $f8  : bpl +

;decryption
  php : rep #$10 : phx
  ldx $f7
  lda [$f7]
  eor $400000,x
  plx : plp
  sec : sbc $f7
  sta [$0b],y
  rtl

;no decryption
+ lda [$f7]
  sta [$0b],y
  rtl
}

savepc build/dl.xpc
