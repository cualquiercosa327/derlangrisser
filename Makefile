DLROM=./resources/dl.rom
CC=clang
CFLAGS=-stdlib=libc++ -lstdc++ -std=gnu++11 -O2 -fomit-frame-pointer
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
	@./toolchain/bin/decomp
	@echo "Converting graphics to bitmap..."
	@./toolchain/bin/dbconv
	@rm ./resources/decomp/*.bin

.PHONY: toolchain
toolchain:
	@mkdir -p ./toolchain/bin
	@echo "Building Der Langrisser toolchain..."
	$(CC) ./toolchain/custom/bdconv.c -o ./toolchain/bin/bdconv -I./toolchain/custom
	$(CC) -w ./toolchain/custom/bmptoimg.c -o ./toolchain/bin/bmptoimg -I./toolchain/custom
	$(CC) -w ./toolchain/custom/dbconv.c -o ./toolchain/bin/dbconv -I./toolchain/custom
	$(CC) -w ./toolchain/custom/dcconv.c -o ./toolchain/bin/dcconv -I./toolchain/custom
	$(CC) -w ./toolchain/custom/decomp.c -o ./toolchain/bin/decomp -I./toolchain/custom
	$(CC) ./toolchain/custom/makevwf8.c -o ./toolchain/bin/makevwf8 -I./toolchain/custom
	$(CC) -w ./toolchain/custom/makevwf12.c -o ./toolchain/bin/makevwf12 -I./toolchain/custom
	$(CC) ./toolchain/custom/text8d.c -o ./toolchain/bin/text8d -I./toolchain/custom
	$(CC) ./toolchain/custom/text8i.c -o ./toolchain/bin/text8i -I./toolchain/custom
	$(CC) ./toolchain/custom/text12d.c -o ./toolchain/bin/text12d -I./toolchain/custom
	$(CC) -w ./toolchain/custom/text12i.c -o ./toolchain/bin/text12i -I./toolchain/custom
	$(CC) ./toolchain/custom/text12ins.c -o ./toolchain/bin/text12ins -I./toolchain/custom
	@echo "Building xkas assembler..."
	$(CC) -w ./toolchain/xkas/xkas.cpp -o ./toolchain/bin/xkas -I./toolchain/xkas -lstdc++

.PHONY: distclean
distclean:
	rm -f ./build/*
	rm -f ./resources/scripts/text8c.txt
	rm -f ./resources/scripts/text8n.txt
	rm -f ./resources/scripts/jp/*.txt
	rm -f ./resources/decomp/*
	rm -f ./toolchain/bin/*

clean:
	rm -f ./build/*
