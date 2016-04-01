#include <stdio.h>

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;


int main()
{
    FILE  *in = fopen("resources/dl.rom", "rb");
    FILE  *out = fopen("resources/scripts/text8c.txt", "wb");
    ulong i;
    word  ptr;
    byte  x;
    
    fseek(in, 0x022187, SEEK_SET);
    
    for (i = 0; i < 0x01fe; i += 2) {
        fseek(in, 0x022187 + i, SEEK_SET);
        ptr = fgetc(in) | fgetc(in) << 8;
        fseek(in, ptr + 0x018000, SEEK_SET);
        while (!feof(in)) {
            x = fgetc(in);
            if (x == 0xff)
                break;
            fputc(x, out);
        }
        fputc(0x0d, out);
        fputc(0x0a, out);
    }
    
    fclose(out);
    
    out = fopen("resources/scripts/text8n.txt", "wb");
    
    for (i = 0x0000; i < 0x014e; i += 2) {
        fseek(in, 0x03e203 + i, SEEK_SET);
        ptr = fgetc(in) | fgetc(in) << 8;
        fseek(in, ptr + 0x030000, SEEK_SET);
        while (!feof(in)) {
            x = fgetc(in);
            if (x == 0xff)
                break;
            fputc(x, out);
        }
        fputc(0x0d, out);
        fputc(0x0a, out);
    }
    
    fclose(in);
    fclose(out);
}
