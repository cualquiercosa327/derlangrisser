#include <stdio.h>
#include <stdlib.h>
#include <string.h>

typedef unsigned char uint8;
uint8 *crypt;


void encrypt(uint8 * data, int offset, int length)
{
    int i;
    
    for (i = 0; i < length; i++) {
        data[i] += offset + i;
        data[i] ^= crypt[(offset + i) & 0x7fff];
    }
}


int main(int argc, char *argv[])
{
    FILE  *fp,
          *rd;
    int   i,
          offset,
          fsize;
    char  t[256];
    uint8 *data;
    uint8 padcrypt[14] = { 'D', 'e', 'r', ' ', 'L', 'a', 'n', 'g', 'r', 'i', 's', 's', 'e', 'r' };
    
    if (argc != 2) {
        printf("usage: text12ins <dl.sfc>\n");
        return 0;
    }
    
    fp = fopen("build/dl.xpc", "rb");
    
    offset = fgetc(fp);
    offset += fgetc(fp) << 8;
    offset -= 0x8000;
    offset += 0x200000;
    fclose(fp);
    
    fp = fopen(argv[1], "rb+");
    
    fseek(fp, offset, SEEK_SET);
    
    while (offset++ < 0x208000) {
        i = offset % 14;
        i = (padcrypt[i] ^ (offset & 0xff)) + ((offset & 0xaa) >> 1) +
            ((offset & 0x55) << 1) + (offset % 473) + (offset % 11) +
            (offset % 3);
        fputc(~i, fp);
    }
    
    crypt = (uint8 *) malloc(0x8000);
    fseek(fp, 0x200000, SEEK_SET); // $408000
    fread(crypt, 1, 0x8000, fp);

    rd = fopen("build/sc.bin", "rb");
    fseek(rd, 0, SEEK_END);
    fsize = ftell(rd);

    fseek(rd, 0, SEEK_SET);
    data = (uint8 *) malloc(fsize);
    fread(data, 1, fsize, rd);
    fclose(rd);

    fseek(fp, 0x120000, SEEK_SET); // $248000
    fwrite(data, 1, fsize, fp);
    free(data);

    offset = 0x220000; // $448000

    for (i = 0; i <= 93; i++) {
        sprintf(t, "build/sc%0.2d.bin", i);
        
        rd = fopen(t, "rb");
        fseek(rd, 0, SEEK_END);
        fsize = ftell(rd);
        
        fseek(rd, 0, SEEK_SET);
        data = (uint8 *) malloc(fsize);
        fread(data, 1, fsize, rd);
        fclose(rd);
        
        fseek(fp, offset, SEEK_SET);
        // encrypt(data, offset, fsize);
        fwrite(data, 1, fsize, fp);
        free(data);
        offset += fsize;
    }
    
    for (i = 0; i < 2; i++) {
        if (i == 0) {
            offset = 0x128000;
        } // $258000
        if (i == 1) {
            offset = 0x138000;
        } // $278000
        
        sprintf(t, "build/intro%d.bin", i + 1);
        
        rd = fopen(t, "rb");
        fseek(rd, 0, SEEK_END);
        fsize = ftell(rd);
        
        fseek(rd, 0, SEEK_SET);
        data = (uint8 *) malloc(fsize);
        fread(data, 1, fsize, rd);
        fclose(rd);
        
        fseek(fp, offset, SEEK_SET);
        encrypt(data, offset, fsize);
        fwrite(data, 1, fsize, fp);
        
        free(data);
    }
    
    free(crypt);
    fclose(fp);
    return 0;
}
