#ifndef __hdr
    #define __hdr
    #include <stdio.h>
    
    typedef unsigned char byte;
    typedef unsigned short word;
    typedef unsigned long ulong;
#endif

#define fputb(x,fp) fputc(x,fp)
#define fputw(x,fp) fputc(x,fp); fputc(x>>8,fp)
#define fputl(x,fp) fputc(x,fp); fputc(x>>8,fp); fputc(x>>16,fp)
#define fputd(x,fp) fputc(x,fp); fputc(x>>8,fp); fputc(x>>16,fp); fputc(x>>24,fp)


void bpp1_bmp16(byte * in, byte * out, ulong width, ulong height)
{
    ulong x,
          y,
          x1,
          y1;
    byte  z,
          s;
    byte  *rd = (byte *) (in);
    
    for (y = 0; y < height; y += 16) {
        for (x = 0; x < width; x += 16) {
            for (y1 = 0; y1 < 8; y1++) {
                z = *rd++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1) * width + (x + x1)) >> 1] =
                        ((z >> s) & 1) << 4 | ((z >> (s - 1)) & 1);
            }
            for (y1 = 0; y1 < 8; y1++) {
                z = *rd++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1) * width + (x + x1 + 8)) >> 1] =
                        ((z >> s) & 1) << 4 | ((z >> (s - 1)) & 1);
            }
            for (y1 = 0; y1 < 8; y1++) {
                z = *rd++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1 + 8) * width + (x + x1)) >> 1] =
                        ((z >> s) & 1) << 4 | ((z >> (s - 1)) & 1);
            }
            for (y1 = 0; y1 < 8; y1++) {
                z = *rd++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1 + 8) * width + (x + x1 + 8)) >> 1] =
                        ((z >> s) & 1) << 4 | ((z >> (s - 1)) & 1);
            }
        }
    }
}


void bpp2_bmp16(byte * in, byte * out, ulong ps, ulong width, ulong height)
{
    ulong x,
          y,
          x1,
          y1;
    byte  z0,
          z1,
          s;
    byte  *rd0 = (byte *) (in),
    
    *rd1 = (byte *) (in + ps);
    
    for (y = 0; y < height; y += 8) {
        for (x = 0; x < width; x += 8) {
            for (y1 = 0; y1 < 8; y1++) {
                z0 = *rd0++;
                z1 = *rd1++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1) * width + (x + x1)) >> 1] =
                        ((z0 >> (s - 0)) & 1) << 4 | ((z1 >> (s - 0)) & 1)
                        << 5 | ((z0 >> (s - 1)) & 1) << 0 |
                        ((z1 >> (s - 1)) & 1) << 1;
            }
        }
    }
}


void bpp2_bmp16_16x16(byte * in, byte * out, ulong width, ulong height)
{
    ulong x,
          y,
          x1,
          y1;
    byte  z0,
          z1,
          s;
    
    byte  *rd = (byte *) (in);
    
    for (y = 0; y < height; y += 16) {
        for (x = 0; x < width; x += 16) {
            for (y1 = 0; y1 < 8; y1++) {
                z0 = *rd++;
                z1 = *rd++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1) * width + (x + x1)) >> 1] =
                        (((z0 >> s) & 1) << 4 | ((z0 >> (s - 1)) & 1)) |
                        ((((z1 >> s) & 1) << 4 | ((z1 >> (s - 1)) & 1)) <<
                         1);
            }
            for (y1 = 0; y1 < 8; y1++) {
                z0 = *rd++;
                z1 = *rd++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1) * width + (x + x1 + 8)) >> 1] =
                        (((z0 >> s) & 1) << 4 | ((z0 >> (s - 1)) & 1)) |
                        ((((z1 >> s) & 1) << 4 | ((z1 >> (s - 1)) & 1)) <<
                         1);
            }
            for (y1 = 0; y1 < 8; y1++) {
                z0 = *rd++;
                z1 = *rd++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1 + 8) * width + (x + x1)) >> 1] =
                        (((z0 >> s) & 1) << 4 | ((z0 >> (s - 1)) & 1)) |
                        ((((z1 >> s) & 1) << 4 | ((z1 >> (s - 1)) & 1)) <<
                         1);
            }
            for (y1 = 0; y1 < 8; y1++) {
                z0 = *rd++;
                z1 = *rd++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1 + 8) * width + (x + x1 + 8)) >> 1] =
                        (((z0 >> s) & 1) << 4 | ((z0 >> (s - 1)) & 1)) |
                        ((((z1 >> s) & 1) << 4 | ((z1 >> (s - 1)) & 1)) <<
                         1);
            }
        }
    }
}


void bpp4_bmp16(byte * in, byte * out, ulong ps, ulong width, ulong height)
{
    ulong x,
          y,
          x1,
          y1;
    byte  z0,
          z1,
          z2,
          z3,
          s;
    byte  *rd0 = (byte *) (in),
          *rd1 = (byte *) (in + ps),
          *rd2 = (byte *) (in + ps + ps),
          *rd3 = (byte *) (in + ps + ps + ps);
    
    for (y = 0; y < height; y += 8) {
        for (x = 0; x < width; x += 8) {
            for (y1 = 0; y1 < 8; y1++) {
                z0 = *rd0++;
                z1 = *rd1++;
                z2 = *rd2++;
                z3 = *rd3++;
                for (x1 = 0, s = 7; x1 < 8; x1 += 2, s -= 2)
                    out[((y + y1) * width + (x + x1)) >> 1] =
                        ((z0 >> (s - 0)) & 1) << 4 | ((z1 >> (s - 0)) & 1)
                        << 5 | ((z2 >> (s - 0)) & 1) << 6 |
                        ((z3 >> (s - 0)) & 1) << 7 | ((z0 >> (s - 1)) & 1)
                        << 0 | ((z1 >> (s - 1)) & 1) << 1 |
                        ((z2 >> (s - 1)) & 1) << 2 | ((z3 >> (s - 1)) & 1)
                        << 3;
            }
        }
    }
}


void bmp16_bpp2(byte * in, byte * out, ulong ps, ulong width, ulong height)
{
    ulong x,
          y,
          yf,
          x1,
          y1;
    byte  z;
    byte  *wr0 = (byte *) (out),
    
    *wr1 = (byte *) (out + ps);
    
    for (y = 0; y < height; y += 8) {
        for (x = 0; x < width; x += 8) {
            for (y1 = 0; y1 < 8; y1++) {
                *wr0 = *wr1 = 0;
                for (x1 = 0; x1 < 8; x1 += 2) {
                    z = in[((y + y1) * width + (x + x1)) >> 1];
                    *wr0 |= ((z >> 4) & 1) << (7 - x1);
                    *wr1 |= ((z >> 5) & 1) << (7 - x1);
                    *wr0 |= ((z >> 0) & 1) << (6 - x1);
                    *wr1 |= ((z >> 1) & 1) << (6 - x1);
                }
                wr0++;
                wr1++;
            }
        }
    }
}


void _bmp16_bpp2(byte * in, byte * out, ulong width, ulong height)
{
    ulong x,
          y,
          yf,
          x1,
          y1;
    byte  z;
    byte  *wr0 = (byte *) (out),
          *wr1 = (byte *) (out + 1);
    
    for (y = 0; y < height; y += 8) {
        for (x = 0; x < width; x += 8) {
            for (y1 = 0; y1 < 8; y1++) {
                *wr0 = *wr1 = 0;
                for (x1 = 0; x1 < 8; x1 += 2) {
                    z = in[((y + y1) * width + (x + x1)) >> 1];
                    *wr0 |= ((z >> 4) & 1) << (7 - x1);
                    *wr1 |= ((z >> 5) & 1) << (7 - x1);
                    *wr0 |= ((z >> 0) & 1) << (6 - x1);
                    *wr1 |= ((z >> 1) & 1) << (6 - x1);
                }
                wr0 += 2;
                wr1 += 2;
            }
        }
    }
}


void bmp16_bpp2_16x16(byte * in, byte * out, ulong width, ulong height)
{
    ulong x,
          y,
          yf,
          x1,
          y1;
    byte  z;
    byte  *wr = (byte *) (out);
    
    for (y = 0; y < height; y += 16) {
        for (x = 0; x < width; x += 8) {
            for (y1 = 0; y1 < 8; y1++) {
                for (x1 = 0; x1 < 8; x1 += 2) {
                    z = in[((y + y1) * width + (x + x1)) >> 1];
                    *wr |= ((z >> 4) & 1) << (7 - x1);
                    *wr |= ((z >> 0) & 1) << (6 - x1);
                }
                wr += 2;
            }
            for (y1 = 0; y1 < 8; y1++) {
                for (x1 = 0; x1 < 8; x1 += 2) {
                    z = in[((y + y1 + 8) * width + (x + x1)) >> 1];
                    *wr |= ((z >> 4) & 1) << (7 - x1);
                    *wr |= ((z >> 0) & 1) << (6 - x1);
                }
                wr += 2;
            }
        }
    }
}


void bmp16_bpp4(byte * in, byte * out, ulong ps, ulong width, ulong height)
{
    ulong x,
          y,
          yf,
          x1,
          y1;
    byte  z;
    byte  *wr0 = (byte *) (out),
          *wr1 = (byte *) (out + ps),
          *wr2 = (byte *) (out + ps + ps),
          *wr3 = (byte *) (out + ps + ps + ps);
    
    for (y = 0; y < height; y += 8) {
        for (x = 0; x < width; x += 8) {
            for (y1 = 0; y1 < 8; y1++) {
                *wr0 = *wr1 = *wr2 = *wr3 = 0;
                for (x1 = 0; x1 < 8; x1 += 2) {
                    z = in[((y + y1) * width + (x + x1)) >> 1];
                    *wr0 |= ((z >> 4) & 1) << (7 - x1);
                    *wr1 |= ((z >> 5) & 1) << (7 - x1);
                    *wr2 |= ((z >> 6) & 1) << (7 - x1);
                    *wr3 |= ((z >> 7) & 1) << (7 - x1);
                    *wr0 |= ((z >> 0) & 1) << (6 - x1);
                    *wr1 |= ((z >> 1) & 1) << (6 - x1);
                    *wr2 |= ((z >> 2) & 1) << (6 - x1);
                    *wr3 |= ((z >> 3) & 1) << (6 - x1);
                }
                wr0++;
                wr1++;
                wr2++;
                wr3++;
            }
        }
    }
}


void _bmp16_bpp4(byte * in, byte * out, ulong width, ulong height)
{
    ulong x,
          y,
          yf,
          x1,
          y1;
    byte  z;
    byte  *wr0,
          *wr1,
          *wr2,
          *wr3;
    
    memset(out, 0, width * height / 2);
    
    for (y = 0; y < height; y += 8) {
        for (x = 0; x < width; x += 8) {
            wr0 = (byte *) (out);
            wr1 = (byte *) (out + 1);
            wr2 = (byte *) (out + 16);
            wr3 = (byte *) (out + 17);
            for (y1 = 0; y1 < 8; y1++) {
                for (x1 = 0; x1 < 8; x1 += 2) {
                    z = in[((y + y1) * width + (x + x1)) >> 1];
                    *wr0 |= ((z >> 4) & 1) << (7 - x1);
                    *wr1 |= ((z >> 5) & 1) << (7 - x1);
                    *wr2 |= ((z >> 6) & 1) << (7 - x1);
                    *wr3 |= ((z >> 7) & 1) << (7 - x1);
                    *wr0 |= ((z >> 0) & 1) << (6 - x1);
                    *wr1 |= ((z >> 1) & 1) << (6 - x1);
                    *wr2 |= ((z >> 2) & 1) << (6 - x1);
                    *wr3 |= ((z >> 3) & 1) << (6 - x1);
                }
                wr0 += 2;
                wr1 += 2;
                wr2 += 2;
                wr3 += 2;
            }
            out += 32;
        }
    }
}


void flip_bmp16(byte * data, ulong width, ulong height)
{
    byte  l[4096];
    ulong x,
          y0,
          y1;
    byte  b;
    
    width >>= 1;
    
    for (y0 = 0, y1 = height - 1; y0 < height; y0 += 2, y1 -= 2) {
        for (x = 0; x < width; x++) {
            b = data[y0 * width + x];
            data[y0 * width + x] = data[y1 * width + x];
            data[y1 * width + x] = b;
        }
    }
}


void flip_bmp24b(byte * data, ulong width, ulong height)
{
    byte  l[4096];
    ulong x,
          y0,
          y1;
    byte  b;
    
    width *= 3;
    
    for (y0 = 0, y1 = height - 1; y0 < height; y0 += 2, y1 -= 2) {
        for (x = 0; x < width;) {
            b = data[y0 * width + x];
            data[y0 * width + x] = data[y1 * width + x];
            data[y1 * width + x] = b;
            x++;
            
            b = data[y0 * width + x];
            data[y0 * width + x] = data[y1 * width + x];
            data[y1 * width + x] = b;
            x++;
            
            b = data[y0 * width + x];
            data[y0 * width + x] = data[y1 * width + x];
            data[y1 * width + x] = b;
            x++;
        }
    }
}


void write_bmp16(char *fn, byte * data, byte * pal, ulong width, ulong height)
{
    FILE  *fp = fopen(fn, "wb");
    ulong x = 0x76 + ((width * height) >> 1),
          z = 0,
          y;
    
    fputb('B', fp);
    fputb('M', fp);
    fputd(x, fp);
    fputd(0, fp);
    fputd(0x76, fp);
    fputd(40, fp);
    fputd(width, fp);
    fputd(height, fp);
    fputw(1, fp);
    fputw(4, fp);
    fputd(0, fp);
    fputd(0, fp);
    fputd(0, fp);
    fputd(0, fp);
    fputd(0, fp);
    fputd(0, fp);
    
    for (x = 0; x < 16; x++) {
        fputb(pal[z + 2], fp);
        fputb(pal[z + 1], fp);
        fputb(pal[z + 0], fp);
        fputb(0, fp);
        z += 3;
    }
    
    flip_bmp16(data, width, height);
    
    for (x = 0; x < (width * height) >> 1; x++)
        fputc(data[x], fp);
    
    fclose(fp);
}
