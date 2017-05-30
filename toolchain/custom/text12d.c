#include <stdio.h>

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;


int main()
{
    FILE  *in,
          *log;
    ulong i,
          pt,
          st,
          lc;
    byte  x;
    
    in = fopen("build/dl.sfc", "rb");
    log = fopen("resources/scripts/sc_log.txt", "w");
    
    for (i = 0; i < 9; i++) {
        lc = 0;
        fprintf(log, "sc%0.2d: ", i);
        fseek(in, 0x120000 + (i * 3), SEEK_SET);
        pt = (fgetc(in) | fgetc(in) << 8 | fgetc(in) << 16) + 0x120000;
        st = (fgetc(in) | fgetc(in) << 8 | fgetc(in) << 16) + 0x120000;
        fseek(in, pt, SEEK_SET);
        while (ftell(in) < st) {
            x = fgetc(in);
            if (!x)
                lc++;
        }
        fprintf(log, "%4d\n", lc);
    }
    fclose(in);
    fclose(log);
}
