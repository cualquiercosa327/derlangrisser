#include <stdio.h>

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;

FILE *fp,
     *wr;
byte data[0x8000];
byte cindex[0xff],
     pindex[0xff],
     paldata[0x86 * 32];


int main()
{
    ulong c,
          i,
          l,
          x,
          z;
    char  fn[256];
    
    fp = fopen("resources/dl.rom", "rb");
    fseek(fp, 0x14d32, SEEK_SET);
    fread(cindex, 1, 255, fp);
    fseek(fp, 0x1c456, SEEK_SET);
    fread(pindex, 1, 255, fp);
    fread(paldata, 1, 0x86 * 32, fp);
    fclose(fp);
    
    for (i = 0; i < 0xff; i++) {
        z = cindex[i] - 1;
        sprintf(fn, "resources/decomp/sprites/d%0.3d.bmp", z + 72);
        wr = fopen(fn, "rb");
        fread(data, 1, 0x436, wr);
        fclose(wr);
        z = pindex[i] << 5;
        for (l = 0; l < 16; l++, z += 2) {
            x = paldata[z] | paldata[z + 1] << 8;
            c = ((x >> 10) & 0x1f) << 3 | ((x >> 5) & 0x1f) << 11 | ((x) & 0x1f) << 19;
            data[0x36 + l * 4] = c;
            data[0x37 + l * 4] = c >> 8;
            data[0x38 + l * 4] = c >> 16;
            data[0x39 + l * 4] = 0;
        }
        // D's Note:
        // I add 1 to z to make the portraits align with their indexes used
        // in the game itself.
        sprintf(fn, "resources/decomp/portraits/p%0.3d.bmp", i + 1);
        wr = fopen(fn, "wb");
        fwrite(data, 1, 0x436, wr);
        fclose(wr);
    }
}
