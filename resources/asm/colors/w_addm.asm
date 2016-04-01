lorom

;---Window Backgrounds (Add Mode)--------
  ;Color Add Mode
  org $42d4 : db $03

  ;Window Shades
  org $437a
  db $20,$4f,$80  ;Ally Window
  db $20,$40,$8f  ;NPC Window
  db $2f,$40,$80  ;Enemy Window

;---Format Description-------------------
; db $RR,$GG,$BB
; For each $XX = bgriiiii
; Example: $30 = 00110000 (Green: 50%)
;
; R-Range - 0x20 - 0x3f
; G-Range - 0x40 - 0x5f
; B-Range - 0x80 - 0x9f
