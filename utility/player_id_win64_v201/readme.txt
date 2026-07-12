

                             Player-ID v2.01

                    Copyright © 2021-2023 Wilfred Bos
                 https://github.com/WilfredC64/player-id


INTRODUCTION
============

Player-ID a.k.a. C64 Music Player Identifier (PI) is a cross-platform utility
to identify Commodore 64 music players in SID files.

Player-ID is inspired by the SIDId tool written by Cadaver of Covert Bitops.
Player-ID makes use of all available cores of the CPU and uses the BNDM
(Backward Nondeterministic Dawg Matching) search algorithm to search through
files very quickly.


USAGE
=====

Usage: player-id <options> <file_path_pattern>

<file_path_pattern>

  The file_path_pattern can be any SID or other filename. You can use wildcards
  to process multiple SID and PRG files. You may want to use the -s option to
  process sub folders as well. If you have spaces in the filename or in the
  folder name then surround the folder and filename with double quotes.

Examples:

  *.sid
  tune?.sid
  "C:\my c64 music collection\sids*.sid"
  C:\HVSC\C64Music*.sid
  ~"/HVSC/C64Music/*.sid"

<options>

  -c{max_threads}: set the maximum CPU threads to be used [Default is all]

    Use the -c option to limit CPU thread usage. By default, it will use all
    available CPU threads. This tool is optimized for running on multiple CPUs
    or on CPUs with multiple cores. The more CPU threads it can use, the faster
    the searches will be.

  -f{config_file}: config file [Default SIDIDCFG env. var. / sidid.cfg file]

    Use the -f option if you want to use a different config file than the
    default.
    If the config file is not specified by the -f option, then it will try to
    find the "sidid.cfg" file via the SIDIDCFG environment variable setting. If
    the variable is not present then it will try to find the "sidid.cfg" file
    in the same directory as where player-id is located.

  -h: scan HVSC location [Uses HVSC environment variable for HVSC path]

    Use the -h option to scan the HVSC location for known players. The HVSC
    location needs to be specified via the environment variable HVSC. Using
    this option will also set the file_path_pattern to *.sid when it is not
    specified, and it will also search through subdirectories.

  -n: show player info [use together with -p option]

    Use the -n option to show the player info, if available. You'll need to
    specify the player ID with the -p option.

  -m: scan for multiple signatures

    Use the -m option if you want to scan files for multiple signatures. Some
    SID files contain multiple players. When the -m option is not specified
    only the first found player will be returned. The first found player is
    dependent on the order of the player signatures in the sidid.cfg file. When
    a player is found multiple times in the file, the -m option will only
    return the player name once.

  -o: list only unidentified files

    Use the -o option if you're only interested in a list of files that could
    not be identified.

  -p{player_name}: scan only for specific player name

    Use the -p option if you only want to scan for a specific player name. For
    the list of player names you can check the sidid.cfg file. A player name
    can't contain spaces and is case-insensitive.

  -s: include subdirectories

    Use the -s option if you want to search multiple files through multiple
    subdirectories. When you use the index via the -h option, then you don't
    have to specify the -s option.

  -t: truncate filenames

    Use the -t option to truncate the filenames so that the signatures found
    column is always at the same column. When the -t isn't used, player-id will
    set the signatures found column based on the longest filename.

  -u: list also unidentified files

    Use the -u option if you're also interested in files that could not be
    identified. All files that are scanned will be listed.

  -v: verify signatures

    Use the -v option if you want to verify signatures for errors. This option
    is useful when you create your own signatures. This option will also verify
    the info file (sidid.nfo) when it's found.

  -wn: write signatures in new format

    Use the -wn option if you want to write a signatures file to the new file
    format (V2).

  -wo: write signatures in old format

    Use the -wo option if you want to write a signatures file to the old file
    format (V1).

  -x: display hexadecimal offset of signature found

    Use the -x option if you want to display the hexadecimal offset where the
    signature has been found. When a signature uses an AND/&& token then it
    will display all the offsets of the sub signatures.


EXAMPLES
========

For searching through all the SID files in HVSC:

  player-id -s "C:\HVSC\C64Music\*.sid"

For identifying multiple signatures in all the SID files in HVSC:

  player-id -s -m "C:\HVSC\C64Music\*.sid"

For scanning HVSC:

  player-id -h

For scanning HVSC and identify multiple signatures:

  player-id -h -m

For scanning HVSC for a specific file pattern:

  player-id -h Commando*.sid

For scanning files that include e.g. SoundMonitor player:

  player-id -pSoundMonitor -s "C:\HVSC\C64Music\*.sid"

For retrieving the player info of e.g. SoundMonitor:

  player-id -pSoundMonitor -n


SIGNATURE FILE FORMAT
=====================

The signature file format is described in file:

Signature_File_Format.txt


DISCLAIMER
==========

Player-ID comes with absolutely no warranty. The author is not liable for any
damage in any event as a result of using Player-ID.

If you experience any problem using Player-ID, please let the author know.


THANKS
======

Thanks to iAN CooG for all the beta testing.

Thanks to Cadaver of Covert Bitops for making the SIDId tool.

Signatures created by: Wilfred Bos, iAN CooG, Professor Chaos, Cadaver, Ice00,
Ninja and Yodelking.


CONTACT INFORMATION
===================

Feel free to send me an e-mail for your feedback, questions or to report bugs.

email: wilfred_hvsc@xs4all.nl
