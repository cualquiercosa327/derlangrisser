;
; Der Langrisser Elemental Patch
;
; This files contains byte patches that enable the broken Elemental unit by
; substituting it for the unused but enabled Demon unit.

; Version:   1.0
; Author:    Derrick Sobodash <derrick@sobodash.com>
; Copyright: (c) 2006, 2016 DL Team
; Website:   https://github.com/sobodash/derlangrisser/
; License:   BSD License <http://opensource.org/licenses/bsd-license.php>
;

lorom

; The game's Elemental class is carried over from Langrisser II, but the unit
; number falls outside the bytes used to toggle acquired units.
;
; Since Demon is never obtained by any commander type and falls within that
; value's bits, we can enable Elemental by cloning it over Demon and changing
; commander units to point accordingly.

; Overwrite Demon with Elemental
org $027142 : db $2a ; Sprite Elemental
org $027143 : db $04 ; Palette Independent
org $06724d : db $01 ; Flying
org $06724f : db $0d ; Undead (Spirit)
org $067251 : db $18 ; AT 24
org $067252 : db $18 ; DF 24
org $067253 : db $07 ; MV 7
org $067254 : db $00 ; Range 0
org $067258 : db $50 ; M.Def 80
org $067259 : db $00 ; Troops
org $06725a : db $14 ; 200P
org $06725b : db $08 ; Money Bounty 8
org $06725c : db $05 ; EXP Bounty
org $06725d : db $0a ; Unit Count 10
org $06725e : db $03 ; Unknown=3

; Next we patch the individual units to use the Demon slot.

; Fix Living Armour class
org $06e8ed : db $9e ; Demon
org $06e8ee : db $b9 ; Skeleton
org $06e8ef : db $00 ; None

; Fix Stone Golem class
org $06ebab : db $8f ; Golem
org $06ebac : db $9e ; Demon
org $06ebad : db $9d ; Spectre

;Fix Jormungand class
org $06ebdf : db $a6 ; Leviathan
org $06ebe0 : db $9e ; Demon
org $06ebe1 : db $bb ; Ogre


