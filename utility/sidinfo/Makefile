#
# Configuration settings for Linux and generic UNIX
# See other Makefile.* files for more options.
#

# Compiler, flags and linker flags
PREFIX ?= /usr/local

BINTOOL_PREFIX =

CFLAGS += -DHAVE_CONFIG_H
LDFLAGS +=

CFLAGS += -DHAVE_ICONV

CFLAGS += -DHAVE_STRING_H
CFLAGS += -DHAVE_STDINT_H
#CFLAGS += -DHAVE_SYS_TYPES_H
CFLAGS += -DHAVE_INTTYPES_H

# Miscellaneous
BINPATH=./
OBJPATH=obj/unix/
EXEEXT=


###
### Stuff
###
ENDIANCHK_BIN=$(BINPATH)endianchk$(EXEEXT)
NOINST_TARGETS += $(ENDIANCHK_BIN) config.h
THLIBS_DEP += config.h

include Makefile.gen


install: all
	@for fn in $(TARGETS); do echo " INSTALL $$fn $(PREFIX)/bin/"; $(INSTALL) -m 755 "$$fn" $(PREFIX)/bin/; done
