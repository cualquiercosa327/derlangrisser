#include "bitlib.c"

void flip_bmp256(char *data, ulong width, ulong height)
{
    int x,
        y,
        ry,
        z0,
        z1;
    
    for (y = 0, ry = height - 1; y < height >> 1; y++, ry--) {
        for (x = 0; x < width; x++) {
            z0 = data[y * width + x];
            z1 = data[ry * width + x];
            data[ry * width + x] = z0;
            data[y * width + x] = z1;
        }
    }
}

int main()
{
    FILE *in,
         *out;
    char fn[256];
    int  i;
    byte r,
         g,
         b;
    word c;
    byte data[0xe000],
         conv[0xe000];
	
    in = fopen("resources/data/intro1.bmp", "rb");
    out = fopen("build/intro1.bin", "wb");

    fseek(in, 0x36, SEEK_SET);
    
    for (i = 0; i < 256; i++) {
        b = fgetc(in) >> 3;
        g = fgetc(in) >> 3;
        r = fgetc(in) >> 3;
        fgetc(in);
        c = b << 10 | g << 5 | r;
        fputc(c, out);
        fputc(c >> 8, out);
    }
    
    fread(data, 1, 0xe000, in);
    fclose(in);

    flip_bmp256(data, 256, 224);
    bmp256tobpp8(conv, data, 256, 224);
    
    fwrite(conv, 1, 0xe000, out);
    fclose(out);

    in = fopen("resources/data/intro2.bmp", "rb");
    out = fopen("build/intro2.bin", "wb");

    fseek(in, 0x36, SEEK_SET);
    for (i = 0; i < 256; i++) {
        b = fgetc(in) >> 3;
        g = fgetc(in) >> 3;
        r = fgetc(in) >> 3;
        fgetc(in);
        c = b << 10 | g << 5 | r;
        fputc(c, out);
        fputc(c >> 8, out);
    }
    fread(data, 1, 0xe000, in);
    fclose(in);

    flip_bmp256(data, 256, 224);
    bmp256tobpp8(conv, data, 256, 224);
    
    fwrite(conv, 1, 0xe000, out);
    fclose(out);
}
