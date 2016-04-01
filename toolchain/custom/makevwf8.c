#include "bitlib.c"
#include <stdlib.h>
#include <string.h>


int main()
{
    FILE *fp;
    byte *in = (byte *) malloc(0x100000),
         *out = (byte *) malloc(0x100000);
    
    loadbmp16(in, "resources/data/font8vwf.bmp", 128, 32);
    bmp16tobpp2(out, in, 128, 32, "0123------------");
    fp = fopen("build/font8vwf.bin", "wb");
    fwrite(out, 1, 0x800, fp);
    fclose(fp);
    
    loadbmp16(in, "resources/data/font12.bmp", 128, 512);
    bmp16tobpp2_16x16(out, in, 128, 512, "0123-----------");
    fp = fopen("build/font16.bin", "wb");
    fwrite(out, 1, 0x8000, fp);
    fclose(fp);
}
