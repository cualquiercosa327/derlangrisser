#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

typedef unsigned char uint8;

uint8 *d0,
      *d1;


int difflen(int index)
{
    int length = 0;
    
    for (int i = index; i < 0x300000; i++) {
        if (d0[i] == d1[i])
            break;
        length++;
    }
    return length > 256 ? 256 : length;
}


int main()
{
    FILE *f0,
         *f1,
         *fp;
    
    f0 = fopen("../resources/dl.rom", "rb");
    f1 = fopen("../build/dl.sfc", "rb");
    
    d0 = (uint8 *) malloc(0x300000);
    d1 = (uint8 *) malloc(0x300000);
    
    memset(d0, 0x00, 0x300000);
    memset(d1, 0x00, 0x300000);
    
    fread(d0, 1, 0x200000, f0);
    fclose(f0);
    
    fread(d1, 1, 0x300000, f1);
    fclose(f1);
    
    // Clear signature from patch data so that it is not stored in patch.bin
    memset(d1 + 0x2ffb00, 0x00, 0x500);
    
    fp = fopen("patch.bin", "wb");
    
    for (int i = 0; i < 0x300000;) {
        if (d0[i] == d1[i]) {
            i++;
            continue;
        }
        
        int length = difflen(i);
        
        fputc(i, fp);
        fputc(i >> 8, fp);
        fputc(i >> 16, fp);
        fputc(length, fp);
        
        for (int l = 0; l < length; l++) {
            fputc(d1[i + l], fp);
        }
        i += length;
    }
    
    free(d0);
    free(d1);
    
    return 0;
}
