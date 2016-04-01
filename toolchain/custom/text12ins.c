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
    
    if (argc != 2) {
        printf("usage: text12ins <dl.sfc>\n");
        return 0;
    }
    
    fp = fopen(argv[1], "rb+");
    
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
        // Encrypt(data, offset, fsize);
        fwrite(data, 1, fsize, fp);
        free(data);
        offset += fsize;
    }
    
    free(crypt);
    fclose(fp);
    return 0;
}
