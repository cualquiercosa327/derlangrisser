#include "x816.c" // x816 cpu emulator

int main()
{
    FILE  *fp;
    ulong i,
          l;
    char  fn[256];
    
    x816_load("resources/dl.rom", mode_lorom);
    x816.mem[0xa03d] = x816.mem[0xa03e] = 0xea; // disable interrupt
    
    for (l = 0; l < 489; l++) {
        sprintf(fn, "resources/decomp/d%0.3d.bin", l);
        fp = fopen(fn, "wb");
        x816.pc = 0xa65a;
        x816.bp = 0xa644;
        x816.debug = x816.d = x816.db = 0x0000;
        // x816.debug++;
        
        x816.p = 0x20;
        x816.s = 0x01e6;
        x816.mem[0x00] = 0x0f;
        x816.mem[0x17] = 0xff;
        x816.mem[0x28] = 0x00;
        x816.mem[0x29] = 0x40;
        x816.a = x816.x = l * 3;
        x816.y = 0x00ff;
        
        memset(x816.mem + 0x7e2000, 0, 0x2000);
        
        x816_run();
        for (i = 0x7e2000; i < 0x7e4000; i++)
            fputc(x816.mem[i], fp);
        fclose(fp);
    }
    x816_free();
}
