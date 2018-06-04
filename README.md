# Der Langrisser Translation Tools

1. Place dl.rom in ./resources
2. Run `make toolchain`
3. Run `make dump`
4. Run `make`

To validate all script files use `make proper` (may be working?).

To remove all build files and the compiled toolchain use `make distclean`.

More information to come...

## Requirements

1. PHP 7.0+ installed in /usr/bin/php. This is available by default on all Macs running any version of OS X. For Linux desktops, you should install your relevant `php5-cli` package.
2. clang (on OS X) or gcc (on Linux). We have only tested with versions 4.1+.

Windows users are on their own.

