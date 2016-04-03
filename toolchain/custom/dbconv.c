#include <stdio.h>

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;

FILE  *sr,
      *fp,
      *tbl;
ulong iw,
      ih;
byte  image[0x2000],
      bpi[0x2000];

byte header[0x36] = {
    0x42, 0x4d, 0x76, 0x10, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x76, 0x00,
    0x00, 0x00, 0x28, 0x00, 0x00, 0x00, 0x80, 0x00, 0x00, 0x00, 0x80, 0x00,
    0x00, 0x00, 0x01, 0x00, 0x04, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x10,
    0x00, 0x00, 0xc4, 0x0e, 0x00, 0x00, 0xc4, 0x0e, 0x00, 0x00, 0x00, 0x00,
    0x00, 0x00, 0x00, 0x00, 0x00, 0x00
};

byte pal_dos[64] = {
    0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xc0, 0x00, 0x00, 0xc0, 0x00, 0x00,
    0x00, 0xc0, 0xc0, 0x00, 0xc0, 0x00, 0x00, 0x00, 0xc0, 0x00, 0xc0, 0x00,
    0xc0, 0xc0, 0x00, 0x00, 0xc0, 0xc0, 0xc0, 0x00, 0x00, 0x00, 0x80, 0x00,
    0x00, 0x00, 0xff, 0x00, 0x00, 0xc0, 0x80, 0x00, 0x00, 0xc0, 0xff, 0x00,
    0xc0, 0x00, 0x80, 0x00, 0xc0, 0x00, 0xff, 0x00, 0xc0, 0xc0, 0x80, 0x00,
    0xc0, 0xc0, 0xff, 0x00
};

byte pal_rom[64];


void convert_image2bpp(void)
{
    ulong p = 0,
          x,
          y,
          x1,
          y1,
          yf,
          z;
    byte  bpc[0x2000];
    
    for (y = z = 0; y < 0x0800; y += 8, z += 16) {
        for (x = 0; x < 8; x++) {
            bpc[x + z + 0x0000] = bpi[x + y + 0x0000];
            bpc[x + z + 0x0008] = bpi[x + y + 0x1000];
            bpc[x + z + 0x1000] = bpi[x + y + 0x0800];
            bpc[x + z + 0x1008] = bpi[x + y + 0x1800];
        }
    }
    
    for (y = 0; y < ih; y += 8) {
        for (x = 0; x < iw; x += 8) {
            for (y1 = 0; y1 < 8; y1++, p++) {
                yf = ih - (y1 + y) - 1;
                for (x1 = 0; x1 < 8; x1++) {
                    z = bpc[p] & (0x80 >> x1) ? 1 : 0;
                    z += bpc[p + 0x1000] & (0x80 >> x1) ? 2 : 0;
                    if (!(x1 & 1))
                        z <<= 4;
                    image[(yf * iw + (x1 + x)) >> 1] |= z;
                }
            }
        }
    }
}


void convert_image4bpp(void)
{
    ulong p = 0,
          x,
          y,
          x1,
          y1,
          yf,
          z;
    
    for (y = 0; y < ih; y += 8) {
        for (x = 0; x < iw; x += 8) {
            for (y1 = 0; y1 < 8; y1++, p++) {
                yf = ih - (y1 + y) - 1;
                for (x1 = 0; x1 < 8; x1++) {
                    z = bpi[p] & (0x80 >> x1) ? 1 : 0;
                    z += bpi[p + 0x0800] & (0x80 >> x1) ? 2 : 0;
                    z += bpi[p + 0x1000] & (0x80 >> x1) ? 4 : 0;
                    z += bpi[p + 0x1800] & (0x80 >> x1) ? 8 : 0;
                    if (!(x1 & 1))
                        z <<= 4;
                    image[(yf * iw + (x1 + x)) >> 1] |= z;
                }
            }
        }
    }
}


int main()
{
    ulong i,
          l,
          p,
          pf,
          x,
          z;
    char  fn[256];
    ulong c;
    
    memset(pal_rom, 0, 64);
    
    sr = fopen("resources/dl.rom", "rb");
    tbl = fopen("resources/decomp/table.bin", "wb");
    
    for (l = 0; l < 488; l++) {
        fseek(sr, 0x40000 + l * 3, SEEK_SET);
        p = fgetc(sr) | fgetc(sr) << 8 | ((fgetc(sr) + 0x04) << 16);
        fseek(sr, p, SEEK_SET);
        x = fgetc(sr);
        if (x) {
            pf = x;
            for (i = 0; i < 16; i++) {
                x = fgetc(sr) | fgetc(sr) << 8;
                pal_rom[i * 4] = ((x >> 10) & 0x1f) << 3;
                pal_rom[i * 4 + 1] = ((x >> 5) & 0x1f) << 3;
                pal_rom[i * 4 + 2] = (x & 0x1f) << 3;
            }
            z = ftell(sr) + ((pf - 1) << 5);
            fseek(sr, z, SEEK_SET);
        }
        else
            pf = 0;
        
        fputc(pf, tbl);
        iw = fgetc(sr) << 3;
        ih = fgetc(sr) << 3;
        
        if (!l)
            ih <<= 1;
            
        header[0x12] = iw;
        header[0x16] = ih;
        memset(image, 0, 0x2000);
        sprintf(fn, "resources/decomp/d%0.3d.bin", l);
        fp = fopen(fn, "rb");
        fread(bpi, 1, 0x2000, fp);
        fclose(fp);
        
        if (!l)
            convert_image2bpp();
        else
            convert_image4bpp();
        
        sprintf(fn, "resources/decomp/sprites/d%0.3d.bmp", l);
        fp = fopen(fn, "wb");
        fwrite(header, 1, 0x36, fp);
        
        if (pf)
            fwrite(pal_rom, 1, 64, fp);
        else
            for (i = 0; i < 64; i += 4) {
                fputc(pal_dos[i + 2], fp);
                fputc(pal_dos[i + 1], fp);
                fputc(pal_dos[i], fp);
                fputc(0, fp);
            }
        
        fwrite(image, 1, iw * ih / 2, fp);
        fclose(fp);
    }
    
    fclose(tbl);
    fclose(sr);
}
