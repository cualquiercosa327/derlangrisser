#include <stdio.h>

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;


int main()
{
    ulong i,
          x = 0x7560;
    
    for (i = 0; i < 18; i++)
        printf(" cmp #$%0.4x : bne $03 : jmp .sr\r\n", x + i * 0x6d);
    
    getch();
}
