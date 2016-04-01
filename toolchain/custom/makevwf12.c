#include "bppconv.c"
#include <stdlib.h>
#include <string.h>

FILE *fp;

typedef struct {
    byte data[64];
} _font;

_font font[256];

ulong __l;
ulong wr_count;
byte  wrdata[0x20000];


void wr(byte z)
{
    int i,
        l,
        x,
        w;
    
    // Skip width detection for green fonts
    if (__l != 1 && __l != 5) {
        for (i = l = 0; i < 32; i += 2) {
            for (x = 0; x < 16; x++) {
                w = font[z].data[i] << 8 | font[z].data[i + 32];
                
                if (w & (0x8000 >> x)) {
                    w = font[z].data[i + 1] << 8 | font[z].data[i + 33];
                    if (!(w & (0x8000 >> x)))
                        if (l < x)
                            l = x;
                }
            }
        }
        
        if (!z)
            wrdata[wr_count] = 4;
        else
            wrdata[wr_count] = l + 1;
    }
    
    memcpy(wrdata + wr_count * 64 + 128, font[z].data, 64);
    wr_count++;
}


int main()
{
    byte  *in = (byte *) malloc(0x20000),
          *out = (byte *) malloc(0x20000);
    ulong i,
          l,
          z;
    int   x,
          y,
          x1,
          y1;
    byte  s,
          p,
          p0,
          p1;
    char  fn[256];
    
    for (l = 0; l <= 5; l++) {
        __l = l;
        sprintf(fn, "resources/data/fontv%d.bmp", l);
        fp = fopen(fn, "rb");
        fseek(fp, 0x76, SEEK_SET);
        fread(in, 1, 0x2800, fp);
        flip_bmp16(in, 256, 80);
        fclose(fp);
        for (i = 0; i < 256; i++)
            for (x = 0; x < 64; x++)
                font[i].data[x] = 0;
        z = 0;
        for (y = 0; y < 80; y += 16) {
            for (x = 0; x < 256; x += 16) {
                for (y1 = 0; y1 < 13; y1++) {
                    for (x1 = 0; x1 < 8; x1++) {
                        p = in[((y + y1) * 256 + (x + x1)) >> 1];
                        if (!(x1 & 1))
                            p >>= 4;
                        font[z].data[(y1 << 1)] |= ((p & 1) ? 1 : 0) << (7 - x1);
                        font[z].data[(y1 << 1) + 1] |= ((p & 2) ? 1 : 0) << (7 - x1);
                    }
                    for (x1 = 0; x1 < 8; x1++) {
                        p = in[((y + y1) * 256 + (x + x1 + 8)) >> 1];
                        if (!(x1 & 1))
                            p >>= 4;
                        font[z].data[(y1 << 1) + 32] |= ((p & 1) ? 1 : 0) << (7 - x1);
                        font[z].data[(y1 << 1) + 33] |= ((p & 2) ? 1 : 0) << (7 - x1);
                    }
                }
                z++;
            }
        }
        sprintf(fn, "build/fontv%d.bin", l);
        fp = fopen(fn, "wb");
        wr_count = 0;
        
        for (i = 0; i < 80; i++)
            wr(i);
        
        sprintf(fn, "build/fontv%d.bin", l);
        fp = fopen(fn, "wb");
        fwrite(wrdata, 1, wr_count * 64 + 128, fp);
        fclose(fp);
    }
    
    free(in);
    free(out);
    return 0;
}
