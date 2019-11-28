all: sidid.exe

sidid.exe: sidid.c
	c:\mingw\bin\gcc sidid.c -Wall -O3 -o sidid.exe
	c:\mingw\bin\strip sidid.exe
