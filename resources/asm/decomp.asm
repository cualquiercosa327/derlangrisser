;
; Der Langrisser Decompressed Blocks
;
; This file contains Super Nintendo code to bypass the game's compression
; system and insert several decompressed scenario data blocks. We use this to
; correct several bugs in the game where the programmers displayed the wrong
; character portraits.
;
; Version:   1.0
; Author:    D, byuu
; Copyright: (c) 2006, 2016 DL Team
; Website:   https://github.com/sobodash/derlangrisser/
; License:   BSD License <http://opensource.org/licenses/bsd-license.php>
;

lorom

org $00bcb0 : jml check_scenario : nop #2

org $608000

; Load a decompressed block
macro load_ev(ev_start, ev_end)
  php : rep #$30 : pha : phx : phy
  sep #$20
  ldx #$0000

- lda <ev_start>,x : sta [$05]
  inc $05 : bne + : inc $06
+ inx : cpx.w #<ev_end>-<ev_start> : bcc -

  rep #$30 : ply : plx : pla : plp : rtl
endmacro


; Check scenario and load decompressed block on match
macro check_sc(sc, byte1, byte2, byte3)
  lda $04 : cmp #$<byte1> : bne +
  lda $03 : cmp #$<byte2> : bne +
  lda $02 : cmp #$<byte3> : bne +
  jml load_ev<sc>
+
endmacro


check_scenario() {
  %check_sc(08, 2e, 83, 69) ; $2e8369 Scenario 1
  %check_sc(09, 2e, 8a, 7f) ; $2e8a7f Scenario 2
  
  %check_sc(10, 2e, 90, eb) ; $2e90eb Scenario 3
  %check_sc(11, 2e, 98, 7a) ; $2e987a Scenario 4
  %check_sc(12, 2e, 9f, af) ; $2e9faf Scenario 5
  %check_sc(13, 2e, a6, c2) ; $2ea6c2 Scenario 6
  %check_sc(14, 2e, ac, 07) ; $2eac07 Scenario 7
  %check_sc(15, 2e, b3, 34) ; $2eb334 Scenario 8
  %check_sc(16, 2e, b7, b8) ; $2eb7b8 Scenario 9
  %check_sc(17, 2e, bd, 4c) ; $2ebd4c Scenario 10
  %check_sc(18, 2e, c1, e5) ; $2ec1e5 Scenario 11
  %check_sc(19, 2e, c8, db) ; $2ec8db Scenario 12
  
  %check_sc(20, 2e, cd, 39) ; $2ecd39 Scenario 13
  %check_sc(21, 2e, d1, 23) ; $2ed123 Scenario 14
  %check_sc(22, 2e, d5, ef) ; $2ed5ef Scenario 15
  %check_sc(23, 2e, da, a3) ; $2edaa3 Scenario 16
  %check_sc(24, 2e, df, 15) ; $2edf15 Scenario 17
  %check_sc(25, 2e, e8, 33) ; $2ee833 Scenario 18
  %check_sc(26, 2e, ec, 7f) ; $2eec7f Scenario 19
  %check_sc(27, 2e, f1, 18) ; $2ef118 Scenario 20
  %check_sc(28, 2e, f6, 43) ; $2ef643 Scenario 21
  %check_sc(29, 2e, fa, e6) ; $2efae6 Scenario 22
  
  %check_sc(30, 2e, fe, 4f) ; $2efe4f Scenario 23/35
  %check_sc(31, 2f, 83, 05) ; $2f8305 Scenario 24
  %check_sc(32, 2f, 87, 4e) ; $2f874e Scenario 25
  %check_sc(33, 2f, 8c, 08) ; $2f8c08 Scenario 26
  %check_sc(34, 2f, 8e, d8) ; $2f8ed8 Scenario 27
  %check_sc(35, 2f, 94, 07) ; $2f9407 Scenario 28
  %check_sc(36, 2f, 98, 2d) ; $2f982d Scenario 29
  %check_sc(37, 2f, 9b, 9e) ; $2f9b9e Scenario 30
  %check_sc(38, 2f, a1, a6) ; $2fa1a6 Scenario 31/33
  
  %check_sc(43, 2f, ab, ae) ; $2fabae Scenario 32/34/36
  %check_sc(44, 2f, af, 9a) ; $2faf9a Scenario 37
  %check_sc(45, 2f, b6, f3) ; $2fb6f3 Scenario 38
  %check_sc(46, 2f, bb, d4) ; $2fbbd4 Scenario 39
  %check_sc(47, 2f, c0, 64) ; $2fc064 Scenario 40
  %check_sc(48, 2f, c8, e6) ; $2fc8e6 Scenario 41
  %check_sc(49, 2f, ce, e4) ; $2fcee4 Scenario 42
  
  %check_sc(50, 2f, d6, 1f) ; $2fd61f Scenario 43
  %check_sc(51, 2f, da, a5) ; $2fdaa5 Scenario 44
  %check_sc(52, 2e, e0, 7d) ; $2fe07d Scenario 45
  %check_sc(53, 2f, e6, 76) ; $2fe676 Scenario 46
  %check_sc(54, 2f, ec, 98) ; $2fec98 Scenario 47/74
  %check_sc(55, 2f, f1, a1) ; $2ff1a1 Scenario 48/75
  %check_sc(56, 2f, f5, 46) ; $2ff546 Scenario 49/76
  %check_sc(57, 2f, fa, ed) ; $2ffaed Scenario 50/77
  %check_sc(58, 30, 80, d4) ; $3080d4 Scenario 51
  %check_sc(59, 30, 85, 1c) ; $30851c Scenario 52
  
  %check_sc(60, 30, 8a, a9) ; $308aa9 Scenario 53
  %check_sc(61, 30, 8e, 73) ; $308e73 Scenario 54
  %check_sc(62, 30, 93, 7a) ; $30937a Scenario 55/78
  %check_sc(63, 30, 99, 76) ; $309976 Scenario 56
  %check_sc(64, 30, 9e, 07) ; $309e07 Scenario 57
  %check_sc(65, 30, a1, 68) ; $30a168 Scenario 58
  %check_sc(66, 30, a6, 19) ; $30a619 Scenario 59
  %check_sc(67, 30, ab, 72) ; $30ab72 Scenario 60
  %check_sc(68, 30, b1, 42) ; $30b142 Scenario 61
  %check_sc(69, 30, b5, a6) ; $30b5a6 Scenario 62
  
  %check_sc(70, 30, ba, 92) ; $30ba92 Scenario 63
  %check_sc(71, 30, bf, c1) ; $30bfc1 Scenario 64
  %check_sc(72, 30, c3, e1) ; $30c3e1 Scenario 65
  %check_sc(73, 30, c9, 4a) ; $30c94a Scenario 66
  %check_sc(74, 30, cf, 4e) ; $30cf4e Scenario 67
  %check_sc(75, 30, d3, 88) ; $30d388 Scenario 68
  %check_sc(76, 30, d8, e9) ; $30d8e9 Scenario 69
  %check_sc(77, 30, de, 86) ; $30de86 Scenario 70
  %check_sc(78, 30, e5, d7) ; $30e5d7 Scenario 71
  %check_sc(79, 30, e9, c2) ; $30e9c2 Scenario 72
  
  %check_sc(82, 30, ec, ee) ; $30ecee Scenario 73
  
  ; Untested:
  ; $30f263 = Lushiris Quiz
  ; $30f503 = Lushiris Tactics

  lda $08 : pha
  lda $09 : pha
  jml $00bcb6
}

load_ev08() {
  %load_ev(ev08, ev08_end)
}
load_ev09() {
  %load_ev(ev09, ev09_end)
}
load_ev10() {
  %load_ev(ev10, ev10_end)
}
load_ev11() {
  %load_ev(ev11, ev11_end)
}
load_ev12() {
  %load_ev(ev12, ev12_end)
}
load_ev13() {
  %load_ev(ev13, ev13_end)
}
load_ev14() {
  %load_ev(ev14, ev14_end)
}
load_ev15() {
  %load_ev(ev15, ev15_end)
}
load_ev16() {
  %load_ev(ev16, ev16_end)
}
load_ev17() {
  %load_ev(ev17, ev17_end)
}
load_ev18() {
  %load_ev(ev18, ev18_end)
}
load_ev19() {
  %load_ev(ev19, ev19_end)
}
load_ev20() {
  %load_ev(ev20, ev20_end)
}
load_ev21() {
  %load_ev(ev21, ev21_end)
}
load_ev22() {
  %load_ev(ev22, ev22_end)
}
load_ev23() {
  %load_ev(ev23, ev23_end)
}
load_ev24() {
  %load_ev(ev24, ev24_end)
}
load_ev25() {
  %load_ev(ev25, ev25_end)
}
load_ev26() {
  %load_ev(ev26, ev26_end)
}
load_ev27() {
  %load_ev(ev27, ev27_end)
}
load_ev28() {
  %load_ev(ev28, ev28_end)
}
load_ev29() {
  %load_ev(ev29, ev29_end)
}
load_ev30() {
  %load_ev(ev30, ev30_end)
}
load_ev31() {
  %load_ev(ev31, ev31_end)
}
load_ev32() {
  %load_ev(ev32, ev32_end)
}
load_ev33() {
  %load_ev(ev33, ev33_end)
}
load_ev34() {
  %load_ev(ev34, ev34_end)
}
load_ev35() {
  %load_ev(ev35, ev35_end)
}
load_ev36() {
  %load_ev(ev36, ev36_end)
}
load_ev37() {
  %load_ev(ev37, ev37_end)
}
load_ev38() {
  %load_ev(ev38, ev38_end)
}
load_ev43() {
  %load_ev(ev43, ev43_end)
}
load_ev44() {
  %load_ev(ev44, ev44_end)
}
load_ev45() {
  %load_ev(ev45, ev45_end)
}
load_ev46() {
  %load_ev(ev46, ev46_end)
}
load_ev47() {
  %load_ev(ev47, ev47_end)
}
load_ev48() {
  %load_ev(ev48, ev48_end)
}
load_ev49() {
  %load_ev(ev49, ev49_end)
}
load_ev50() {
  %load_ev(ev50, ev50_end)
}
load_ev51() {
  %load_ev(ev51, ev51_end)
}
load_ev52() {
  %load_ev(ev52, ev52_end)
}
load_ev53() {
  %load_ev(ev53, ev53_end)
}
load_ev54() {
  %load_ev(ev54, ev54_end)
}
load_ev55() {
  %load_ev(ev55, ev55_end)
}
load_ev56() {
  %load_ev(ev56, ev56_end)
}
load_ev57() {
  %load_ev(ev57, ev57_end)
}
load_ev58() {
  %load_ev(ev58, ev58_end)
}
load_ev59() {
  %load_ev(ev59, ev59_end)
}
load_ev60() {
  %load_ev(ev60, ev60_end)
}
load_ev61() {
  %load_ev(ev61, ev61_end)
}
load_ev62() {
  %load_ev(ev62, ev62_end)
}
load_ev63() {
  %load_ev(ev63, ev63_end)
}
load_ev64() {
  %load_ev(ev64, ev64_end)
}
load_ev65() {
  %load_ev(ev65, ev65_end)
}
load_ev66() {
  %load_ev(ev66, ev66_end)
}
load_ev67() {
  %load_ev(ev67, ev67_end)
}
load_ev68() {
  %load_ev(ev68, ev68_end)
}
load_ev69() {
  %load_ev(ev69, ev69_end)
}
load_ev70() {
  %load_ev(ev70, ev70_end)
}
load_ev71() {
  %load_ev(ev71, ev71_end)
}
load_ev72() {
  %load_ev(ev72, ev72_end)
}
load_ev73() {
  %load_ev(ev73, ev73_end)
}
load_ev74() {
  %load_ev(ev74, ev74_end)
}
load_ev75() {
  %load_ev(ev75, ev75_end)
}
load_ev76() {
  %load_ev(ev76, ev76_end)
}
load_ev77() {
  %load_ev(ev77, ev77_end)
}
load_ev78() {
  %load_ev(ev78, ev78_end)
}
load_ev79() {
  %load_ev(ev79, ev79_end)
}
load_ev82() {
  %load_ev(ev82, ev82_end)
}

ev08:
  incbin resources/events/ev08.dat
ev08_end:

ev09:
  incbin resources/events/ev09.dat
ev09_end:

ev10:
  incbin resources/events/ev10.dat
ev10_end:

ev11:
  incbin resources/events/ev11.dat
ev11_end:

ev12:
  incbin resources/events/ev12.dat
ev12_end:

ev13:
  incbin resources/events/ev13.dat
ev13_end:

ev14:
  incbin resources/events/ev14.dat
ev14_end:

ev15:
  incbin resources/events/ev15.dat
ev15_end:

; $0547 = 0d->25
; Correct Aaron's portrait (was Cherie)
ev16:
  incbin resources/data/e008.bin
ev16_end:

ev17:
  incbin resources/events/ev17.dat
ev17_end:

ev18:
  incbin resources/events/ev18.dat
ev18_end:

ev19:
  incbin resources/events/ev19.dat
ev19_end:

ev20:
  incbin resources/events/ev20.dat
ev20_end:

ev21:
  incbin resources/events/ev21.dat
ev21_end:

ev22:
  incbin resources/events/ev22.dat
ev22_end:

ev23:
  incbin resources/events/ev23.dat
ev23_end:

ev24:
  incbin resources/events/ev24.dat
ev24_end:

ev25:
  incbin resources/events/ev25.dat
ev25_end:

ev26:
  incbin resources/events/ev26.dat
ev26_end:

ev27:
  incbin resources/events/ev27.dat
ev27_end:

ev28:
  incbin resources/events/ev28.dat
ev28_end:

ev29:
  incbin resources/events/ev29.dat
ev29_end:

ev30:
  incbin resources/events/ev30.dat
ev30_end:

ev31:
  incbin resources/events/ev31.dat
ev31_end:

; $0372 = eb->ec [43]
; Correct Vampire Lord portrait
; $038d = eb->f7 [102]
; $039a = eb->f7 (untested)
; Correct Esto portrait
; $03fe = f0->f7 [53]
; Correct Esto portrait
ev32:
  incbin resources/data/e024.bin
ev32_end:

ev33:
  incbin resources/events/ev33.dat
ev33_end:

ev34:
  incbin resources/events/ev34.dat
ev34_end:

ev35:
  incbin resources/events/ev35.dat
ev35_end:

ev36:
  incbin resources/events/ev36.dat
ev36_end:

ev37:
  incbin resources/events/ev37.dat
ev37_end:

ev38:
  incbin resources/events/ev38.dat
ev38_end:

ev43:
  incbin resources/events/ev43.dat
ev43_end:

ev44:
  incbin resources/events/ev44.dat
ev44_end:

ev45:
  incbin resources/events/ev45.dat
ev45_end:

ev46:
  incbin resources/events/ev46.dat
ev46_end:

ev47:
  incbin resources/events/ev47.dat
ev47_end:

ev48:
  incbin resources/events/ev48.dat
ev48_end:

ev49:
  incbin resources/events/ev49.dat
ev49_end:

ev50:
  incbin resources/events/ev50.dat
ev50_end:

ev51:
  incbin resources/events/ev51.dat
ev51_end:

ev52:
  incbin resources/events/ev52.dat
ev52_end:

ev53:
  incbin resources/events/ev53.dat
ev53_end:

ev54:
  incbin resources/events/ev54.dat
ev54_end:

ev55:
  incbin resources/events/ev55.dat
ev55_end:

ev56:
  incbin resources/events/ev56.dat
ev56_end:

ev57:
  incbin resources/events/ev57.dat
ev57_end:

ev58:
  incbin resources/events/ev58.dat
ev58_end:

ev59:
  incbin resources/events/ev59.dat
ev59_end:

ev60:
  incbin resources/events/ev60.dat
ev60_end:

ev61:
  incbin resources/events/ev61.dat
ev61_end:

ev62:
  incbin resources/events/ev62.dat
ev62_end:

ev63:
  incbin resources/events/ev63.dat
ev63_end:

ev64:
  incbin resources/events/ev64.dat
ev64_end:

ev65:
  incbin resources/events/ev65.dat
ev65_end:

ev66:
  incbin resources/events/ev66.dat
ev66_end:

ev67:
  incbin resources/events/ev67.dat
ev67_end:

ev68:
  incbin resources/events/ev68.dat
ev68_end:

ev69:
  incbin resources/events/ev69.dat
ev69_end:

ev70:
  incbin resources/events/ev70.dat
ev70_end:

ev71:
  incbin resources/events/ev71.dat
ev71_end:

ev72:
  incbin resources/events/ev72.dat
ev72_end:

ev73:
  incbin resources/events/ev73.dat
ev73_end:

ev74:
  incbin resources/events/ev74.dat
ev74_end:

ev75:
  incbin resources/events/ev75.dat
ev75_end:

ev76:
  incbin resources/events/ev76.dat
ev76_end:

ev77:
  incbin resources/events/ev77.dat
ev77_end:

ev78:
  incbin resources/events/ev78.dat
ev78_end:

ev79:
  incbin resources/events/ev79.dat
ev79_end:

; $02d3 = ed->ee
; Correct Serpent's portrait (was Dullahan)
ev82:
  incbin resources/data/e072.bin
ev82_end:

