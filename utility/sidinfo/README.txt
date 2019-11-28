SIDInfo - PSID/RSID information displayer
Programmed and designed by Matti 'ccr' Hämäläinen
(C) Copyright 2014-2018 Tecnic Software productions (TNSP)

See COPYING for license information.

-----------------------------------------------------------------------

Contact
=======
IRC    : ccr @ IRCNet, sometimes also Freenode
e-mail : ccr@tnsp.org

-----------------------------------------------------------------------

Requirements
============
The compilation and usage of these utilities requires following
software packages or functional equivalents installed:

 - GCC (4.x, older ones might work as well)
 - GNU binutils
 - GNU Make

 - th-libs library (included in the tar/zip packages,
   for building from mercurial repo, see "how to build")

 - libiconv (optional, for converting ISO-8859/Latin-1 encoded
   text used in HVSC SID files and STIL database to whatever
   character set your system is using, like UTF-8.)


For Linux -> Win32/64 cross-compilation I have used the standard
MinGW packages from Debian Testing (wheezy):

gcc-mingw-w64 mingw-w64-i686-dev mingw-w64-x86-64-dev

Some of those require a bit of poking to get working properly, YMMV.

Please don't ask me for help to get cross-compilation working.

-----------------------------------------------------------------------

How to build
============
0) If building from Mercurial repo, you need th-libs:

   $ hg clone http://tnsp.org/hg/th-libs

1) Possibly edit Makefile / Makefile.w32

2) $ make

   or, if cross-compiling to Win32 via MinGW

   $ make -f Makefile.w32

3) ???

4) If it works -> Happy fun times \:D\

5) sudo make install


-----------------------------------------------------------------------

Usage
=====
For more information about options, see 'sidinfo --help'.

--- --- --- --- --- --- --- --- --- ---

Display all information about one file in "entry per row" format:

$ sidinfo /misc/C64Music/MUSICIANS/J/Jeff/Anal_ogue.sid
Filename             : /misc/C64Music/MUSICIANS/J/Jeff/Anal_ogue.sid
Type                 : PSID
Version              : 2.0
Data offset          : 124
Data size            : 7154
Load address         : 0
Init address         : 4017
Play address         : 4027
Songs                : 1
Start song           : 1
Name                 : Anal'ogue
Author               : Søren Lund (Jeff)
Copyright            : 1996 Jeff
Hash                 : 6d5b7f0ff092e55abf27c37c8bc3fc64

--- --- --- --- --- --- --- --- --- ---

Display in "parseable" INI-style format, with hexadecimal values:

$ sidinfo /misc/C64Music/MUSICIANS/J/Jeff/Anal_ogue.sid -p -x
Filename=/misc/C64Music/MUSICIANS/J/Jeff/Anal_ogue.sid
Type=PSID
Version=2.0
DataOffs=$0000007c
DataSize=$00001bf2
LoadAddr=$0000
InitAddr=$0fb1
PlayAddr=$0fbb
Songs=$0001
StartSong=$0001
Name=Anal'ogue
Author=Søren Lund (Jeff)
Copyright=1996 Jeff
Hash=6d5b7f0ff092e55abf27c37c8bc3fc64

--- --- --- --- --- --- --- --- --- ---

One-line format with "|" as field separator, and specify
which fields to display:

$ sidinfo -l \| -f type,ver,hash,name,author,copyright Anal_ogue.sid

PSID|2.0|Anal'ogue|Søren Lund (Jeff)|1996 Jeff|6d5b7f0ff092e55abf27c37c8bc3fc64|

--- --- --- --- --- --- --- --- --- ---

By using the format string functionality you can control
the output very specifically:

$ sidinfo Anal_ogue.sid -F 'NAME="@name@"\nHASH=@hash@\n'

NAME="Anal'ogue"
HASH=6d5b7f0ff092e55abf27c37c8bc3fc64

--- --- --- --- --- --- --- --- --- ---

You could, for example create SQL INSERT statements:

$ sidinfo Anal_ogue.sid -e\' -F "INSERT INTO sometable (filename,name,author) VALUES ('@filename@', '@name@', '@author@', '@copyright@')\n"

INSERT INTO sometable (filename,name,author) VALUES ('./Anal_ogue.sid', 'Anal\'ogue', 'Søren Lund (Jeff)', '1996 Jeff')

--- --- --- --- --- --- --- --- --- ---

Furthermore, you can use "printf"-style format specifiers for
formatting each @field@, see this example:

$ sidinfo Anal_ogue.sid -F 'NAME=@name:"%-64s"@\nHASH=@hash:"%64s"@\nLOAD_ADDR=@loadaddr:$%04x@\n'
NAME="Anal'ogue                                                       "
HASH="                                6d5b7f0ff092e55abf27c37c8bc3fc64"
LOAD_ADDR=$0fb0

Many of the format specifiers are supported, but not all, and obviously
only integer/string formats are supported.

--- --- --- --- --- --- --- --- --- ---

Since sidinfo v0.7.6 it is also possible to automatically scan
and recurse directories via '-R' option, for example:

$ sidinfo -R /misc/C64Music/*.sid

The above will start from /misc/C64Music/ and scan any accessible
subdirectories for files that match "*.sid" pattern and handle them.

Using previous example about SQL inserts:

$ sidinfo /misc/C64Music/*.sid -R -e\' -F "INSERT INTO sometable (filename,name,author) VALUES ('@filename@', '@name@', '@author@', '@copyright@')\n"

