lorom

;---Window Backgrounds (PC-FX)-----------
  ;Color Subtract Mode
  org $42d4 : db $83

  ;Make the main windows green
  org $fba5 : dw $9141

  ;Window Shades
  org $437a
  db $3b,$45,$9b  ;Ally Window
  db $3f,$4c,$80  ;NPC Window
  db $25,$4f,$8f  ;Enemy Window

;---Format Description-------------------
; db $RR,$GG,$BB
; For each $XX = bgriiiii
; Example: $30 = 00110000 (Green: 50%)
;
; R-Range - 0x20 - 0x3f
; G-Range - 0x40 - 0x5f
; B-Range - 0x80 - 0x9f
