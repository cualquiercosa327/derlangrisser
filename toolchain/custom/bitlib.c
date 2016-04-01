#ifndef __hdr
    #define __hdr
    #include <stdio.h>
    #include <string.h>

    typedef unsigned char byte;
    typedef unsigned short word;
    typedef unsigned long ulong;
#endif

ulong __x;

#define transtobin(s) \
    for(__x = 0; __x < 16; __x++) { \
        if(s[__x] >= '0' && s[__x] <= '9') \
            ts[__x] = s[__x] - '0'; \
        else if(s[__x] >= 'a' && s[__x] <= 'f') \
            ts[__x] = s[__x] - 'a' + 0x0a; \
        else ts[__x] = 0; \
    }

void bmp16tobpp1(byte * out, byte * in, ulong width, ulong height, char *trans)
{
    ulong x,
          y,
          x1,
          y1,
          r0,
          r1,
          z0,
          z1,
          p = 0;
    byte  ts[16];
    
    memset(out, 0, width * height / 8);
    transtobin(trans)
    
    for (y1 = 0; y1 < height; y1 += 8) {
        for (x1 = 0; x1 < width; x1 += 8) {
            for (y = 0; y < 8; y++) {
                for (x = 0; x < 8; x += 2) {
                    r0 = in[((y1 + y) * width + (x1 + x)) / 2];
                    r1 = ts[r0 & 0x0f];
                    r0 = ts[r0 >> 4];
                    if (r0 & 1)
                        out[p] |= 0x80 >> x;
                    if (r1 & 1)
                        out[p] |= 0x40 >> x;
                }
                p++;
            }
        }
    }
}

void bmp16tobpp2(byte * out, byte * in, ulong width, ulong height, char *trans)
{
    ulong x,
          y,
          x1,
          y1,
          r0,
          r1,
          z0,
          z1,
          p = 0;
    byte  ts[16];
    
    memset(out, 0, width * height / 4);
    transtobin(trans)
    
    for (y1 = 0; y1 < height; y1 += 8) {
        for (x1 = 0; x1 < width; x1 += 8) {
            for (y = 0; y < 8; y++) {
                for (x = 0; x < 8; x += 2) {
                    r0 = in[((y1 + y) * width + (x1 + x)) / 2];
                    r1 = ts[r0 & 0x0f];
                    r0 = ts[r0 >> 4];
                    if (r0 & 1)
                        out[p] |= 0x80 >> x;
                    if (r0 & 2)
                        out[p + 1] |= 0x80 >> x;
                    if (r1 & 1)
                        out[p] |= 0x40 >> x;
                    if (r1 & 2)
                        out[p + 1] |= 0x40 >> x;
                }
                p += 2;
            }
        }
    }
}

void bmp16tobpp4(byte * out, byte * in, ulong width, ulong height, char *trans)
{
    ulong x,
          y,
          x1,
          y1,
          r0,
          r1,
          z0,
          z1,
          p = 0;
    byte  ts[16];

    memset(out, 0, width * height / 2);
    transtobin(trans)

    for (y1 = 0; y1 < height; y1 += 8) {
        for (x1 = 0; x1 < width; x1 += 8) {
            for (y = 0; y < 8; y++) {
                for (x = 0; x < 8; x += 2) {
                    r0 = in[((y1 + y) * width + (x1 + x)) / 2];
                    r1 = ts[r0 & 0x0f];
                    r0 = ts[r0 >> 4];
                    if (r0 & 1)
                        out[p] |= 0x80 >> x;
                    if (r0 & 2)
                        out[p + 1] |= 0x80 >> x;
                    if (r0 & 4)
                        out[p + 16] |= 0x80 >> x;
                    if (r0 & 8)
                        out[p + 17] |= 0x80 >> x;
                    if (r1 & 1)
                        out[p] |= 0x40 >> x;
                    if (r1 & 2)
                        out[p + 1] |= 0x40 >> x;
                    if (r1 & 4)
                        out[p + 16] |= 0x40 >> x;
                    if (r1 & 8)
                        out[p + 17] |= 0x40 >> x;
                }
                p += 2;
            }
            p += 16;
        }
    }
}

void bmp256tobpp8(byte * out, byte * in, ulong width, ulong height)
{
    ulong x,
          y,
          x1,
          y1,
          p = 0;
    byte  c;

    memset(out, 0, width * height);

    for (y1 = 0; y1 < height; y1 += 8) {
        for (x1 = 0; x1 < width; x1 += 8) {
            for (y = 0; y < 8; y++) {
                for (x = 0; x < 8; x++) {
                    c = in[(y1 + y) * width + (x1 + x)];
                    if (c & 0x01)
                        out[p] |= 0x80 >> x;
                    if (c & 0x02)
                        out[p + 1] |= 0x80 >> x;
                    if (c & 0x04)
                        out[p + 16] |= 0x80 >> x;
                    if (c & 0x08)
                        out[p + 17] |= 0x80 >> x;
                    if (c & 0x10)
                        out[p + 32] |= 0x80 >> x;
                    if (c & 0x20)
                        out[p + 33] |= 0x80 >> x;
                    if (c & 0x40)
                        out[p + 48] |= 0x80 >> x;
                    if (c & 0x80)
                        out[p + 49] |= 0x80 >> x;
                }
                p += 2;
            }
            p += 48;
        }
    }
}

void bmp16tobpp2_16x16(byte * out, byte * in, ulong width, ulong height, char *trans)
{
    ulong x,
          y,
          x1,
          y1,
          r0,
          r1,
          z0,
          z1,
          p = 0;
    byte  ts[16];
    
    memset(out, 0, width * height / 4);
    transtobin(trans)
    
    for (y1 = 0; y1 < height; y1 += 16) {
        for (x1 = 0; x1 < width; x1 += 8) {
            for (y = 0; y < 8; y++) {
                for (x = 0; x < 8; x += 2) {
                    r0 = in[((y1 + y) * width + (x1 + x)) / 2];
                    r1 = ts[r0 & 0x0f];
                    r0 = ts[r0 >> 4];
                    if (r0 & 1)
                        out[p] |= 0x80 >> x;
                    if (r0 & 2)
                        out[p + 1] |= 0x80 >> x;
                    if (r1 & 1)
                        out[p] |= 0x40 >> x;
                    if (r1 & 2)
                        out[p + 1] |= 0x40 >> x;
                }
                p += 2;
            }
            for (y = 0; y < 8; y++) {
                for (x = 0; x < 8; x += 2) {
                    r0 = in[((y1 + y + 8) * width + (x1 + x)) / 2];
                    r1 = ts[r0 & 0x0f];
                    r0 = ts[r0 >> 4];
                    if (r0 & 1)
                        out[p] |= 0x80 >> x;
                    if (r0 & 2)
                        out[p + 1] |= 0x80 >> x;
                    if (r1 & 1)
                        out[p] |= 0x40 >> x;
                    if (r1 & 2)
                        out[p + 1] |= 0x40 >> x;
                }
                p += 2;
            }
        }
    }
}

void bpp4tobmp16(byte * out, byte * in, ulong width, ulong height, char *trans)
{
    ulong x,
          y,
          x1,
          y1,
          r0,
          r1,
          z0,
          z1,
          p = 0;
    byte  ts[16];
    
    memset(out, 0, width * height / 2);
    transtobin(trans)
    
    for (y1 = 0; y1 < height; y1 += 8) {
        for (x1 = 0; x1 < width; x1 += 8) {
            for (y = 0; y < 8; y++) {
                for (x = 0; x < 8; x += 2) {
                    r0 = 0;
                    if (in[p] & (0x80 >> x))
                        r0 |= 1;
                    if (in[p + 1] & (0x80 >> x))
                        r0 |= 2;
                    if (in[p + 16] & (0x80 >> x))
                        r0 |= 4;
                    if (in[p + 17] & (0x80 >> x))
                        r0 |= 8;
                        
                    r0 = ts[r0];
                    r1 = 0;
                    
                    if (in[p] & (0x40 >> x))
                        r1 |= 1;
                    if (in[p + 1] & (0x40 >> x))
                        r1 |= 2;
                    if (in[p + 16] & (0x40 >> x))
                        r1 |= 4;
                    if (in[p + 17] & (0x40 >> x))
                        r1 |= 8;

                    r1 = ts[r1];
                    out[((y1 + y) * width + (x1 + x)) / 2] = (r0 << 4) | r1;
                }
                p += 2;
            }
            p += 16;
        }
    }
}

void loadbmp16(byte * out, char *fn, ulong width, ulong height)
{
    FILE  *fp;
    ulong y,
          y1;
    ulong z;
    
    fp = fopen(fn, "rb");
    
    if (!fp) {
        printf("bitlib error: [%s] not foundn", fn);
        return;
    }
    
    for (y = 0, y1 = height - 1; y < height; y++, y1--) {
        fseek(fp, 0x76 + y1 * width / 2, SEEK_SET);
        fread(out + y * width / 2, 1, width / 2, fp);
    }
    
    fclose(fp);
}

void savebmp16(byte * out, char *fn, ulong width, ulong height, ulong * pal)
{
    FILE  *fp;
    ulong y,
          y1;
    ulong z;
    byte  hdr[0x76] = {
        0x42, 0x4d, 0x76, 0x10, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x76,
        0x00, 0x00, 0x00, 0x28, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
        0x00, 0x00, 0x00, 0x00, 0x01, 0x00, 0x04, 0x00, 0x00, 0x00, 0x00,
        0x00, 0x00, 0x10, 0x00, 0x00, 0xc4, 0x0e, 0x00, 0x00, 0xc4, 0x0e,
        0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
        0x00, 0x00, 0x00, 0xc0, 0x00, 0x00, 0x00, 0x00, 0xc0, 0x00, 0x00,
        0xc0, 0xc0, 0x00, 0x00, 0x00, 0x00, 0xc0, 0x00, 0xc0, 0x00, 0xc0,
        0x00, 0x00, 0xc0, 0xc0, 0x00, 0xc0, 0xc0, 0xc0, 0x00, 0x80, 0x00,
        0x00, 0x00, 0xff, 0x00, 0x00, 0x00, 0x80, 0xc0, 0x00, 0x00, 0xff,
        0xc0, 0x00, 0x00, 0x80, 0x00, 0xc0, 0x00, 0xff, 0x00, 0xc0, 0x00,
        0x80, 0xc0, 0xc0, 0x00, 0xff, 0xc0, 0xc0, 0x00
    };
    
    fp = fopen(fn, "wb");
    
    hdr[0x12] = width;
    hdr[0x13] = width >> 8;
    hdr[0x16] = height;
    hdr[0x17] = height >> 8;
    
    if (!pal) {
        fwrite(hdr, 1, 0x76, fp);
    } else {
        fwrite(hdr, 1, 0x36, fp);
        fwrite(pal, 1, 0x40, fp);
    }
    
    for (y = 0, y1 = height - 1; y < height; y++, y1--) {
        fwrite(out + y1 * width / 2, 1, width / 2, fp);
    }
    
    fclose(fp);
}
