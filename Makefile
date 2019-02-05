DLROM=./resources/dl.rom
CC=clang
CFLAGS=-stdlib=libc++ -lstdc++ -O2 -fomit-frame-pointer -march=native
MD5=$(shell md5 -q $(DLROM))

all:
ifneq ($(MD5),91d62c4cb790fc2fb38b10b68616e228)
	$(error dl.rom md5 mismatch, expected 91d62c4cb790fc2fb38b10b68616e228)
endif
	@mkdir -p ./build
	@cp $(DLROM) ./build/dl.sfc
	@echo "Converting fonts to binary..."
	@./toolchain/bin/makevwf8
	@./toolchain/bin/makevwf12
	@echo "Converting scripts to binary..."
	@./toolchain/bin/text8i
	@./toolchain/bin/text12i
	@echo "Converting replacement graphics to binary..."
	@./toolchain/bin/bmptoimg
	@./toolchain/bin/bdconv
	@echo "Compressing graphics..."
	@./toolchain/bin/dcconv
	@echo "Assembling intro program..."
	@./toolchain/bin/xkas ./resources/asm/intro.asm ./build/dl.sfc
	@echo "Assembling decomp routines..."
	@./toolchain/bin/xkas ./resources/asm/decomp.asm ./build/dl.sfc
	@echo "Assembling replacement name table..."
	@./toolchain/bin/xkas ./resources/asm/name.asm ./build/dl.sfc
	@echo "Assembling 8x8 text fixes..."
	@./toolchain/bin/xkas ./resources/asm/text_a.asm ./build/dl.sfc
	@echo "Assembling 16x16 text fixes..."
	@./toolchain/bin/xkas ./resources/asm/text_b.asm ./build/dl.sfc
	@echo "Assembling various text fixes..."
	@./toolchain/bin/xkas ./resources/asm/text_c.asm ./build/dl.sfc
	@echo "Assembling new menu positions..."
	@./toolchain/bin/xkas ./resources/asm/window.asm ./build/dl.sfc
	@echo "Assembling 12x12 font display code..."
	@./toolchain/bin/xkas ./resources/asm/font12.asm ./build/dl.sfc
	@echo "Assembling 8x8 font display code..."
	@./toolchain/bin/xkas ./resources/asm/font8.asm ./build/dl.sfc
	@echo "Inserting game script..."
	@./toolchain/bin/text12ins ./build/dl.sfc
	@echo "Build of ./build/dl.sfc complete!"

.PHONY: proper
proper:
	@echo "Validating game scripts..."
	@./toolchain/proper.php

.PHONY: dump
dump:
ifneq ($(MD5),91d62c4cb790fc2fb38b10b68616e228)
	$(error dl.rom md5 mismatch, expected 91d62c4cb790fc2fb38b10b68616e228)
endif
	@echo "Dumping game scripts..."
	@mkdir -p ./resources/scripts/jp
	@./toolchain/dump.php
	@echo "Dumping 8x8 text..."
	@./toolchain/bin/text8d
	@echo "Dumping game graphics..."
	@mkdir -p ./resources/decomp
	@mkdir -p ./resources/decomp/portraits
	@mkdir -p ./resources/decomp/sprites
	@./toolchain/bin/decomp
	@echo "Converting graphics to bitmap..."
	@./toolchain/bin/dbconv
	@./toolchain/bin/ptobmp
	@rm ./resources/decomp/*.bin
	@echo "Decompiling event scripts..."
	@mkdir -p ./resources/scripts/event
	@./toolchain/decompev.php &>/dev/null

.PHONY: ips
ips:
ifneq ($(MD5),91d62c4cb790fc2fb38b10b68616e228)
        $(error dl.rom md5 mismatch, expected 91d62c4cb790fc2fb38b10b68616e228)
endif
	@echo "Creating IPS patch..."
	@./toolchain/createips.php

.PHONY: toolchain
toolchain:
	@mkdir -p ./toolchain/bin
	@echo "Building Der Langrisser toolchain..."
	$(CC) $(CFLAGS) ./toolchain/custom/bdconv.c -o ./toolchain/bin/bdconv
	$(CC) $(CFLAGS) -w ./toolchain/custom/bmptoimg.c -o ./toolchain/bin/bmptoimg
	$(CC) $(CFLAGS) -w ./toolchain/custom/dbconv.c -o ./toolchain/bin/dbconv
	$(CC) $(CFLAGS) -w ./toolchain/custom/dcconv.c -o ./toolchain/bin/dcconv
	$(CC) $(CFLAGS) -w ./toolchain/custom/decomp.c -o ./toolchain/bin/decomp
	$(CC) $(CFLAGS) ./toolchain/custom/makevwf8.c -o ./toolchain/bin/makevwf8
	$(CC) $(CFLAGS) -w ./toolchain/custom/makevwf12.c -o ./toolchain/bin/makevwf12
	$(CC) $(CFLAGS) -w ./toolchain/custom/ptobmp.c -o ./toolchain/bin/ptobmp
	$(CC) $(CFLAGS) ./toolchain/custom/text8d.c -o ./toolchain/bin/text8d
	$(CC) $(CFLAGS) ./toolchain/custom/text8i.c -o ./toolchain/bin/text8i
	$(CC) $(CFLAGS) ./toolchain/custom/text12d.c -o ./toolchain/bin/text12d
	$(CC) $(CFLAGS) -w ./toolchain/custom/text12i.c -o ./toolchain/bin/text12i
	$(CC) $(CFLAGS) ./toolchain/custom/text12ins.c -o ./toolchain/bin/text12ins
	@echo "Building xkas assembler..."
	$(CC) $(CFLAGS) -w ./toolchain/xkas/xkas.cpp -o ./toolchain/bin/xkas

.PHONY: distclean
distclean:
	rm -f ./build/*
	rm -f ./resources/scripts/text8c.txt
	rm -f ./resources/scripts/text8n.txt
	rm -f ./resources/scripts/jp/*.txt
	rm -rf ./resources/decomp/*
	rm -f ./toolchain/bin/*

clean:
	rm -f ./build/*
