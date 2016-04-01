lorom

;---Window Backgrounds (Better)----------
  ;Color Subtract Mode
  org $42d4 : db $83

  ;Make the main windows light blue
  org $fba5 : dw $3063

  ;Window Shades
  org $437a
  db $3f,$4c,$80  ;Ally Window
  db $3b,$45,$9b  ;NPC Window
  db $25,$4f,$8f  ;Enemy Window

;---Header Update------------------------

  ;Title
  org $7fc0 : db 'Der Langrisser'

  ;ROM Size
  org $7fd7 : db $0c

  ;Country Code
  org $7fd9 : db $01

;---Format Description-------------------
; db $RR,$GG,$BB
; For each $XX = bgriiiii
; Example: $30 = 00110000 (Green: 50%)
;
; R-Range - 0x20 - 0x3f
; G-Range - 0x40 - 0x5f
; B-Range - 0x80 - 0x9f
