#include <windows.h>
#include <commdlg.h>
#include "libbase.h"
#include "libstring.h"
#include "libstring.cpp"

void alert(char *s, ...) {
char str[4096];
va_list args;
  va_start(args, s);
  vsprintf(str, s, args);
  va_end(args);
  MessageBox(0, str, "dledit", MB_OK);
}

#define CTL_EDIT      100
#define CTL_L3        101
#define CTL_L4        102
#define CTL_W0        103
#define CTL_W1        104
#define CTL_W2        105
#define CTL_W3        106
#define CTL_STATIC    107
#define CTL_BLOCKUP   108
#define CTL_BLOCKDOWN 109
#define CTL_PARTUP    110
#define CTL_PARTDOWN  111
#define CTL_SAVE      112
#define CTL_SAVEEXIT  113

string script_fn;
string script_data, script_block, script_part;

struct {
int pos, count;
int part_pos, part_count;
}script = { 0, 1, 0, 1 };

HWND hwnd;
WNDPROC edit_wndproc;
HFONT hfont, hsfont;
BITMAPINFO bmi;

uint16 video_buffer[300 * 64];
byte scdata[300 * 64];
byte font[6][256 * 80];
byte lentbl[6][80];

/*
  2007-05-02:
  Force Windows to recognize LF-formatted text.
  To hell with speed.
*/

void GetDlgItemTextLf(HWND hwnd, UINT id, char *data, uint length) {
  GetDlgItemText(hwnd, id, data, length);
string temp;
  strcpy(temp, data);
  replace(temp, "\r", "");
  strcpy(data, strptr(temp));
}

void SetDlgItemTextLf(HWND hwnd, UINT id, const char *data) {
string temp;
  strcpy(temp, data);
  replace(temp, "\r", "");
  replace(temp, "\n", "\r\n");
  SetDlgItemText(hwnd, id, strptr(temp));
}

void VideoInit(void) {
  memset(&bmi, 0, sizeof(BITMAPINFO));
  bmi.bmiHeader.biSize        = sizeof(BITMAPINFOHEADER);
  bmi.bmiHeader.biWidth       = 300;
  bmi.bmiHeader.biHeight      = -64;
  bmi.bmiHeader.biPlanes      = 1;
  bmi.bmiHeader.biBitCount    = 16;
  bmi.bmiHeader.biSizeImage   = 300 * 64 * 2;
  bmi.bmiHeader.biCompression = BI_RGB;
}

void Redraw() {
  InvalidateRect(hwnd, 0, FALSE);
}

uint16 colortable[6] = { 0x0000, 0x03e0, 0x1ce7, 0x7fff, 0x7c00, 0x01e0 };

void RenderScene(void) {
uint16 *screen = video_buffer;
  for(int y=0;y<64;y++) {
    for(int x=0;x<300;x++) {
      *screen++ = colortable[scdata[y * 300 + x]];
    }
  }
}

uint32 current_font = 0;
uint8 _sq;
char datastr[65536];
int line_width = 192;
int line_count = 4;

void SetBorders(uint8 c) {
int x, y;
  for(y=0;y<64;y++) {
    for(x=0;x<300;x++) {
      if(x >= line_width || (y / 16) >= line_count)scdata[y * 300 + x] = c;
    }
  }
}

void DrawChar(uint8 c, int x, int y) {
int x1, y1, z;
int x0, y0;
  x0 = (c & 15) * 16;
  y0 = (c >> 4) * 16;
  for(y1=0;y1<16;y1++) {
    for(x1=0;x1<lentbl[current_font][c];x1++) {
      z = font[current_font][(y0 + y1) * 256 + (x0 + x1)];
      if(x + x1 < line_width) {
        scdata[(y + y1) * 300 + (x + x1)] = z;
      } else {
        SetBorders(4);
      }
    }
  }
}

#define CC_LINE   0x51
#define CC_FONT0  0x52
#define CC_FONT1  0x53
#define CC_FONT2  0x54
#define CC_FONT3  0x55
#define CC_FONT4  0x56
#define CC_FONT5  0x57
#define CC_ERWIN  0x58
#define CC_NUM    0x59
#define CC_SKIP   0x5a
#define CC_IGNORE 0x7f

void GetScriptText() {
  GetDlgItemTextLf(hwnd, CTL_EDIT, datastr, 65535);
  if(script.pos >= script.count) {
    script.pos = script.count - 1;
  }
  strcpy(script_block[script.pos], datastr);
  split(script_part, "{07}\n", script_block[script.pos]);
  script.part_count = count(script_part);
  if(script.part_pos >= script.part_count) {
    script.part_pos = script.part_count - 1;
  }
  strcpy(datastr, strptr(script_part[script.part_pos]));
}

void SelectText(int block, int part) {
  split(script_part, "{07}\n", script_block[block]);
  SetDlgItemTextLf(hwnd, CTL_EDIT, strptr(script_block[block]));
  Redraw();
  script.pos = block;
  script.part_pos = part;
  script.part_count = count(script_part);
}

void FormatText(void) {
char t[65536], cmd[4096];
int i, z, wp = 0;
int z0;
string t0;
  GetScriptText();
  _sq = 0;
  strcpy(t0, datastr);
  replace(t0, "...", ".{skip 1}.{skip 1}.");
  strcpy(datastr, strptr(t0));
  for(i=0;i<strlen(datastr);i++) {
    z = datastr[i];
    if(z >= 'A' && z <= 'Z')z -= 'A' - 0x01;
    else if(z >= 'a' && z <= 'z')z -= 'a' - 0x1b;
    else if(z >= '0' && z <= '9')z -= '0' - 0x35;
    else if(z == '.')z = 0x3f;
    else if(z == ',')z = 0x40;
    else if(z == '!')z = 0x41;
    else if(z == '?')z = 0x42;
    else if(z == '\'')z = 0x43;
    else if(z == '\"') {
      _sq ^= 1;
      z = 0x44 + (_sq ^ 1);
    }
    else if(z == '+')z = 0x4a;
    else if(z == '-') {
      if(datastr[i + 1] == '-') {
        z = 0x4f;
        i++;
      } else {
        z = 0x4b;
      }
    }
    else if(z == ':')z = 0x4c;
    else if(z == '*')z = 0x4d;
    else if(z == '/')z = 0x4e;
    else if(z == '\r')z = CC_IGNORE;
    else if(z == '\n') {
      z = CC_LINE;
    }
    else if(z == '{') {
      z0 = 0;
      i++;
      while(datastr[i] != '}' && i < strlen(datastr)) {
        cmd[z0++] = datastr[i++];
      }
      cmd[z0] = 0;
      if(datastr[i] == '}') {
        if     (strmatch(cmd, "end"))goto end_read;
        if     (strmatch(cmd, "07"))goto end_read;
        if     (strmatch(cmd, "font0"))z = CC_FONT0;
        else if(strmatch(cmd, "font1"))z = CC_FONT1;
        else if(strmatch(cmd, "font2"))z = CC_FONT2;
        else if(strmatch(cmd, "font3"))z = CC_FONT3;
        else if(strmatch(cmd, "font4"))z = CC_FONT4;
        else if(strmatch(cmd, "font5"))z = CC_FONT5;
        else if(strbegin(cmd, "skip")) {
          t[wp++] = CC_SKIP;
          z = strdec(cmd + 5) & 255;
        }
        else if(strmatch(cmd, "02"))z = CC_ERWIN;
        else if(strmatch(cmd, "03"))z = CC_NUM;
        else if(strmatch(cmd, "08")) {
          z = CC_LINE;
          if(datastr[i + 1] == '\r')i += 2;
          else if(datastr[i + 1] == '\n')i++;
        }
        else if(strmatch(cmd, "37"))z = 0x47;
        else if(strmatch(cmd, "38"))z = 0x46;
        else if(strmatch(cmd, "39"))z = 0x48;
        else if(strmatch(cmd, "3a"))z = 0x49;
        else if(strmatch(cmd, "oq"))z = 0x44;
        else if(strmatch(cmd, "eq"))z = 0x45;
        else {
          z = CC_IGNORE;
        }
      } else {
        z = CC_IGNORE;
      }
    }
    else z = 0x50;
    if(z != CC_IGNORE) {
      t[wp++] = z;
    }
  }
end_read:
  t[wp] = 0;
  strcpy(datastr, t);
}

uint8 ConvertChar(uint8 x) {
  if(x == 0x50)return 0x00;
  return x;
}

void RenderText(void) {
int i, z;
int x, y;
int xpos, ypos;
  memset(scdata, 0, 300 * 64);
  SetBorders(5);
  xpos = ypos = 0;
  FormatText();
  i = current_font = 0;
  while(i < strlen(datastr) && xpos < 256) {
    z = datastr[i++];
    z = ConvertChar(z);
    if(z == CC_LINE) {
      xpos = 0;
      ypos += 16;
      if((ypos / 16) >= line_count) {
        SetBorders(4);
        break;
      }
    } else if(z >= CC_FONT0 && z <= CC_FONT5) {
      current_font = z - CC_FONT0;
    } else if(z == CC_ERWIN) {
      DrawChar(0x4d, xpos, ypos);
      xpos += 64;
    } else if(z == CC_NUM) {
      DrawChar(0x4d, xpos, ypos);
      xpos += 35;
    } else if(z == CC_SKIP) {
      xpos += (uint8)datastr[i++];
    } else {
      DrawChar(z, xpos, ypos);
      xpos += lentbl[current_font][z];
    }
  }
}

void UpdateLabel();

void UpdateDisplay() {
RECT  rsrc, rdest;
POINT p;
  RenderText();
  RenderScene();

PAINTSTRUCT ps;
  BeginPaint(hwnd, &ps);
  SetDIBitsToDevice(ps.hdc, 5, 30, 300, 64, 0, 0, 0, 64, (void*)video_buffer, &bmi, DIB_RGB_COLORS);
  EndPaint(hwnd, &ps);

  UpdateLabel();
}

void UpdateLabel() {
char str[256];
  sprintf(str, "Width: %d, Lines: %d, Pos: %d of %d, Part: %d of %d", line_width, line_count,
    script.pos + 1, script.count,
    script.part_pos + 1, script.part_count);
  SetDlgItemTextLf(hwnd, CTL_STATIC, str);
}

bool _no_prompt_to_save = false;
void SaveScript(bool force);

long __stdcall WndProc(HWND hwnd, UINT msg, WPARAM wparam, LPARAM lparam) {
  switch(msg) {
  case WM_DESTROY:
    PostQuitMessage(0);
    break;
  case WM_PAINT:
    UpdateDisplay();
    break;
  case WM_COMMAND:
    switch(LOWORD(wparam)) {
    case CTL_L3:line_count = 3;Redraw();break;
    case CTL_L4:line_count = 4;Redraw();break;
    case CTL_W0:line_width = 166;Redraw();break;
    case CTL_W1:line_width = 176;Redraw();break;
    case CTL_W2:line_width = 184;Redraw();break;
    case CTL_W3:line_width = 192;Redraw();break;
    case CTL_BLOCKUP:
      if(script.pos > 0)script.pos--;
      script.part_pos = 0;
      SelectText(script.pos, 0);
      Redraw();
      break;
    case CTL_BLOCKDOWN:
      if(++script.pos >= script.count)script.pos = script.count - 1;
      script.part_pos = 0;
      SelectText(script.pos, 0);
      Redraw();
      break;
    case CTL_PARTUP:
      if(script.part_pos > 0)script.part_pos--;
      Redraw();
      break;
    case CTL_PARTDOWN:
    //calls GetScriptText which checks for script.part_pos boundaries
      script.part_pos++;
      Redraw();
      break;
    case CTL_SAVE:
      SaveScript(true);
      break;
    case CTL_SAVEEXIT:
      _no_prompt_to_save = true;
      SaveScript(true);
      PostQuitMessage(0);
      break;
    }
    break;
  }
  return DefWindowProc(hwnd, msg, wparam, lparam);
}

#define KeyDown(key) ((GetAsyncKeyState(key) & 0x8000)?1:0)
long __stdcall new_edit_wndproc(HWND hwnd, UINT msg, WPARAM wparam, LPARAM lparam) {
  switch(msg) {
  case WM_KEYUP:
    switch(wparam) {
    case VK_PRIOR:
      SendMessage(::hwnd, WM_COMMAND, (KeyDown(VK_LSHIFT) || KeyDown(VK_RSHIFT))?CTL_BLOCKUP:CTL_PARTUP, 0);
      return TRUE;
    case VK_NEXT:
      SendMessage(::hwnd, WM_COMMAND, (KeyDown(VK_LSHIFT) || KeyDown(VK_RSHIFT))?CTL_BLOCKDOWN:CTL_PARTDOWN, 0);
      return TRUE;
    }
    Redraw();
    break;
  }
  return edit_wndproc(hwnd, msg, wparam, lparam);
}

void CreateControls() {
  CreateWindowEx(WS_EX_STATICEDGE, "STATIC", "", WS_CHILD|WS_VISIBLE, 5, 5, 300, 20, hwnd, (HMENU)CTL_STATIC, GetModuleHandle(0), 0);
  CreateWindowEx(WS_EX_STATICEDGE, "EDIT", "", WS_CHILD|WS_VISIBLE|ES_MULTILINE|WS_HSCROLL|WS_VSCROLL, 5, 106, 300, 160, hwnd, (HMENU)CTL_EDIT, GetModuleHandle(0), 0);

  CreateWindow("BUTTON", "Block Up",   WS_CHILD|WS_VISIBLE,     5, 271, 75, 25, hwnd, (HMENU)CTL_BLOCKUP,   GetModuleHandle(0), 0);
  CreateWindow("BUTTON", "Block Down", WS_CHILD|WS_VISIBLE, 5+ 75, 271, 75, 25, hwnd, (HMENU)CTL_BLOCKDOWN, GetModuleHandle(0), 0);
  CreateWindow("BUTTON", "Part Up",    WS_CHILD|WS_VISIBLE, 5+150, 271, 75, 25, hwnd, (HMENU)CTL_PARTUP,    GetModuleHandle(0), 0);
  CreateWindow("BUTTON", "Part Down",  WS_CHILD|WS_VISIBLE, 5+225, 271, 75, 25, hwnd, (HMENU)CTL_PARTDOWN,  GetModuleHandle(0), 0);

  CreateWindow("BUTTON", "Width -> 166", WS_CHILD|WS_VISIBLE,     5, 296, 75, 25, hwnd, (HMENU)CTL_W0, GetModuleHandle(0), 0);
  CreateWindow("BUTTON", "Width -> 176", WS_CHILD|WS_VISIBLE, 5+ 75, 296, 75, 25, hwnd, (HMENU)CTL_W1, GetModuleHandle(0), 0);
  CreateWindow("BUTTON", "Width -> 184", WS_CHILD|WS_VISIBLE, 5+150, 296, 75, 25, hwnd, (HMENU)CTL_W2, GetModuleHandle(0), 0);
  CreateWindow("BUTTON", "Width -> 192", WS_CHILD|WS_VISIBLE, 5+225, 296, 75, 25, hwnd, (HMENU)CTL_W3, GetModuleHandle(0), 0);

  CreateWindow("BUTTON", "Lines -> 3", WS_CHILD|WS_VISIBLE,     5, 321, 75, 25, hwnd, (HMENU)CTL_L3, GetModuleHandle(0), 0);
  CreateWindow("BUTTON", "Lines -> 4", WS_CHILD|WS_VISIBLE, 5+ 75, 321, 75, 25, hwnd, (HMENU)CTL_L4, GetModuleHandle(0), 0);
  CreateWindow("BUTTON", "Save", WS_CHILD|WS_VISIBLE, 5+150, 321, 75, 25, hwnd, (HMENU)CTL_SAVE, GetModuleHandle(0), 0);
  CreateWindow("BUTTON", "Save + Exit", WS_CHILD|WS_VISIBLE, 5+225, 321, 75, 25, hwnd, (HMENU)CTL_SAVEEXIT, GetModuleHandle(0), 0);

  SendDlgItemMessage(hwnd, CTL_EDIT, WM_SETFONT, (WPARAM)hsfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_STATIC, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_BLOCKUP, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_BLOCKDOWN, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_PARTUP, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_PARTDOWN, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_W0, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_W1, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_W2, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_W3, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_L3, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_L4, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_SAVE, WM_SETFONT, (WPARAM)hfont, TRUE);
  SendDlgItemMessage(hwnd, CTL_SAVEEXIT, WM_SETFONT, (WPARAM)hfont, TRUE);

  edit_wndproc = (WNDPROC)GetWindowLong(GetDlgItem(hwnd, CTL_EDIT), GWL_WNDPROC);
  SetWindowLong(GetDlgItem(hwnd, CTL_EDIT), GWL_WNDPROC, (LONG)new_edit_wndproc);
}

void CreateEditorWindow() {
WNDCLASS wc;
  wc.cbClsExtra = 0;
  wc.cbWndExtra = 0;
  wc.hbrBackground = (HBRUSH)COLOR_WINDOW;
  wc.hCursor       = LoadCursor(0, IDC_ARROW);
  wc.hIcon         = LoadIcon(0, IDI_APPLICATION);
  wc.hInstance     = GetModuleHandle(0);
  wc.lpfnWndProc   = WndProc;
  wc.lpszClassName = "dledit";
  wc.lpszMenuName  = 0;
  wc.style         = CS_HREDRAW | CS_VREDRAW;
  RegisterClass(&wc);

  hwnd = CreateWindow("dledit", "DL Script Editor v0.07 ~byuu", WS_POPUP | WS_CAPTION | WS_SYSMENU | WS_MINIMIZEBOX | WS_VISIBLE,
    64, 64, 316, 376, 0, 0, wc.hInstance, 0);
}

void LoadFonts() {
int l, x, y;
uint32 c, d;
FILE *fp;
char fn[256];
  for(l=0;l<6;l++) {
    sprintf(fn, "fontv%d.bmp", l);
    fp = fopen(fn, "rb");
    if(!fp){MessageBox(hwnd, "Font file cannot be opened", "", MB_OK);exit(0);}
    for(y=0;y<80;y++) {
      fseek(fp, 0x36 + ((79 - y) * 256 * 3), SEEK_SET);
      for(x=0;x<256;x++) {
        c = fgetc(fp) | fgetc(fp) << 8 | fgetc(fp) << 16;
        if(c == 0x000000)d = 0;
        if(c == 0x00ff00)d = 1;
        if(c == 0x7f7f7f)d = 2;
        if(c == 0xffffff)d = 3;
        font[l][y * 256 + x] = d;
      }
    }
    fclose(fp);
    sprintf(fn, "fontv%d.bin", l);
    fp = fopen(fn, "rb");
    if(!fp){MessageBox(hwnd, "Font file cannot be opened", "", MB_OK);exit(0);}
    for(x=0;x<80;x++) {
      lentbl[l][x] = fgetc(fp);
    }
  }
}

void CreateGUIFonts(void) {
HDC hdc;
long height;
  hdc = GetDC(0);
  height = -MulDiv(8, GetDeviceCaps(hdc, LOGPIXELSY), 72);
  ReleaseDC(0, hdc);
  hfont = CreateFont(height, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "Tahoma");

  hdc = GetDC(0);
  height = -MulDiv(10, GetDeviceCaps(hdc, LOGPIXELSY), 72);
  ReleaseDC(0, hdc);
  hsfont = CreateFont(height, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "Arial");
}

bool GUIOpenFile(char *fn) {
OPENFILENAME ofn;
  *fn = 0;
  memset(&ofn, 0, sizeof(ofn));

  ofn.lStructSize = sizeof(ofn);
  ofn.hwndOwner = 0;
  ofn.lpstrFilter = "Der Langrisser script files (*.txt)\0*.txt\0All Files (*.*)\0*.*\0\0";
  ofn.lpstrFile = fn;
  ofn.nMaxFile = MAX_PATH;
  ofn.Flags = OFN_FILEMUSTEXIST;
  ofn.lpstrDefExt = "smc";

  if(GetOpenFileName(&ofn)) {
    return true;
  } else {
DWORD err = CommDlgExtendedError();
    alert("Common Dialog Error = %0.8x", err);
    return false;
  }
}

void LoadScript() {
FILE *fp;
  fp = fopen(strptr(script_fn), "rb");
  if(!fp){MessageBox(0, "Error loading script file", "", MB_OK);exit(0);}
  fseek(fp, 0, SEEK_END);
int fsize = ftell(fp);
  fseek(fp, 0, SEEK_SET);
char *data = (char*)malloc(fsize + 1);
  fread(data, 1, fsize, fp);
  data[fsize] = 0;
  strcpy(script_data, data);
  replace(script_data, "\r", "");
  replace(script_data, "{END}", "{end}");
  replace(script_data, "{3A}", "{3a}");
  split(script_block, "{end}\n\n", script_data);

  script.count = count(script_block) - 1; //last block is always null because it ends with {end} tag
}

void SaveScript(bool force) {
FILE *fp;
  if(force == false) {
    if(MessageBox(0, "Write modified script to file?", "", MB_YESNO) == IDNO) {
      MessageBox(0, "Script file was *not* saved, changes have been lost", "", MB_OK);
      return;
    }
  }
  fp = fopen(strptr(script_fn), "wb");
  if(!fp){MessageBox(0, "Error gaining write access to script file, cannot save", "", MB_OK);exit(0);}
  for(int i=0;i<script.count;i++) {
    fprintf(fp, "%s{end}\n\n", script_block[i]);
  }
  fclose(fp);
  if(force == false || _no_prompt_to_save == true) {
    MessageBox(0, "Script file has been saved successfully", "", MB_OK);
  }
}

int __stdcall WinMain(HINSTANCE hinstance, HINSTANCE hprevinstance, LPSTR lpcmdline, int ncmdshow) {
MSG msg;
char sfn[MAX_PATH + 2];
  LoadFonts();

  if(strmatch(lpcmdline, "")) {
    GUIOpenFile(sfn);
    strcpy(script_fn, sfn);
  } else {
    strcpy(script_fn, lpcmdline);
  }

  LoadScript();

  CreateGUIFonts();
  CreateEditorWindow();
  CreateControls();
  memset(&scdata, 0, 300 * 64);
  VideoInit();
  SelectText(0, 0);
  UpdateLabel();

  while(GetMessage(&msg, 0, 0, 0)) {
    if(msg.message == WM_QUIT)break;
    TranslateMessage(&msg);
    DispatchMessage(&msg);
  }

  if(_no_prompt_to_save == false)SaveScript(false);

  return 0;
}
