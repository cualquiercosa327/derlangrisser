#include <stdio.h>
#include <string.h>

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;

FILE  *fp,
      *wr;
char  linedata[65536];
ulong linenum = 0;
byte  lentbl[5][80];
byte  fontsel = 0;


void readline(void)
{
    int x,
        z = 0;
    
    while (!feof(fp)) {
        x = fgetc(fp);
        if (x == '\r');
        else if (x == '\n')
            break;
        else
            linedata[z++] = x;
    }
    linedata[z] = 0;
}


void calcline(void)
{
    int   i,
          x,
          x1,
          z,
          ssl = strlen(linedata);
    char  cmd[4096];
    ulong line_width = 0;
    
    for (i = 0; i < ssl; i++) {
        x = linedata[i];
        if (x == '{') {
            x1 = i;
            z = 0;
            while (i < ssl) {
                x = linedata[++i];
                if (x == '}')
                    break;
                cmd[z++] = x;
            }
            
            cmd[z] = 0;

            if (!strcmp(cmd, "font0"))
                fontsel = 0;
            if (!strcmp(cmd, "font1"))
                fontsel = 1;
            if (!strcmp(cmd, "font2"))
                fontsel = 2;
            if (!strcmp(cmd, "font3"))
                fontsel = 3;
            if (!strcmp(cmd, "font4"))
                fontsel = 4;
            if (!strcmp(cmd, "03")) {
                if (linedata[x1 - 1] != ' ')
                    line_width += 6;
                else
                    line_width += 12;
            }
            
            if (!strcmp(cmd, "37")) {
                x = 0x47;
                goto is_char;
            }
            
            if (!strcmp(cmd, "38")) {
                x = 0x46;
                goto is_char;
            }
            
            if (!strcmp(cmd, "39")) {
                x = 0x48;
                goto is_char;
            }
            
            if (!strcmp(cmd, "3a")) {
                x = 0x49;
                goto is_char;
            }
        }
        else {
            is_char:
            if (x == ' ')
                x = 0x00;
            else if (x >= 'A' && x <= 'Z')
                x -= 'A' - 0x01;
            else if (x >= 'a' && x <= 'z')
                x -= 'a' - 0x1b;
            else if (x >= '0' && x <= '9')
                x -= '0' - 0x35;
            else if (x == '.')
                x = 0x3f;
            else if (x == ',')
                x = 0x40;
            else if (x == '!')
                x = 0x41;
            else if (x == '?')
                x = 0x42;
            else if (x == '\'')
                x = 0x43;
            else if (x == '\"')
                x = 0x44;
            else if (x == '+')
                x = 0x4a;
            else if (x == '-')
                x = 0x4b;
            else if (x == ':')
                x = 0x4c;
            else if (x == '*')
                x = 0x4d;
            else if (x == '/')
                x = 0x4e;
            else if (x == '_')
                x = 0x4f;
            else
                printf("%c [%0.2x] not found in font\n", x, x);
            line_width += lentbl[fontsel][x];
        }
    }
    
    if (line_width > 176)
        printf("error: line %d too long [%s] - %d pixels, 176 max\n", linenum, linedata, line_width);

    fprintf(wr, "{skip %d}%s\r\n", (176 - line_width) >> 1, linedata);
}


int main()
{
    int  i;
    char fn[256];

    for (i = 0; i < 5; i++) {
        sprintf(fn, "build/fontv%d.bin", i);
        fp = fopen(fn, "rb");
        fread(lentbl[i], 1, 80, fp);
        fclose(fp);
    }
    
    fp = fopen("center_text.txt", "rb");
    wr = fopen("center_out.txt", "wb");
    
    while (!feof(fp)) {
        readline();
        if (feof(fp))
            break;
        calcline();
        linenum++;
    }
    
    fclose(fp);
    fclose(wr);
    
    printf("done\n");
    
    // getch();
    return 0;
}
