SIDId V1.09 - Quick & dirty HVSC playroutine identity scanner
Written by Cadaver (loorni@gmail.com), playroutine signatures provided by Ian
Coog, Ice00, Ninja, Yodelking, Wilfred/HVSC & Prof. Chaos. Playroutine info 
file from HVSC crew.

Usage: sidid [directory to scan] [options]

Options:
-a             Scan all files, not just those with .sid extension
-c<configfile> Configfile to use (env.variable SIDIDCFG can also be used)
-d             Do not recurse subdirs
-m             Scan each file for multiple signatures
-o             List only unidentified files
-s<playername> Scan only for specific player
-u             List also unidentified files
-? or --help   Display usage information

Redirect output to file as necessary.

For win32 systems only, the signature configuration file (sidid.cfg) is assumed
to be in the same dir as the executable. Otherwise, it has to be specified
either with the command line option or with the enviroment variable SIDIDCFG.

In the configfile, player signature names must not contain spaces and should be
under 24 letters for neat display. A signature consists of hexadecimal values
and ?? to accept any byte at that position. AND means to skip any number of 
bytes and then continue when the next byte is matched. END ends the current 
signature. Multiple signatures can exist for one player, see for example 
JCH_NewPlayer.

Good signatures should not contain any addresses, not even lowbytes and
preferably also not zeropage, unless it is known that the playroutine is never
zeropage-relocated.

No responsibility whatsoever is taken for the correctness of the existing
signatures! Additional sigs by Yodelking, Ian Coog, ice00 & Wilfred/HVSC.

Note: DigiOrganizer is listed last in the configfile, so it is not found by
default when a tune contains another recognized playroutine. Use -m to find
out all tunes which use it.


Changes:

V1.0    - Original
V1.01   - AND function added
V1.02   - Multiscan added
V1.03   - Listing of unidentified files added
        - Scanning of all files added
V1.04   - Added searching only for specific player
V1.05   - Added option to not recurse subdirs
V1.06   - Directory to scan can be given as an argument
        - Added option -c to specify the configfile
        - Added option -? to show usage information
V1.07   - Fixed AND function to work in the case where false first byte(s) of
          the sequence past AND are encountered before the proper sequence
V1.08   - List full filenames when scanning for one player only
V1.09   - Fixed not recognizing the last byte of a file as part of a sequence


Copyright (C) 2006-2015 by the author & contributors. All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice,
   this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. The name of the author may not be used to endorse or promote products
   derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ''AS IS'' AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.

