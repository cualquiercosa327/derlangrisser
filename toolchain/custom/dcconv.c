#include <stdio.h>

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;

#define ccount 6

word  cnum[ccount] = { 0, 2, 4, 244, 245, 464 };
ulong fsize,
      wsize;
byte  src[0x0800],
      dest[0x2000];
ulong sp,
      dp;
byte  buffer[255],
      bp;


void writecl(byte c, word len)
{
    if (len <= 0x1f) {
        dest[dp++] = c << 5 | (len - 1);
    }
    else {
        dest[dp++] = c << 5 | 0x1f;
        dest[dp++] = len - 1;
    }
}


void compress(byte cp)
{
    byte x,
         zf;
    word zc;
    
    sp = dp = bp = zf = 0;
    
    if (cp != 3) {
        while (sp < fsize) {
            if (!src[sp]) {
                zc = 0;
                while (!src[sp + zc] && ((sp + zc) < fsize) && zc < 255)
                    zc++;
                if (zc > 2) {
                    zf = 1;
                    if (bp) {
                        writecl(4, bp);
                        memcpy(dest + dp, buffer, bp);
                        dp += bp;
                        bp = 0;
                    }
                    writecl(7, zc);
                    sp += zc;
                }
            }
            
            if (!zf)
                buffer[bp++] = src[sp++];
            else
                zf = 0;
            
            if (bp == 255) {
                writecl(4, bp);
                memcpy(dest + dp, buffer, bp);
                dp += bp;
                bp = 0;
            }
        }
    }
    else {
        while (sp < fsize) {
            buffer[bp++] = src[sp++];
            if (bp == 255) {
                writecl(4, bp);
                memcpy(dest + dp, buffer, bp);
                dp += bp;
                bp = 0;
            }
        }
    }
    
    if (bp) {
        writecl(4, bp);
        memcpy(dest + dp, buffer, bp);
        dp += bp;
    }
    
    dest[dp++] = 0x00;
    wsize = dp;
}


int main()
{
    FILE  *fp,
          *wr,
          *rd;
    ulong l,
          x,
          y,
          p;
    char  fn[256];
    
    for (l = 0; l < ccount; l++) {
        sprintf(fn, "build/m%0.3d.bin", cnum[l]);
        fp = fopen(fn, "rb");
        
        sprintf(fn, "build/c%0.3d.bin", cnum[l]);
        wr = fopen(fn, "wb");
        
        fseek(fp, 0, SEEK_END);
        fsize = ftell(fp);
        fseek(fp, 0, SEEK_SET);
        x = fgetc(fp);
        fputc(x, wr);
        
        while (x--) {
            fread(dest, 1, 32, fp);
            fwrite(dest, 1, 32, wr);
        }
        
        y = fgetc(fp);
        fputc(y, wr);
        y = fgetc(fp);
        fputc(y, wr);
        fsize -= ftell(fp);
        fsize >>= 2;
        
        fread(src, 1, fsize, fp);
        compress(0);
        fwrite(dest, 1, wsize, wr);
        
        fread(src, 1, fsize, fp);
        compress(1);
        fwrite(dest, 1, wsize, wr);
        
        fread(src, 1, fsize, fp);
        compress(2);
        fwrite(dest, 1, wsize, wr);
        
        fread(src, 1, fsize, fp);
        compress(3);
        fwrite(dest, 1, wsize, wr);
        
        fclose(fp);
        fclose(wr);
    }
    
    fp = fopen("build/dl.sfc", "rb");
    fseek(fp, 0x40000, SEEK_SET);
    fread(src, 1, 489 * 3, fp);
    fclose(fp);
    
    p = 0x1d0000; // 0x210000-0x040000
    
    for (l = 0; l < ccount; l++) {
        x = cnum[l];
        src[x * 3] = p;
        src[x * 3 + 1] = p >> 8;
        src[x * 3 + 2] = p >> 16;
        
        sprintf(fn, "build/c%0.3d.bin", x);
        
        fp = fopen(fn, "rb");
        fseek(fp, 0, SEEK_END);
        p += ftell(fp);
        fclose(fp);
    }
    
    fp = fopen("build/ctable.bin", "wb");
    fwrite(src, 1, 489 * 3, fp);
    fclose(fp);
}
