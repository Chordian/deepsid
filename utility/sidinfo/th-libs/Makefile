#
# Configuration settings for Linux and generic UNIX
# See other Makefile.* files for more options.
#

# Miscellaneous
BINTOOL_PREFIX =
PREFIX = /usr/local
BINPATH = ./
OBJPATH = obj/unix/
EXEEXT =

# Compiler flags and linker flags
CFLAGS += -DHAVE_CONFIG_H
LDFLAGS +=

CFLAGS += -DHAVE_STRING_H
CFLAGS += -DHAVE_STDINT_H
#CFLAGS += -DHAVE_SYS_TYPES_H
CFLAGS += -DHAVE_INTTYPES_H


###
### Unix targets
###
ENDIANCHK_BIN = $(BINPATH)endianchk$(EXEEXT)
NOINST_TARGETS += $(ENDIANCHK_BIN) config.h
THLIBS_DEP += config.h

include Makefile.gen
