#include <stdio.h>
#include <string.h>

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;

#define ccount 6

word cnum[ccount] = {
    0,
    2,
    4,
    244,
    245,
    464
};

char cfn[ccount][256] = {
    "resources/data/font8.bmp",     // 000
    "resources/data/borders.bmp",   // 002
    "resources/data/cursors.bmp",   // 004
    "resources/data/copyright.bmp", // 244
    "resources/data/title.bmp",     // 245
    "resources/data/dasende.bmp"    // 464
};

FILE  *sr,
      *fp,
      *wr;
ulong width,
      height;
byte  src[0x2000],
      dest[0x2000],
      conv[0x2000];

void convert_image2bpp(void)
{
    ulong i,
          l,
          p = 0,
          x,
          y,
          x1,
          y1,
          yf,
          z;
    height <<= 1;
    
    for (y = 0; y < height; y += 8) {
        for (x = 0; x < width; x += 8) {
            for (y1 = 0; y1 < 8; y1++, p++) {
                yf = height - (y + y1) - 1;
                for (x1 = 0; x1 < 8; x1++) {
                    z = src[(yf * width + (x + x1)) >> 1];
                    if (!(x1 & 1))
                        z >>= 4;
                    dest[p] |= ((z & 1) ? 0x80 : 0) >> x1;
                    dest[p + 0x1000] |= ((z & 2) ? 0x80 : 0) >> x1;
                }
            }
        }
    }
    for (i = z = 0; i < 4096; i += 8, z += 16) {
        for (l = 0; l < 8; l++) {
            conv[i + l] = dest[z + l];
            conv[i + l + 0x1000] = dest[z + l + 0x0008];
        }
    }
    memcpy(dest, conv, 0x0400);
    memcpy(dest + 0x0400, conv + 0x0800, 0x0400);
    memcpy(dest + 0x0800, conv + 0x1000, 0x0400);
    memcpy(dest + 0x0c00, conv + 0x1800, 0x0400);
    height >>= 1;
}

void convert_image4bpp(void)
{
    ulong p = 0,
          pl = (width * height) >> 3,
          x,
          y,
          x1,
          y1,
          yf,
          z;
    for (y = 0; y < height; y += 8) {
        for (x = 0; x < width; x += 8) {
            for (y1 = 0; y1 < 8; y1++, p++) {
                yf = height - (y + y1) - 1;
                for (x1 = 0; x1 < 8; x1++) {
                    z = src[(yf * width + (x + x1)) >> 1];
                    if (!(x1 & 1))
                        z >>= 4;
                    dest[p] |= ((z & 0x01) ? 0x80 : 0) >> x1;
                    dest[p + pl] |= ((z & 0x02) ? 0x80 : 0) >> x1;
                    dest[p + pl + pl] |= ((z & 0x04) ? 0x80 : 0) >> x1;
                    dest[p + pl + pl + pl] |=
                    ((z & 0x08) ? 0x80 : 0) >> x1;
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
          x,
          z;
    char  fn[256];
    
    sr = fopen("build/dl.sfc", "rb");
    
    if (!sr) {
        printf("failed to load 'dl.sfc'n");
        return 0;
    }
    for (l = 0; l < ccount; l++) {
        fp = fopen(cfn[l], "rb");
        sprintf(fn, "build/m%0.3d.bin", cnum[l]);
        wr = fopen(fn, "wb");
        fseek(fp, 0, SEEK_END);
        p = ftell(fp) - 0x76;
        fseek(fp, 0x76, SEEK_SET);
        fread(src, 1, p, fp);
        fclose(fp);
        memset(dest, 0, 0x2000);
        fseek(sr, 0x40000 + cnum[l] * 3, SEEK_SET);
        p = fgetc(sr);
        p += fgetc(sr) << 8;
        p += (fgetc(sr) + 0x04) << 16;
        fseek(sr, p, SEEK_SET);
        x = fgetc(sr);
        fputc(x, wr);
        x <<= 5;
        while (x--) {
            fputc(fgetc(sr), wr);
        }
        width = fgetc(sr);
        fputc(width, wr);
        width <<= 3;
        height = fgetc(sr);
        fputc(height, wr);
        height <<= 3;
        if (!l) {
            convert_image2bpp();
        } else {
            convert_image4bpp();
        }
        fwrite(dest, 1, (width * height) >> 1, wr);
        fclose(wr);
    }
    fclose(sr);
}
