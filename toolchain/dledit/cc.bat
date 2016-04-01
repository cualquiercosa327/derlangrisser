cl /nologo /O2 /wd4996 dledit.cpp /link kernel32.lib user32.lib gdi32.lib comdlg32.lib
@pause
@del *.obj