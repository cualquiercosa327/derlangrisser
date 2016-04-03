# Der Langrisser Codes

These codes are provided as a reference for use in development. There are lots
of times during testing where it’s useful to be able to skip scenarios or pad
inventory, and using the game’s inbuilt mechanisms is one of the best ways.


## Secret Shop

1. Highlight the word “Buy” in the shop menu.
2. Press LEFT, RIGHT, RIGHT, DOWN, RIGHT, UP, A.

This code allows you to purchase most equipment in the game.


## All Items

1. Highlight the word “Buy” in the shop menu.
2. Press LEFT, UP, RIGHT, DOWN, RIGHT, UP, LEFT, Select, DOWN, RIGHT, LEFT,
   Select, A.

This code takes you to a special shop with all items available for purchase.


## Scenario Select

1. At the loading screen, highlight any scenario save.
2. Press UP, DOWN, UP, DOWN, LEFT, RIGHT, Select, A.

While in-game scenarios are listed as 1-21, the scenario select menu is
numbered according to a scenario’s position in the script index.

A list of scenarios and their corresponding numbers is available in the
scenarios.md table.


## Multimedia Test

1. At the loading screen, highlight any scenario save.
2. Press LEFT, RIGHT, Select, A.

This will enable the Music and Sound Effects tests. A third test, the
Battle Test, is enabled by a patch included in /resources/asm/.


## Debug Mode

1. Highlight an empty square on the map, preferably one that allows the cursor
   some maneuverability.
2. Press DOWN, LEFT, UP, RIGHT, A, LEFT, UP, B, DOWN, RIGHT, A, B, DOWN,
   RIGHT, A.

This code allows you to control any unit, take multiple turns and cast any spell.


## Action Replay Codes

We developed these for beta testing and consider them the "master" debug mode.

These codes allow such impossible actions as stepping to any tile, attacking
any tile, attacking dead enemies, allowing troops to cast magic, allowing units
to summon units, and controlling normally uncontrollable units.

Codes marked (1) and (2) must be used in tandem to achieve their desired effect.

| Memory Patch | Effect                        |
| ------------ | ----------------------------- |
| 03864A:80    | Control any unit (1)          |
| 03864B:2D    | Control any unit (2)          |
| 00E1AB80     | Attack any tile (1)           |
| 00E1AC12     | Attack any tile (2)           |
| 00E3B600     | Cast on any tile (1)          |
| 00E3BB80     | Cast on any tile (2)          |
| 00D53980     | Move to any tile              |
| 038BA900     | Troops can Cast               |
| 038BC900     | Enable all magic (1)          |
| 03921400     | Enable all magic (2)          |
| 0393DC00     | Cast costs 0MP                |
| 0399A100     | Summon costs 0MP              |
| 7EB00200     | Infinite turns - Commander 1  |
| 7EB06200     | Infinite turns - Commander 2  |
| 7EB0C200     | Infinite turns - Commander 3  |
| 7EB12200     | Infinite turns - Commander 4  |
| 7EB18200     | Infinite turns - Commander 5  |
| 7EB1E200     | Infinite turns - Commander 6  |
| 7EB24200     | Infinite turns - Commander 7  |
| 7EB2A200     | Infinite turns - Commander 8  |
| 7EB30200     | Infinite turns - NPC/Enemy 1  |
| 7EB36200     | Infinite turns - NPC/Enemy 2  |
| 7EB3C200     | Infinite turns - NPC/Enemy 3  |
| 7EB42200     | Infinite turns - NPC/Enemy 4  |
| 7EB48200     | Infinite turns - NPC/Enemy 5  |
| 7EB4E200     | Infinite turns - NPC/Enemy 6  |
| 7EB54200     | Infinite turns - NPC/Enemy 7  |
| 7EB5A200     | Infinite turns - NPC/Enemy 8  |
| 7EB60200     | Infinite turns - NPC/Enemy 9  |
| 7EB66200     | Infinite turns - NPC/Enemy 10 |
| 7EB6C200     | Infinite turns - NPC/Enemy 11 |
| 7EB72200     | Infinite turns - NPC/Enemy 12 |
| 7EB78200     | Infinite turns - NPC/Enemy 13 |
| 7EB7E200     | Infinite turns - NPC/Enemy 14 |
| 7EB84200     | Infinite turns - NPC/Enemy 15 |
