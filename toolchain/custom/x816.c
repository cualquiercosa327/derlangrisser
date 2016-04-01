/*
 * x816 cpu emulator/debugger w/snes memory mapping+extensions version
 * 0.001 author: byuu
 * 
 * unemulated: signed overflows (used by adc and sbc) carry on sbc (sbc
 * #const _may_ be emulated correctly) no flags on t** transfer regs (only 
 * tax emulated, and flags were not needed) lots more 
 */

#include <stdio.h>

#define mode_lorom 0
#define mode_hirom 1

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;
typedef struct {
    byte  *mem;
    byte  p,
          db,
          debug;
    word  a,
          x,
          y,
          d,
          s;
    ulong pc,
          bp,
          p2180;
} x816r;

x816r x816;

#define a8()  x816.p &   0x20
#define x8()  x816.p &   0x10
#define cb()  x816.p &   0x01
#define clc() x816.p &= ~0x01
#define sec() x816.p |=  0x01
#define clz() x816.p &= ~0x02
#define sez() x816.p |=  0x02
#define cli() x816.p &= ~0x04
#define sei() x816.p |=  0x04
#define clv() x816.p &= ~0x40
#define sev() x816.p |=  0x40
#define cln() x816.p &= ~0x80
#define sen() x816.p |=  0x80


word brel(void)
{
    byte r;

    r = x816.mem[x816.pc + 1];
    
    if (r & 0x80)
        return x816.pc - (0xff - r) + 1;
    return x816.pc + r + 2;
}

#define dprint(s,l) db_printf(s,l); break


// Note: Changed from dprintf in the original source to avoid a conflict with
// int dprintf in the stdlib.h of OSX 10.6.0+
// ANSI-hating bastards... :(
void db_printf(char *s, byte l)
{
    char s1[16],
         s2[16];
    int  i;

    strcpy(s1, s);

    if (!l) {
        for (i = strlen(s1); i < 13; i++)
            s1[i] = ' ';
        s1[13] = 0;
        printf("%s", s1);
        return;
    }
    
    for (i = 0; i < strlen(s1); i++)
        if (s1[i] == '*')
            break;
    s1[i] = 0;
    
    if (l == 1)
        sprintf(s2, "%0.2x", x816.mem[x816.pc + 1]);
    if (l == 2)
        sprintf(s2, "%0.2x%0.2x", x816.mem[x816.pc + 2],
    x816.mem[x816.pc + 1]);
    if (l == 3)
        sprintf(s2, "%0.2x%0.2x%0.2x", x816.mem[x816.pc + 3],
    x816.mem[x816.pc + 2], x816.mem[x816.pc + 1]);
    if (l == 4)
        sprintf(s2, "%0.4x", brel());
    if (l == 5) {
        if (a8())
            sprintf(s2, "%0.2x", x816.mem[x816.pc + 1]);
        else
            sprintf(s2, "%0.2x%0.2x", x816.mem[x816.pc + 2],
        x816.mem[x816.pc + 1]);
    }
    if (l == 6) {
        if (x8())
            sprintf(s2, "%0.2x", x816.mem[x816.pc + 1]);
        else
            sprintf(s2, "%0.2x%0.2x", x816.mem[x816.pc + 2],
        x816.mem[x816.pc + 1]);
    }
    
    strcat(s1, s2);
    strcat(s1, s + i + 1);
    
    for (i = strlen(s1); i < 13; i++)
        s1[i] = ' ';
    s1[13] = 0;
    printf("%s", s1);
}


void x816_dbg(void)
{
    byte op;
    op = x816.mem[x816.pc];
    printf("%0.6x ", x816.pc);
    
    switch (op) {
        case 0x05:
            dprint("ora $*", 1);
        case 0x08:
            dprint("php", 0);
        case 0x09:
            dprint("ora #$*", 5);
        case 0x0a:
            dprint("asl", 0);
        case 0x0e:
            dprint("asl $*", 2);
        case 0x10:
            dprint("bpl $*", 4);
        case 0x18:
            dprint("clc", 0);
        case 0x1a:
            dprint("inc", 0);
        case 0x20:
            dprint("jsr $*", 2);
        case 0x22:
            dprint("jsl $*", 3);
        case 0x24:
            dprint("bit $*", 1);
        case 0x25:
            dprint("and $*", 1);
        case 0x26:
            dprint("rol $*", 1);
        case 0x28:
            dprint("plp", 0);
        case 0x29:
            dprint("and #$*", 5);
        case 0x2a:
            dprint("rol", 0);
        case 0x2d:
            dprint("and $*", 2);
        case 0x2e:
            dprint("rol $*", 2);
        case 0x30:
            dprint("bmi $*", 4);
        case 0x38:
            dprint("sec", 0);
        case 0x3a:
            dprint("dec", 0);
        case 0x46:
            dprint("lsr $*", 1);
        case 0x48:
            dprint("pha", 0);
        case 0x49:
            dprint("eor #$*", 5);
        case 0x4a:
            dprint("lsr", 0);
        case 0x4b:
            dprint("phk", 0);
        case 0x4c:
            dprint("jmp $*", 2);
        case 0x4e:
            dprint("lsr $*", 2);
        case 0x5a:
            dprint("phy", 0);
        case 0x60:
            dprint("rts", 0);
        case 0x63:
            dprint("adc $*,s", 1);
        case 0x64:
            dprint("stz $*", 1);
        case 0x65:
            dprint("adc $*", 1);
        case 0x66:
            dprint("ror $*", 1);
        case 0x68:
            dprint("pla", 0);
        case 0x69:
            dprint("adc #$*", 5);
        case 0x6b:
            dprint("rtl", 0);
        case 0x6d:
            dprint("adc $*", 2);
        case 0x6e:
            dprint("ror $*", 2);
        case 0x75:
            dprint("adc $*,x", 1);
        case 0x78:
            dprint("sei", 0);
        case 0x7a:
            dprint("ply", 0);
        case 0x7b:
            dprint("tdc", 0);
        case 0x80:
            dprint("bra $*", 4);
        case 0x84:
            dprint("sty $*", 1);
        case 0x85:
            dprint("sta $*", 1);
        case 0x86:
            dprint("stx $*", 1);
        case 0x88:
            dprint("dey", 0);
        case 0x8a:
            dprint("txa", 0);
        case 0x8b:
            dprint("phb", 0);
        case 0x8d:
            dprint("sta $*", 2);
        case 0x8f:
            dprint("sta $*", 3);
        case 0x90:
            dprint("bcc $*", 4);
        case 0x95:
            dprint("sta $*,x", 1);
        case 0x9c:
            dprint("stz $*", 2);
        case 0x9d:
            dprint("sta $*,x", 2);
        case 0xa0:
            dprint("ldy #$*", 6);
        case 0xa2:
            dprint("ldx #$*", 6);
        case 0xa3:
            dprint("lda $*,s", 1);
        case 0xa5:
            dprint("lda $*", 1);
        case 0xa6:
            dprint("ldx $*", 1);
        case 0xa7:
            dprint("lda [$*]", 1);
        case 0xa8:
            dprint("tay", 0);
        case 0xa9:
            dprint("lda #$*", 5);
        case 0xaa:
            dprint("tax", 0);
        case 0xab:
            dprint("plb", 0);
        case 0xac:
            dprint("ldy $*", 2);
        case 0xad:
            dprint("lda $*", 2);
        case 0xaf:
            dprint("lda $*", 3);
        case 0xb0:
            dprint("bcs $*", 4);
        case 0xb5:
            dprint("lda $*,x", 1);
        case 0xb7:
            dprint("lda [$*],y", 1);
        case 0xb9:
            dprint("lda $*,y", 2);
        case 0xbf:
            dprint("lda $*,x", 3);
        case 0xc2:
            dprint("rep #$*", 1);
        case 0xc4:
            dprint("cpy $*", 1);
        case 0xc6:
            dprint("dec $*", 1);
        case 0xc8:
            dprint("iny", 0);
        case 0xc9:
            dprint("cmp #$*", 5);
        case 0xca:
            dprint("dex", 0);
        case 0xcd:
            dprint("cmp $*", 2);
        case 0xce:
            dprint("dec $*", 2);
        case 0xd0:
            dprint("bne $*", 4);
        case 0xda:
            dprint("phx", 0);
        case 0xe2:
            dprint("sep #$*", 1);
        case 0xe5:
            dprint("sbc $*", 1);
        case 0xe6:
            dprint("inc $*", 1);
        case 0xe8:
            dprint("inx", 0);
        case 0xe9:
            dprint("sbc #$*", 5);
        case 0xea:
            dprint("nop", 0);
        case 0xeb:
            dprint("xba", 0);
        case 0xed:
            dprint("sbc $*", 2);
        case 0xee:
            dprint("inc $*", 2);
        case 0xf0:
            dprint("beq $*", 4);
        case 0xf4:
            dprint("pea $*", 2);
        case 0xfa:
            dprint("plx", 0);
        default:
            printf("[%0.2x] unknown ", op);
        break;
    }
    
    printf(" a:%0.4x x:%0.4x y:%0.4x s:%0.4x db:%0.2x d:%0.4x p:%0.2x\n",
       x816.a, x816.x, x816.y, x816.s, x816.db, x816.d, x816.p);
}


void x816_adc_addr(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    word a;

    if (a8()) {
        if (x816.mem[addr] + (x816.a & 0xff) <= 0xff)
            clc();
        else
            sec();
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) + (x816.mem[addr])) & 0xff;
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        if (x816.mem[addr] + (x816.mem[addr + 1] << 8) + x816.a <= 0xffff)
            clc();
        else
            sec();
        a = (x816.a) + (x816.mem[addr]) + (x816.mem[addr + 1] << 8);
        x816.a = a;
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc += 2;
}


void x816_adc_const(void)
{
    byte c = cb();
    x816.pc++;

    if (a8()) {
        if ((x816.mem[x816.pc] + (x816.a & 0xff) + c) <= 0xff)
            clc();
        else
            sec();
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) + (x816.mem[x816.pc]) + c) & 0xff;
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
        x816.pc++;
    }
    else {
        if ((x816.mem[x816.pc] + (x816.mem[x816.pc + 1] << 8) + x816.a + c) <= 0xffff)
            clc();
        else
            sec();
        x816.a = x816.a + x816.mem[x816.pc] + (x816.mem[x816.pc + 1] << 8) + c;
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
        x816.pc += 2;
    }
}


void x816_adc_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    word a;
    
    if (a8()) {
        if (x816.mem[addr] + (x816.a & 0xff) <= 0xff)
            clc();
        else
            sec();
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) + (x816.mem[addr])) & 0xff;
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        if (x816.mem[addr] + (x816.mem[addr + 1] << 8) + x816.a <= 0xffff)
            clc();
        else
            sec();
        a = (x816.a) + (x816.mem[addr]) + (x816.mem[addr + 1] << 8);
        x816.a = a;
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc++;
}


void x816_adc_dpx(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc] + x816.x;
    
    word a;
    
    if (a8()) {
        if (x816.mem[addr] + (x816.a & 0xff) <= 0xff)
            clc();
        else
            sec();
        x816.a =
            x816.a & 0xff00 | ((x816.a & 0xff) + (x816.mem[addr])) & 0xff;
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        if (x816.mem[addr] + (x816.mem[addr + 1] << 8) + x816.a <= 0xffff)
            clc();
        else
            sec();
        a = (x816.a) + (x816.mem[addr]) + (x816.mem[addr + 1] << 8);
        x816.a = a;
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc++;
}


void x816_adc_s(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.s + x816.mem[x816.pc];
    word            a;
    
    if (a8()) {
        if (x816.mem[addr] + (x816.a & 0xff) <= 0xff)
            clc();
        else
            sec();
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) + (x816.mem[addr])) & 0xff;
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        if (x816.mem[addr] << 8 + (x816.mem[addr + 1]) + x816.a <= 0xffff)
            clc();
        else
            sec();
        a = (x816.a) + (x816.mem[addr] << 8) + (x816.mem[addr + 1]);
        x816.a = a;
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc++;
}


void x816_and_const(void)
{
    word a;
    
    x816.pc++;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | (x816.a & 0xff) & (x816.mem[x816.pc]);
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
        x816.pc++;
    }
    else {
        a = x816.a & (x816.mem[x816.pc] + (x816.mem[x816.pc + 1] << 8));
        x816.a = a;
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
        x816.pc += 2;
    }
}


void x816_and_addr(void)
{
    ulong addr;
    word  a;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) & (x816.mem[addr]));
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        x816.a = x816.a & (x816.mem[addr] | x816.mem[addr + 1] << 8);
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc += 2;
}


void x816_and_dp(void)
{
    ulong addr;
    word  a;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) & (x816.mem[addr]));
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        x816.a = x816.a & (x816.mem[addr] | x816.mem[addr + 1] << 8);
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc++;
}


void x816_asl(void)
{
    x816.pc++;
    if (a8()) {
        if (x816.a & 0x80)
            sec();
        else
            clc();
        x816.a = x816.a & 0xff00 | (x816.a << 1) & 0xff;
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        if (x816.a & 0x8000)
            sec();
        else
            clc();
        x816.a <<= 1;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_asl_addr(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8 | x816.db << 16;
    
    word x;
    
    if (a8()) {
        if (x816.mem[addr] & 0x80)
            sec();
        else
            clc();
        x816.mem[addr] <<= 1;
        if (x816.mem[addr])
            clz();
        else
            sez();
    }
    else {
        x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x & 0x8000)
            sec();
        else
            clc();
        x <<= 1;
        if (x)
            clz();
        else
            sez();
        x816.mem[addr] = x;
        x816.mem[addr + 1] = x >> 8;
    }
    x816.pc += 2;
}


void x816_bcc(void)
{
    byte r;
    
    x816.pc++;
    
    if (x816.p & 0x01)
        goto cs;
    
    r = x816.mem[x816.pc];
    
    if (r & 0x80) {
        x816.pc -= 0xff - r;
    }
    else {
        x816.pc += r + 1;
    }
    return;
    cs:
        x816.pc++;
}


void x816_bcs(void)
{
    byte r;
    
    x816.pc++;
    
    if (!(x816.p & 0x01))
        goto cc;
    
    r = x816.mem[x816.pc];
    
    if (r & 0x80) {
        x816.pc -= 0xff - r;
    }
    else {
        x816.pc += r + 1;
    }
    return;
    cc:
        x816.pc++;
}


void x816_beq(void)
{
    byte r;
    
    x816.pc++;
    
    if (!(x816.p & 0x02))
        goto ne;
    
    r = x816.mem[x816.pc];
    
    if (r & 0x80) {
        x816.pc -= 0xff - r;
    }
    else {
        x816.pc += r + 1;
    }
    return;
    ne:
        x816.pc++;
}


void x816_bit_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    if (x816.mem[addr] & x816.a)
        clz();
    else
        sez();
        
    if (a8()) {
        if (x816.mem[addr] & 0x80)
            sen();
        else
            cln();
        if (x816.mem[addr] & 0x40)
            sev();
        else
            clv();
    }
    else {
        if (x816.mem[addr + 1] & 0x80)
            sen();
        else
            cln();
        if (x816.mem[addr + 1] & 0x40)
            sev();
        else
            clv();
    }
    x816.pc++;
}


void x816_bmi(void)
{
    byte r;
    
    x816.pc++;
    
    if (!(x816.p & 0x80))
        goto pl;
    
    r = x816.mem[x816.pc];
    
    if (r & 0x80) {
        x816.pc -= 0xff - r;
    }
    else {
        x816.pc += r + 1;
    }
    return;
    pl:
        x816.pc++;
}


void x816_bne(void)
{
    byte r;
    
    x816.pc++;
    
    if (x816.p & 0x02)
        goto eq;
    
    r = x816.mem[x816.pc];
    
    if (r & 0x80) {
        x816.pc -= 0xff - r;
    }
    else {
        x816.pc += r + 1;
    }
    return;
    eq:
        x816.pc++;
}


void x816_bpl(void)
{
    byte r;
    
    x816.pc++;
    
    if (x816.p & 0x80)
        goto mi;
    
    r = x816.mem[x816.pc];
    
    if (r & 0x80) {
        x816.pc -= 0xff - r;
    }
    else {
        x816.pc += r + 1;
    }
    return;
    mi:
        x816.pc++;
}


void x816_bra(void)
{
    byte r;
    
    x816.pc++;
    r = x816.mem[x816.pc];
    
    if (r & 0x80) {
        x816.pc -= 0xff - r;
    }
    else {
        x816.pc += r + 1;
    }
}


void x816_clc(void)
{
    x816.pc++;
    clc();
}


void x816_cmp_addr(void)
{
    ulong addr;
    word  x;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    if (a8()) {
        if ((x816.a & 0xff) == x816.mem[addr])
            sez();
        else
            clz();
        if ((x816.a & 0xff) >= x816.mem[x816.pc])
            sec();
        else
            clc();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.a == x)
            sez();
        else
            clz();
        if (x816.a >= x)
            sec();
        else
            clc();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc += 2;
}


void x816_cmp_const(void)
{
    word x;
    
    x816.pc++;
    
    if (a8()) {
        if ((x816.a & 0xff) == x816.mem[x816.pc])
            sez();
        else
            clz();
        if ((x816.a & 0xff) >= x816.mem[x816.pc])
            sec();
        else
            clc();
        x816.pc++;
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8;
        if (x816.a == x)
            sez();
        else
            clz();
        if (x816.a >= x)
            sec();
        else
            clc();
        x816.pc += 2;
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
}


// Highest bit of p is wrong
void x816_cpy_dp(void)
{
    ulong addr;
    word  x;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    if (x8()) {
        if ((x816.y & 0xff) == x816.mem[addr])
            sez();
        else
            clz();
        if ((x816.y & 0xff) >= x816.mem[x816.pc])
            sec();
        else
            clc();
        if (x816.y & 0x80)
            sen();
        else
            cln();
    }
    else {
        x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.y == x)
            sez();
        else
            clz();
        if (x816.y >= x)
            sec();
        else
            clc();
        if (x816.y & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc++;
}


void x816_dec(void)
{
    x816.pc++;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | (x816.a - 1) & 0xff;
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a--;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_dec_addr(void)
{
    ulong addr;
    word  x;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    if (a8()) {
        x816.mem[addr]--;
        if (x816.mem[addr])
            clz();
        else
            sez();
        if (x816.mem[addr] & 0x80)
            sen();
        else
            cln();
    }
    else {
        x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        x--;
        x816.mem[addr] = x;
        x816.mem[addr + 1] = x >> 8;
        if (x)
            clz();
        else
            sez();
        if (x & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc += 2;
}


void x816_dec_dp(void)
{
    ulong addr;
    word  x;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    if (a8()) {
        x816.mem[addr]--;
        if (x816.mem[addr])
            clz();
        else
            sez();
        if (x816.mem[addr] & 0x80)
            sen();
        else
            cln();
    }
    else {
        x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        x--;
        x816.mem[addr] = x;
        x816.mem[addr + 1] = x >> 8;
        if (x)
            clz();
        else
            sez();
        if (x & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc++;
}


void x816_dex(void)
{
    x816.pc++;
    
    if (x8()) {
        x816.x = x816.x & 0xff00 | (x816.x - 1) & 0xff;
        if (x816.x)
            clz();
        else
            sez();
        if (x816.x & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.x--;
        if (x816.x)
            clz();
        else
            sez();
        if (x816.x & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_dey(void)
{
    x816.pc++;
    
    if (x8()) {
        x816.y = x816.y & 0xff00 | (x816.y - 1) & 0xff;
        if (x816.y)
            clz();
        else
            sez();
        if (x816.y & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.y--;
        if (x816.y)
            clz();
        else
            sez();
        if (x816.y & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_eor_const(void)
{
    word x;
    
    x816.pc++;
    
    if (a8()) {
        x816.a ^= x816.mem[x816.pc++];
        if (!(x816.a & 0xff))
            sez();
        else
            clz();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a ^= (x816.mem[x816.pc++] | x816.mem[x816.pc++] << 8);
        if (!x816.a)
            sez();
        else
            clz();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_inc(void)
{
    x816.pc++;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | (x816.a + 1) & 0xff;
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a++;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_inc_addr(void)
{
    ulong addr;
    word  x;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    if (a8()) {
        x816.mem[addr]++;
        if (x816.mem[addr])
            clz();
        else
            sez();
        if (x816.mem[addr] & 0x80)
            sen();
        else
            cln();
    }
    else {
        x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        x++;
        x816.mem[addr] = x;
        x816.mem[addr + 1] = x >> 8;
        if (x)
            clz();
        else
            sez();
        if (x & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc += 2;
}


void x816_inc_dp(void)
{
    ulong addr;
    word  x;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    if (a8()) {
        x816.mem[addr]++;
        if (x816.mem[addr])
            clz();
        else
            sez();
        if (x816.mem[addr] & 0x80)
            sen();
        else
            cln();
    }
    else {
        x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        x++;
        x816.mem[addr] = x;
        x816.mem[addr + 1] = x >> 8;
        if (x)
            clz();
        else
            sez();
        if (x & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc++;
}


void x816_inx(void)
{
    x816.pc++;
    
    if (x8()) {
        x816.x = x816.x & 0xff00 | (x816.x + 1) & 0xff;
        if (x816.x & 0xff)
            clz();
        else
            sez();
        if (x816.x & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.x++;
        if (x816.x)
            clz();
        else
            sez();
        if (x816.x & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_iny(void)
{
    x816.pc++;
    
    if (x8()) {
        x816.y = x816.y & 0xff00 | (x816.y + 1) & 0xff;
        if (x816.y & 0xff)
            clz();
        else
            sez();
        if (x816.y & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.y++;
        if (x816.y)
            clz();
        else
            sez();
        if (x816.y & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_jmp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8 | x816.pc & 0xff0000;
    x816.pc = addr;
}


void x816_jsr(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8 | x816.pc & 0xff0000;
    x816.pc += 2;
    x816.mem[x816.s--] = x816.pc >> 8;
    x816.mem[x816.s--] = x816.pc;
    x816.pc = addr;
}


void x816_jsl(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc + 2] << 16;
    x816.pc += 3;
    x816.mem[x816.s--] = x816.pc >> 16;
    x816.mem[x816.s--] = x816.pc >> 8;
    x816.mem[x816.s--] = x816.pc;
    x816.pc = addr;
}


void x816_lda_addr(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[addr];
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc += 2;
}


void x816_lda_addry(void)
{
    ulong addr;
    
    x816.pc++;
    addr = (x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc]) + x816.y;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[addr];
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[addr] | (x816.mem[addr + 1] << 8);
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc += 2;
}


void x816_lda_const(void)
{
    x816.pc++;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[x816.pc];
        x816.pc++;
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8;
        x816.pc += 2;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_lda_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[addr];
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc++;
}


void x816_lda_dps(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc] + x816.s;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[addr];
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc++;
}


void x816_lda_dpx(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc] + x816.x;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[addr];
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc++;
}


void x816_lda_ildp(void)
{
    ulong addr,
          addr1;
    
    x816.pc++;
    
    addr1 = x816.mem[x816.pc];
    addr = x816.mem[addr1] | x816.mem[addr1 + 1] << 8 | x816.mem[addr1 + 2] << 16;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[addr];
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc++;
}


void x816_lda_ildpy(void)
{
    ulong addr,
          addr1;
    
    x816.pc++;
    addr1 = x816.mem[x816.pc];
    addr = (x816.mem[addr1] | x816.mem[addr1 + 1] << 8 | x816.mem[addr1 + 2] << 16) + x816.y;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[addr];
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc++;
}


void x816_lda_long(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc + 2] << 16;
    
    word x;
    
    if (addr == 0x4216 || addr == 0x4217) {
        x = x816.mem[0x4202] * x816.mem[0x4203];
        x816.mem[0x4216] = x;
        x816.mem[0x4217] = x >> 8;
    }
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[addr];
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[addr + 1] << 8 | x816.mem[addr];
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc += 3;
}


void x816_lda_long_x(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc + 2] << 16;
    addr += x816.x;
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | x816.mem[addr];
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.a = x816.mem[addr + 1] << 8 | x816.mem[addr];
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc += 3;
}


void x816_ldx_const(void)
{
    x816.pc++;
    
    if (x8()) {
        x816.x = x816.x & 0xff00 | x816.mem[x816.pc];
        x816.pc++;
        if (x816.x & 0xff)
            clz();
        else
            sez();
        if (x816.x & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.x = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8;
        x816.pc += 2;
        if (x816.x)
            clz();
        else
            sez();
        if (x816.x & 0x8000)
            sen();
        else
            cln();
    }
}

void x816_ldx_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    if (x8()) {
        x816.x = x816.x & 0xff00 | x816.mem[addr];
        if (x816.x & 0xff)
            clz();
        else
            sez();
        if (x816.x & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.x)
            clz();
        else
            sez();
        if (x816.x & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc++;
}


void x816_ldy_addr(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    if (x8()) {
        x816.y = x816.y & 0xff00 | x816.mem[addr];
        if (x816.y & 0xff)
            clz();
        else
            sez();
        if (x816.y & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.y = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x816.y)
            clz();
        else
            sez();
        if (x816.y & 0x8000)
            sen();
        else
            cln();
    }
    x816.pc += 2;
}


void x816_ldy_const(void)
{
    x816.pc++;
    
    if (x8()) {
        x816.y = x816.y & 0xff00 | x816.mem[x816.pc];
        x816.pc++;
        if (x816.y & 0xff)
            clz();
        else
            sez();
        if (x816.y & 0x80)
            sen();
        else
            cln();
    }
    else {
        x816.y = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8;
        x816.pc += 2;
        if (x816.y)
            clz();
        else
            sez();
        if (x816.y & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_lsr(void)
{
    x816.pc++;
    
    if (a8()) {
        if (x816.a & 0x01)
            sec();
        else
            clc();
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) >> 1) & 0xff;
        if (x816.a & 0xff)
            clz();
        else
            sez();
        if (x816.a & 0x80)
            sen();
        else
            cln();
    }
    else {
        if (x816.a & 0x0001)
            sec();
        else
            clc();
        x816.a >>= 1;
        if (x816.a)
            clz();
        else
            sez();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
    }
}


void x816_lsr_addr(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    word x;
    
    if (a8()) {
        if (x816.mem[addr] & 0x01)
            sec();
        else
            clc();
        x816.mem[addr] = x816.mem[addr] >> 1;
        if (x816.mem[addr])
            clz();
        else
            sez();
        if (x816.mem[addr] & 0x80)
            sen();
        else
            cln();
    }
    else {
        x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x)
            sec();
        else
            clc();
        x >>= 1;
        if (x)
            clz();
        else
            sez();
        if (x & 0x8000)
            sen();
        else
            cln();
        x816.mem[addr] = x;
        x816.mem[addr + 1] = x >> 8;
    }
    x816.pc += 2;
}


void x816_lsr_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    word x;
    
    if (a8()) {
        if (x816.mem[addr] & 0x01)
            sec();
        else
            clc();
        x816.mem[addr] = x816.mem[addr] >> 1;
        if (x816.mem[addr])
            clz();
        else
            sez();
        if (x816.mem[addr] & 0x80)
            sen();
        else
            cln();
    }
    else {
        x = x816.mem[addr] | x816.mem[addr + 1] << 8;
        if (x)
            sec();
        else
            clc();
        x >>= 1;
        if (x)
            clz();
        else
            sez();
        if (x & 0x8000)
            sen();
        else
            cln();
        x816.mem[addr] = x;
        x816.mem[addr + 1] = x >> 8;
    }
    x816.pc++;
}


void x816_nop(void)
{
    x816.pc++;
}


void x816_ora_const(void)
{
    byte c = cb();

    x816.pc++;

    if (a8()) {
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) | (x816.mem[x816.pc])) & 0xff;
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        x816.a = x816.a | (x816.mem[x816.pc] + (x816.mem[x816.pc + 1] << 8));
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc++;
}


void x816_ora_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    if (a8()) {
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) | x816.mem[addr]) & 0xff;
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        x816.a = x816.a | (x816.mem[addr] + x816.mem[addr + 1] << 8);
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc++;
}


void x816_pea(void)
{
    x816.pc++;
    x816.mem[x816.s--] = x816.mem[x816.pc];
    x816.mem[x816.s--] = x816.mem[x816.pc + 1];
    x816.pc += 2;
}


void x816_pha(void)
{
    x816.pc++;
    x816.mem[x816.s--] = x816.a;
    if (a8())
        return;
    x816.mem[x816.s--] = x816.a >> 8;
}


void x816_pla(void)
{
    x816.pc++;
    if (a8())
        x816.a = x816.a & 0xff00 | x816.mem[++x816.s];
    else
        x816.a = x816.mem[++x816.s] << 8 | x816.mem[++x816.s];
}


void x816_phb(void)
{
    x816.pc++;
    x816.mem[x816.s--] = x816.db;
}


void x816_plb(void)
{
    x816.pc++;
    x816.db = x816.mem[++x816.s];
}


void x816_phk(void)
{
    x816.pc++;
    x816.mem[x816.s--] = x816.pc >> 16;
}


void x816_php(void)
{
    x816.pc++;
    x816.mem[x816.s--] = x816.p;
}


void x816_plp(void)
{
    x816.pc++;
    x816.p = x816.mem[++x816.s];
}


void x816_phx(void)
{
    x816.pc++;
    x816.mem[x816.s--] = x816.x;
    if (x8())
        return;
    x816.mem[x816.s--] = x816.x >> 8;
}


void x816_plx(void)
{
    x816.pc++;
    if (x8())
        x816.x = x816.x & 0xff00 | x816.mem[++x816.s];
    else
        x816.x = x816.mem[++x816.s] << 8 | x816.mem[++x816.s];
}

void x816_phy(void)
{
    x816.pc++;
    x816.mem[x816.s--] = x816.y;
    if (x8())
        return;
    x816.mem[x816.s--] = x816.y >> 8;
}


void x816_ply(void)
{
    x816.pc++;
    if (x8())
        x816.y = x816.y & 0xff00 | x816.mem[++x816.s];
    else
        x816.y = x816.mem[++x816.s] << 8 | x816.mem[++x816.s];
}


void x816_rep(void)
{
    byte b;
    
    x816.pc++;
    b = x816.mem[x816.pc];
    x816.p &= ~b;
    x816.pc++;
}


void x816_rol(void)
{
    word a;
    byte c;
    
    if (a8()) {
        a = x816.a & 0xff;
        c = a & 0x80;
        a <<= 1;
        a |= x816.p & 0x01;
        if (c)
            sec();
        else
            clc();
        x816.a = x816.a & 0xff00 | a;
    }
    else {
        a = x816.a;
        c = a & 0x8000;
        a <<= 1;
        a |= x816.p & 0x01;
        if (c)
            sec();
        else
            clc();
        x816.a = a;
    }
    x816.pc++;
}


void x816_rol_addr(void)
{
    ulong addr;
    word  a;
    byte  c;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    if (a8()) {
        a = x816.mem[addr];
        c = a & 0x80;
        a <<= 1;
        a |= x816.p & 0x01;
        if (c)
            sec();
        else
            clc();
        x816.mem[addr] = a;
    }
    else {
        a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        c = a & 0x8000;
        a <<= 1;
        a |= x816.p & 0x01;
        if (c)
            sec();
        else
            clc();
        x816.mem[addr] = a;
        x816.mem[addr + 1] = a >> 8;
    }
    x816.pc += 2;
}


void x816_rol_dp(void)
{
    ulong addr = x816.d + x816.mem[++x816.pc];
    word  a;
    byte  c;
    
    if (a8()) {
        a = x816.mem[addr];
        c = a & 0x80;
        a <<= 1;
        a |= x816.p & 0x01;
        if (c)
            sec();
        else
            clc();
        x816.mem[addr] = a;
    }
    else {
        a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        c = a & 0x8000;
        a <<= 1;
        a |= x816.p & 0x01;
        if (c)
            sec();
        else
            clc();
        x816.mem[addr] = a;
        x816.mem[addr + 1] = a >> 8;
    }
    x816.pc++;
}

void x816_ror_addr(void)
{
    ulong addr;
    word  a;
    byte  c;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    if (a8()) {
        a = x816.mem[addr];
        c = a & 0x01;
        a >>= 1;
        a |= (x816.p & 0x01) << 7;
        if (c)
            sec();
        else
            clc();
        x816.mem[addr] = a;
    }
    else {
        a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        c = a & 0x0001;
        a >>= 1;
        a |= (x816.p & 0x01) << 15;
        if (c)
            sec();
        else
            clc();
        x816.mem[addr] = a;
        x816.mem[addr + 1] = a >> 8;
    }
    x816.pc += 2;
}


void x816_ror_dp(void)
{
    ulong addr = x816.d + x816.mem[++x816.pc];
    word  a;
    byte  c;
    
    if (a8()) {
        a = x816.mem[addr];
        c = a & 0x01;
        a >>= 1;
        a |= (x816.p & 0x01) << 7;
        if (c)
            sec();
        else
            clc();
        x816.mem[addr] = a;
    }
    else {
        a = x816.mem[addr] | x816.mem[addr + 1] << 8;
        c = a & 0x0001;
        a >>= 1;
        a |= (x816.p & 0x01) << 15;
        if (c)
            sec();
        else
            clc();
        x816.mem[addr] = a;
        x816.mem[addr + 1] = a >> 8;
    }
    x816.pc++;
}


void x816_rtl(void)
{
    x816.pc =
        x816.mem[++x816.s] | x816.mem[++x816.s] << 8 | x816.mem[++x816.s] << 16;
}


void x816_rts(void)
{
    ulong addr =
        x816.mem[++x816.s] | x816.mem[++x816.s] << 8 | x816.pc & 0xff0000;
    x816.pc = addr;
}


void x816_sbc_const(void)
{
    word x,
         y;

    x816.pc++;

    if (a8()) {
        x = x816.a;
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) - (x816.mem[x816.pc] +
            (!(x816.p & 0x01)))) & 0xff;
        y = x816.a;
        
        if ((x & 0x80) == (y & 0x80))
            sec();
        else
            clc();
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
        x816.pc++;
    }
    else {
        x = x816.a;
        x816.a = x816.a - (x816.mem[x816.pc] + x816.mem[x816.pc + 1] << 8 +
            (!(x816.p & 0x01)));
        y = x816.a;
        if ((x & 0x8000) == (y & 0x8000))
            sec();
        else
            clc();
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
        x816.pc += 2;
    }
}


void x816_sbc_addr(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.db << 16 | x816.mem[x816.pc + 1] << 8 | x816.mem[x816.pc];
    
    if (a8()) {
        sec();
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) - (x816.mem[addr])) & 0xff;
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        sec();
        x816.a = x816.a - (x816.mem[addr] | x816.mem[addr + 1] << 8);
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc += 2;
}


void x816_sbc_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    
    if (a8()) {
        sec();
        x816.a = x816.a & 0xff00 | ((x816.a & 0xff) - (x816.mem[addr])) & 0xff;
        if (x816.a & 0x80)
            sen();
        else
            cln();
        if (x816.a & 0xff)
            clz();
        else
            sez();
    }
    else {
        sec();
        x816.a = x816.a - (x816.mem[addr] | x816.mem[addr + 1] << 8);
        if (x816.a & 0x8000)
            sen();
        else
            cln();
        if (x816.a)
            clz();
        else
            sez();
    }
    x816.pc++;
}


void x816_sec(void)
{
    x816.pc++;
    sec();
}


void x816_sei(void)
{
    x816.pc++;
    sei();
}


void x816_sep(void)
{
    x816.pc++;
    x816.p |= x816.mem[x816.pc];
    
    if (x816.mem[x816.pc] & 0x10) {
        x816.x &= 0xff;
        x816.y &= 0xff;
    }
    
    x816.pc++;
}


void x816_sta_addr(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8 | x816.db << 16;
    
    if (addr == 0x2180) {
        x816.mem[x816.p2180++] = x816.a;
        if (a8());
        else
            x816.mem[x816.p2180++] = x816.p2180 >> 8;
    }
    else if (addr == 0x2181) {
        if (a8())
            x816.p2180 = x816.p2180 & 0xffff00 | (x816.a & 0xff);
        else
            x816.p2180 = x816.p2180 & 0xff0000 | x816.a;
    }
    else if (addr == 0x2182)
        x816.p2180 = x816.p2180 & 0xff00ff | x816.a << 8;
    else if (addr == 0x2183)
        x816.p2180 = (x816.p2180 & 0x00ffff) | 0x7e0000 | (x816.a & 1) << 16;
    else {
        x816.mem[addr] = x816.a;
    
        if (a8());
        else
            x816.mem[addr + 1] = x816.a >> 8;
    }
    x816.pc += 2;
}


void x816_sta_addrx(void)
{
    ulong addr;
    
    x816.pc++;
    addr = (x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8 | x816.db << 16) +
        x816.x;
    x816.mem[addr] = x816.a;
    
    if (a8());
    else
        x816.mem[addr + 1] = x816.a >> 8;
        
    x816.pc += 2;
}


void x816_sta_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    x816.mem[addr] = x816.a;
    
    if (a8());
    else
        x816.mem[addr + 1] = x816.a >> 8;
    
    x816.pc++;
}

void x816_sta_dpx(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc] + x816.x;
    x816.mem[addr] = x816.a;
    
    if (a8());
    else
        x816.mem[addr + 1] = x816.a >> 8;
    
    x816.pc++;
}


void x816_sta_long(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.mem[x816.pc + 2] << 16 | x816.mem[x816.pc + 1]
        << 8 | x816.mem[x816.pc];
    x816.mem[addr] = x816.a;
    
    if (a8());
    else
        x816.mem[addr + 1] = x816.a >> 8;
    
    x816.pc += 3;
}


void x816_stx_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    x816.mem[addr] = x816.x;
    
    if (!x8())
        x816.mem[addr + 1] = x816.x >> 8;
    
    x816.pc++;
}


void x816_sty_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    x816.mem[addr] = x816.y;
    
    if (!x8())
        x816.mem[addr + 1] = x816.y >> 8;
    
    x816.pc++;
}


void x816_stz_addr(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.mem[x816.pc] | x816.mem[x816.pc + 1] << 8 | x816.db << 16;
    x816.mem[addr] = 0;
    
    if (a8());
    else
        x816.mem[addr + 1] = 0;
    
    x816.pc += 2;
}


void x816_stz_dp(void)
{
    ulong addr;
    
    x816.pc++;
    addr = x816.d + x816.mem[x816.pc];
    x816.mem[addr] = 0;
    
    if (a8());
    else
        x816.mem[addr + 1] = 0;
        
    x816.pc++;
}


void x816_tax(void)
{
    x816.pc++;
    
    if (x8())
        x816.x = x816.x & 0xff00 | x816.a & 0x00ff;
    else
        x816.x = x816.a;
}


void x816_txa(void)
{
    x816.pc++;
    
    if (a8())
        x816.a = x816.a & 0xff00 | x816.x & 0x00ff;
    else
        x816.a = x816.x;
}


void x816_tay(void)
{
    x816.pc++;
    
    if (x8())
        x816.y = x816.y & 0xff00 | x816.a & 0x00ff;
    else
        x816.y = x816.a;
}


void x816_tdc(void)
{
    x816.pc++;
    
    if (a8())
        x816.a = (x816.a & 0xff00) | (x816.d & 0x00ff);
    else
        x816.a = x816.d;
}


void x816_xba(void)
{
    byte s;
    s = x816.a >> 8;
    
    x816.a = s | ((x816.a & 0xff) << 8);
    x816.pc++;
}


void x816_load(char *fn, byte mode)
{
    FILE *fp = fopen(fn, "rb");
    int  i;
    
    x816.mem = (byte *) malloc(0x1000000);
    memset(x816.mem, 0, 0x1000000);
    
    if (mode == mode_lorom) {
        for (i = 0; i < 128; i++)
            fread(x816.mem + 0x8000 + i * 0x10000, 1, 0x8000, fp);
    }
    else if (mode == mode_hirom) {
        fread(x816.mem + 0xc00000, 1, 0x400000, fp);
    }
    
    fclose(fp);
}


void x816_run(void)
{
    byte  op,
          term = 0;
    ulong ic = 0;
    
    while (1) {
        if (term)
            break;
        if (x816.pc == x816.bp)
            break;
    
        op = x816.mem[x816.pc];
        
        if (x816.debug)
            x816_dbg();
        
        switch (op) {
            case 0x05:
                x816_ora_dp();
                break;
            case 0x08:
                x816_php();
                break;
            case 0x09:
                x816_ora_const();
                break;
            case 0x0a:
                x816_asl();
                break;
            case 0x0e:
                x816_asl_addr();
                break;
            case 0x10:
                x816_bpl();
                break;
            case 0x18:
                x816_clc();
                break;
            case 0x1a:
                x816_inc();
                break;
            case 0x20:
                x816_jsr();
                break;
            case 0x22:
                x816_jsl();
                break;
            case 0x24:
                x816_bit_dp();
                break;
            case 0x25:
                x816_and_dp();
                break;
            case 0x26:
                x816_rol_dp();
                break;
            case 0x28:
                x816_plp();
                break;
            case 0x29:
                x816_and_const();
                break;
            case 0x2a:
                x816_rol();
                break;
            case 0x2d:
                x816_and_addr();
                break;
            case 0x2e:
                x816_rol_addr();
                break;
            case 0x30:
                x816_bmi();
                break;
            case 0x38:
                x816_sec();
                break;
            case 0x3a:
                x816_dec();
                break;
            case 0x46:
                x816_lsr_dp();
                break;
            case 0x48:
                x816_pha();
                break;
            case 0x49:
                x816_eor_const();
                break;
            case 0x4a:
                x816_lsr();
                break;
            case 0x4b:
                x816_phk();
                break;
            case 0x4c:
                x816_jmp();
                break;
            case 0x4e:
                x816_lsr_addr();
                break;
            case 0x5a:
                x816_phy();
                break;
            case 0x60:
                x816_rts();
                break;
            case 0x63:
                x816_adc_s();
                break;
            case 0x64:
                x816_stz_dp();
                break;
            case 0x65:
                x816_adc_dp();
                break;
            case 0x66:
                x816_ror_dp();
                break;
            case 0x68:
                x816_pla();
                break;
            case 0x69:
                x816_adc_const();
                break;
            case 0x6b:
                x816_rtl();
                break;
            case 0x6d:
                x816_adc_addr();
                break;
            case 0x6e:
                x816_ror_addr();
                break;
            case 0x75:
                x816_adc_dpx();
                break;
            case 0x78:
                x816_sei();
                break;
            case 0x7a:
                x816_ply();
                break;
            case 0x7b:
                x816_tdc();
                break;
            case 0x80:
                x816_bra();
                break;
            case 0x84:
                x816_sty_dp();
                break;
            case 0x85:
                x816_sta_dp();
                break;
            case 0x86:
                x816_stx_dp();
                break;
            case 0x88:
                x816_dey();
                break;
            case 0x8a:
                x816_txa();
                break;
            case 0x8b:
                x816_phb();
                break;
            case 0x8d:
                x816_sta_addr();
                break;
            case 0x8f:
                x816_sta_long();
                break;
            case 0x90:
                x816_bcc();
                break;
            case 0x95:
                x816_sta_dpx();
                break;
            case 0x9c:
                x816_stz_addr();
                break;
            case 0x9d:
                x816_sta_addrx();
                break;
            case 0xa0:
                x816_ldy_const();
                break;
            case 0xa2:
                x816_ldx_const();
                break;
            case 0xa3:
                x816_lda_dps();
                break;
            case 0xa5:
                x816_lda_dp();
                break;
            case 0xa6:
                x816_ldx_dp();
                break;
            case 0xa7:
                x816_lda_ildp();
                break;
            case 0xa8:
                x816_tay();
                break;
            case 0xa9:
                x816_lda_const();
                break;
            case 0xaa:
                x816_tax();
                break;
            case 0xab:
                x816_plb();
                break;
            case 0xac:
                x816_ldy_addr();
                break;
            case 0xad:
                x816_lda_addr();
                break;
            case 0xaf:
                x816_lda_long();
                break;
            case 0xb0:
                x816_bcs();
                break;
            case 0xb5:
                x816_lda_dpx();
                break;
            case 0xb7:
                x816_lda_ildpy();
                break;
            case 0xb9:
                x816_lda_addry();
                break;
            case 0xbf:
                x816_lda_long_x();
                break;
            case 0xc2:
                x816_rep();
                break;
            case 0xc4:
                x816_cpy_dp();
                break;
            case 0xc6:
                x816_dec_dp();
                break;
            case 0xc8:
                x816_iny();
                break;
            case 0xc9:
                x816_cmp_const();
                break;
            case 0xca:
                x816_dex();
                break;
            case 0xcd:
                x816_cmp_addr();
                break;
            case 0xce:
                x816_dec_addr();
                break;
            case 0xd0:
                x816_bne();
                break;
            case 0xda:
                x816_phx();
                break;
            case 0xe2:
                x816_sep();
                break;
            case 0xe5:
                x816_sbc_dp();
                break;
            case 0xe6:
                x816_inc_dp();
                break;
            case 0xe8:
                x816_inx();
                break;
            case 0xe9:
                x816_sbc_const();
                break;
            case 0xeb:
                x816_xba();
                break;
            case 0xed:
                x816_sbc_addr();
                break;
            case 0xea:
                x816_nop();
                break;
            case 0xee:
                x816_inc_addr();
                break;
            case 0xf0:
                x816_beq();
                break;
            case 0xf4:
                x816_pea();
                break;
            case 0xfa:
                x816_plx();
                break;
            default:
                term = 1;
                break;
        }
        ic++;
    }
    
    if (x816.debug)
        printf("Executed %d instructions\n", ic);
}


void x816_free(void)
{
    free(x816.mem);
}
