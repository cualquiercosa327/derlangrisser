#include <stdio.h>

#define TAG_ENDWINDOW   0x00
#define TAG_ERWIN       0x02
#define TAG_VARIABLE    0x82    // 0x03
#define TAG_WAITFORKEY  0x06
#define TAG_CLEARWINDOW 0x07
#define TAG_LINEBREAK   0x08

#define TAG_FONT_0      0x90
#define TAG_FONT_1      0x91
#define TAG_FONT_2      0x92
#define TAG_FONT_3      0x93
#define TAG_FONT_4      0x94
#define TAG_FONT_5      0x95

#define TAG_SKIP        0x9f

#define CHR_SPACE       0x5c

typedef unsigned char byte;
typedef unsigned short word;
typedef unsigned long ulong;

FILE  *in,
      *out,
      *tbl;
ulong pt = 0x100000;
byte  len_tbl[128 * 6];
byte  write_buffer[4096];
ulong write_index,
      line_pixel_pos,
      line_pixel_pos_b,
      current_script;
int   center_offset = 0;

byte line_width[] = {
 // 000, 001, 002, 003, 004, 005, 006, 007, 008, 009, 00a, 00b, 00c, 00d, 00e, 00f,
      0,   0,   0, 192,   0,   0,   0,   0, 166, 166, 166, 166, 166, 166, 166, 166, // 0-15
    166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, // 16-31
    166, 166, 166, 166, 166, 166, 166,   0,   0,   0,   0, 166, 166, 166, 166, 166, // 32-47
    166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, // 48-63
    166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, 166, // 64-79
      0,   0, 166, 192, 192, 176, 184, 184, 166, 166, 166, 166, 166, 166            // 80-93
};

byte default_lines_per_window[] = {
    3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3,
    3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3,
    3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3,
    3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3,
    3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3,
    3, 3, 3, 3, 4, 3, 3, 3, 3, 3, 3, 3, 3, 3
};

byte lookup_table[] = {
    0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f, 0x10,
    0x11, 0x12, 0x13, 0x14, 0x15, 0x16, 0x17, 0x18, 0x19, 0x1a, 0x1b, 0x1c, 0x1d, 0x1e, 0x1f, 0x20,
    0x21, 0x22, 0x23, 0x24, 0x25, 0x26, 0x27, 0x28, 0x29, 0x2a, 0x2b, 0x2c, 0x2d, 0x00, 0x00, 0x00,
    0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
    0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x3f, 0x40, 0x41, 0x42, 0x43, 0x2e, 0x2f, 0x30, 0x31, 0x32,
    0x33, 0x34, 0x00, 0x00, 0x00, 0x00, 0x00, 0x35, 0x36, 0x37, 0x38, 0x39, 0x3a, 0x3b, 0x3c, 0x3d,
    0x3e, 0x44, 0x45, 0x46, 0x47, 0x48, 0x49, 0x4a, 0x4b, 0x4c, 0x4d, 0x4e, 0x4f, 0x00, 0x00, 0x00,
    0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00
};

#define insertbyte(__x) \
  for(z = write_index; z > l; z--) \
    write_buffer[z] = write_buffer[z-1]; \
    write_buffer[++l] = (__x); \
    write_index++


void write_byte(byte x)
{
    static byte lastbyte = 0;
    
    ulong i,
          l,
          z,
          start_of_line,
          end_of_line,
          inc_counter;
    int   remaining_pixels;
    byte  fontset = 0x90,
          linepos = 0,
          centered_flag = 0;
    byte  maxlines = default_lines_per_window[current_script];
    
    // Only perform formatting after reading in an entire window
    if (x == TAG_ENDWINDOW) {
        write_buffer[write_index++] = TAG_ENDWINDOW;
        l = line_pixel_pos = 0;
        
        while (l < write_index) {
            x = write_buffer[l];
            if (x == TAG_ENDWINDOW)
                break;
            
            // Regular letter?
            if (x >= 0x0a && x < 0x80)
                line_pixel_pos += len_tbl[(fontset - 0x90) * 128 + lookup_table[x - 0x0a]];
            
            // Max. length of name = 8 chars * 8 pixels
            if (x == TAG_ERWIN)
                line_pixel_pos += 64;
            
            // Max. length of variable = 5 chars * 7 pixels
            if (x == TAG_VARIABLE)
                line_pixel_pos += 35;
            
            if (x == TAG_SKIP)
                line_pixel_pos += write_buffer[++l];
            
            if (x == TAG_CLEARWINDOW) {
                line_pixel_pos = 0;
                linepos = 0;
                maxlines = 4;
                if (fontset != TAG_FONT_0) {
                    insertbyte(fontset);
                }
            }
            if (x == TAG_LINEBREAK) {
                line_pixel_pos = 0;
                linepos++;
                
                // If we have filled all available lines...
                if (linepos >= maxlines) {
                    linepos = 0;
                    maxlines = 4;
                    write_buffer[l] = TAG_WAITFORKEY;
                    insertbyte(TAG_CLEARWINDOW);
                }
                if (fontset != TAG_FONT_0) {
                    insertbyte(fontset);
                }
            }
            if (x >= TAG_FONT_0 && x <= TAG_FONT_5)
            fontset = x;
            
            // On a space, need to read ahead one word to see if the word
            // will fit on the current line
            if (x == CHR_SPACE) {
                i = l;
                line_pixel_pos_b = line_pixel_pos;
                while (i++ < write_index) {
                    x = write_buffer[i];
                    if (x == TAG_WAITFORKEY ||
                        x == TAG_CLEARWINDOW ||
                        x == TAG_LINEBREAK ||
                        x == CHR_SPACE || x == TAG_ENDWINDOW)
                        break;
                    
                    // Regular letter?
                    if (x >= 0x0a && x < 0x80)
                    line_pixel_pos_b +=
                        len_tbl[(fontset - 0x90) * 128 +
                        lookup_table[x - 0x0a]];
                    
                    if (x == TAG_ERWIN)
                        line_pixel_pos_b += 64;
                    if (x == TAG_VARIABLE)
                        line_pixel_pos_b += 35;
                    if (x == TAG_SKIP)
                        line_pixel_pos_b += write_buffer[++i];
                }
                
        // if we should be line wrapping...
        if (line_width[current_script] > 0) {

            // check to see if we have exceeded the line width
            if (line_pixel_pos_b > line_width[current_script]) {
            /*
             * this may be useful for detecting line wraps
             * when they are not wanted... if(current_script
             * == 84) { printf("line_width = %d - %0.2x %0.2x
             * %0.2x\n", line_pixel_pos_b, write_buffer[0],
             * write_buffer[1], write_buffer[2]); } 
             */
            line_pixel_pos = 0;
            linepos++;
            if (linepos >= maxlines) {    // yes, and we
                            // need a new
                            // window
                linepos = 0;
                maxlines = 4;
                write_buffer[l] = TAG_WAITFORKEY;
                insertbyte(TAG_CLEARWINDOW);
            } else
                write_buffer[l] = TAG_LINEBREAK;
            if (fontset != TAG_FONT_0) {    // yes, but we
                            // only need a new 
                            // line
                insertbyte(fontset);
            }
            }
        }
        }
        l++;
    }

    // centered_flag = 1 if window should be centered, this will skip
    // the center byte
    // inserted by the tag parser in main()
    fwrite(write_buffer + centered_flag, 1,
           write_index - centered_flag, out);
    pt += write_index - centered_flag;
    write_index = 0;

    } else {
    // prevent a space on the start of a new line
    if (x == CHR_SPACE
        && (lastbyte == TAG_CLEARWINDOW || lastbyte == TAG_LINEBREAK));
    else
        write_buffer[write_index++] = x;
    lastbyte = x;
    }
}


ulong strdectonum(char *str)
{
    ulong r = 0,
          m = 1;
    int   i,
          ssl = strlen(str);
    byte  x;
    
    for (i = 0; i < ssl; i++) {
        if (str[i] >= '0' && str[i] <= '9');
        else
            break;
    }
    
    for (--i; i >= 0; i--, m *= 10) {
        x = str[i] - '0';
        r += x * m;
    }
    
    return r;
}


void strlwr(char *str)
{
    while (*str) {
        if (*str >= 'A' && *str <= 'Z')
            *str += 0x20;
        str++;
    }
}


void strupr(char *str)
{
    while (*str) {
        if (*str >= 'a' && *str <= 'z')
            *str -= 0x20;
        str++;
    }
}


int main()
{
    ulong i,
          z,
          file_pos,
          temp,
          temp1,
          temp2,
          temp3;
    byte  x,
          quote_swap_flag = 0;
    char  fn[256],
          buffer[256];
    
    write_index = 0;
    
    tbl = fopen("build/fontv0.bin", "rb");
    fread(len_tbl, 1, 128, tbl);
    fclose(tbl);
    
    tbl = fopen("build/fontv1.bin", "rb");
    fread(len_tbl + 128, 1, 128, tbl);
    fclose(tbl);
    
    tbl = fopen("build/fontv2.bin", "rb");
    fread(len_tbl + 256, 1, 128, tbl);
    fclose(tbl);
    
    tbl = fopen("build/fontv3.bin", "rb");
    fread(len_tbl + 384, 1, 128, tbl);
    fclose(tbl);
    
    tbl = fopen("build/fontv4.bin", "rb");
    fread(len_tbl + 512, 1, 128, tbl);
    fclose(tbl);
    
    tbl = fopen("build/fontv5.bin", "rb");
    fread(len_tbl + 640, 1, 128, tbl);
    fclose(tbl);
    
    tbl = fopen("build/sc.bin", "wb");
    
    for (i = 0; i < 94; i++) {
        current_script = i;
        
        sprintf(fn, "resources/scripts/en/sc%0.2d.txt", i);
        in = fopen(fn, "rb");
        sprintf(fn, "build/sc%0.2d.bin", i);
        out = fopen(fn, "wb");
        
        fputc(pt, tbl);
        fputc(pt >> 8, tbl);
        fputc(pt >> 16, tbl);
        
        if (!in) {
            for (z = 0; z < 2048; z++)
                write_byte(0x00);
            fclose(out);
            continue;
        }
        
        while (!feof(in)) {
            while (!feof(in)) {
                x = fgetc(in);
                
                if (x == '{') {
                    z = 0;
                    while (!feof(in)) {
                        x = fgetc(in);
                        if (x == '}')
                            break;
                        buffer[z++] = x;
                    }
                    buffer[z] = 0;
                    strlwr(buffer);
                    if (!strcmp(buffer, "02"))
                        write_byte(0x02);
                    
                    // Use alternate tag to hook number routine print/finish,
                    // see font12.asm
                    if (!strcmp(buffer, "03")) {
                        write_byte(0x82);
                    }
                    
                    if (!strcmp(buffer, "06"))
                        write_byte(0x06);
                    if (!strcmp(buffer, "07")) {
                        write_byte(0x07);
                    
                        // Skip a line break, since {07} implies a new window
                        // and scripts line break for clarity here
                        temp1 = fgetc(in);
                        if (temp1 == '\r')
                            fgetc(in);
                    }
                    
                    if (!strcmp(buffer, "08"));
                        // write_byte(0x08);
                        // Ignored for DLedit 2007-05-02
                    
                    if (!strcmp(buffer, "37"))
                        write_byte(0x6e); // Accent a
                    
                    if (!strcmp(buffer, "38"))
                        write_byte(0x6d); // Umlaut a
                    
                    if (!strcmp(buffer, "39"))
                        write_byte(0x6f); // Circumflex a
                    
                    if (!strcmp(buffer, "3a"))
                        write_byte(0x70); // Umlaut o
                    
                    if (!strcmp(buffer, "oq"))
                        write_byte(0x6b); // Open quote
                    
                    if (!strcmp(buffer, "eq"))
                        write_byte(0x6c); // End quote
                    
                    if (!strcmp(buffer, "font0"))
                        write_byte(0x90);
                    
                    if (!strcmp(buffer, "font1"))
                        write_byte(0x91);
                    
                    if (!strcmp(buffer, "font2"))
                        write_byte(0x92);
                    
                    if (!strcmp(buffer, "font3"))
                        write_byte(0x93);
                    
                    if (!strcmp(buffer, "font4"))
                        write_byte(0x94);
                    
                    if (!strcmp(buffer, "font5"))
                        write_byte(0x95);
                    
                    // Deprecated: remove when unneeded
                    if (!strcmp(buffer, "center"));
                    
                    if (buffer[0] == 's' && buffer[1] == 'k'
                        && buffer[2] == 'i' && buffer[3] == 'p'
                        && buffer[4] == ' ') {
                        center_offset = strdectonum(buffer + 5);
                        write_byte(TAG_SKIP);
                        write_byte(center_offset);
                    }
                    
                    if (buffer[0] == '\\' && buffer[1] == 'x') {
                        temp1 = buffer[2];
                        temp2 = buffer[3];
                        if (temp1 >= '0' && temp1 <= '9')
                            temp1 = temp1 - '0';
                        if (temp1 >= 'A' && temp1 <= 'F')
                            temp1 = temp1 - 'A' + 0x0a;
                        if (temp1 >= 'a' && temp1 <= 'f')
                            temp1 = temp1 - 'a' + 0x0a;
                        if (temp2 >= '0' && temp2 <= '9')
                            temp2 = temp2 - '0';
                        if (temp2 >= 'A' && temp2 <= 'F')
                            temp2 = temp2 - 'A' + 0x0a;
                        if (temp2 >= 'a' && temp2 <= 'f')
                            temp2 = temp2 - 'a' + 0x0a;
                        write_byte((temp1 << 4) + temp2);
                    }
                    
                    if (!strcmp(buffer, "end")) {
                        write_byte(0x00);
                        
                        // Skip two lines as per formatting standards.
                        // Support both \n and \r\n formatting
                        temp1 = fgetc(in);
                        if (temp1 == '\r')
                            fgetc(in);
                        temp1 = fgetc(in);
                        if (temp1 == '\r')
                            fgetc(in);
                        quote_swap_flag = 0;
                        break;
                    }
                }
                else {
                    if (x >= 'A' && x <= 'Z')
                        write_byte((byte) (x - 'A' + 0x0a));
                    if (x >= 'a' && x <= 's')
                        write_byte((byte) (x - 'a' + 0x24));
                    if (x >= 't' && x <= 'z')
                        write_byte((byte) (x - 't' + 0x55));
                    if (x >= '0' && x <= '9')
                        write_byte((byte) (x - '0' + 0x61));
                    if (x == ' ')
                        write_byte(0x5c);
                    if (x == '.') {
                        write_byte(0x50);
                        temp3 = ftell(in);
                        
                        if (fgetc(in) == '.' && fgetc(in) == '.') {
                            // Match ellipsis pattern and
                            // add padding between each point
                            write_byte(TAG_SKIP);
                            write_byte(1);
                            write_byte(0x50);
                            write_byte(TAG_SKIP);
                            write_byte(1);
                            write_byte(0x50);
                        }
                        else {
                            // Otherwise, return to previous file position
                            fseek(in, temp3, SEEK_SET);
                        }
                    }
                    if (x == ',')
                        write_byte(0x51);
                    if (x == '!')
                        write_byte(0x52);
                    if (x == '?')
                        write_byte(0x53);
                    if (x == '\'')
                        write_byte(0x54);
                    if (x == '+')
                        write_byte(0x71);
                    if (x == ':')
                        write_byte(0x73);
                    if (x == '*')
                        write_byte(0x74);
                    if (x == '/')
                        write_byte(0x75);
                    if (x == '\n')
                        write_byte(0x08);
                        // write_byte(0x5c);
                        // Write new line for \n instead of space
                        // for DLedit 2007-05-02
                    if (x == '-') {
                        file_pos = ftell(in);
                        x = fgetc(in);
                        
                        if (x == '-')
                            write_byte(0x76);
                        else {
                            write_byte(0x72);
                            fseek(in, file_pos, SEEK_SET);
                        }
                    }
                    if (x == '\"') {
                        write_byte((byte) (0x6b + quote_swap_flag));
                        quote_swap_flag ^= 1;
                    }
                    if (x == '\r');
                }
            }
        }
        fclose(in);
        fclose(out);
    }
    fclose(tbl);
    return 0;
}
