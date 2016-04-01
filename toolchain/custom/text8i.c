#include <stdio.h>

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;


int main()
{
    FILE  *in,
          *out,
          *ptr;
    ulong i,
          ptr_offset;
    byte  x;
    
    in = fopen("resources/scripts/text8ce.txt", "rb");
    out = fopen("build/text8ce.bin", "wb");
    ptr = fopen("build/text8ce.ptr", "wb");
    
    for (i = 0; i < 0x01fe; i += 2) {
        // address hardcoded by text_a.asm
        ptr_offset = 0x8200 + ftell(out);
        
        fputc(ptr_offset, ptr);
        fputc(ptr_offset >> 8, ptr);
        while (!feof(in)) {
            x = fgetc(in);
            if (x == '\r')
                break;
            if (x >= 'A' && x <= 'Z')
                fputc(x - 'A' + 0xb1, out);
            if (x >= 'a' && x <= 's')
                fputc(x - 'a' + 0xcb, out);
            if (x >= 't' && x <= 'z')
                fputc(x - 't' + 0xa7, out);
            if (x == ' ')
                fputc(0xaf, out);
        }
        fgetc(in);
        fputc(0xff, out);
    }
    fclose(in);
    fclose(out);
    fclose(ptr);
    
    in = fopen("resources/scripts/text8ne.txt", "rb");
    out = fopen("build/text8ne.bin", "wb");
    ptr = fopen("build/text8ne.ptr", "wb");
    
    for (i = 0; i < 0x014e; i += 2) {
        // Address hardcoded by text_a.asm
        ptr_offset = 0xc200 + ftell(out);
        
        fputc(ptr_offset, ptr);
        fputc(ptr_offset >> 8, ptr);
        while (!feof(in)) {
            x = fgetc(in);
            if (x == '\r')
                break;
            if (x >= 'A' && x <= 'Z')
                fputc(x - 'A' + 0xb1, out);
            if (x >= 'a' && x <= 's')
                fputc(x - 'a' + 0xcb, out);
            if (x >= 't' && x <= 'z')
                fputc(x - 't' + 0xa7, out);
            if (x == ' ')
                fputc(0xaf, out);
        }
        fgetc(in);
        fputc(0xff, out);
    }
    fclose(in);
    fclose(out);
    fclose(ptr);
}
