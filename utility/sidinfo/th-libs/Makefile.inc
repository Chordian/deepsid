ifneq ($(BINTOOL_PREFIX),)
CC = $(BINTOOL_PREFIX)gcc
CXX = $(BINTOOL_PREFIX)g++
AR = $(BINTOOL_PREFIX)ar
RANLIB = $(BINTOOL_PREFIX)ranlib
else
CC ?= $(BINTOOL_PREFIX)gcc
CXX ?= $(BINTOOL_PREFIX)g++
AR ?= $(BINTOOL_PREFIX)ar
RANLIB ?= $(BINTOOL_PREFIX)ranlib
endif

INSTALL ?= install
MKDIR ?= mkdir
MKDIR_P ?= $(MKDIR) -p


THLIBS_A = $(OBJPATH)thlibs.a
THLIBS_DEP += $(THLIBS)th_types.h $(THLIBS)th_util.h


NOBUILD_TARGETS +=
NOINST_TARGETS += $(THLIBS_A)


COMPILE_OBJ = @if test ! -d $(OBJPATH); then mkdir -p $(OBJPATH); fi && echo " CC $<" && $(CC) $(CFLAGS) -c -o $@ $<
LINK_BIN = @if test ! -d $(BINPATH); then mkdir -p $(BINPATH); fi && echo " LINK $@" && $(CC) -o $@ $(filter %.o %.a,$+) $(LDFLAGS)


##
## Target rules
##
all: $(NOBUILD_TARGETS) $(NOINST_TARGETS) $(TARGETS)


$(THLIBS)th_string.c: $(addprefix $(THLIBS), th_printf1.c th_strmatch.c th_string.h)
	@touch $@


$(OBJPATH)%.o: $(THLIBS)%.c $(THLIBS_DEP)
	$(COMPILE_OBJ)

$(OBJPATH)%.o: $(THLIBS)%.c $(THLIBS)%.h $(THLIBS_DEP)
	$(COMPILE_OBJ) -I$(THLIBS)

$(OBJPATH)%.o: %.c %.h $(THLIBS_DEP)
	$(COMPILE_OBJ) -I$(THLIBS)

$(OBJPATH)%.o: %.c $(THLIBS_DEP)
	$(COMPILE_OBJ) -I$(THLIBS)


$(THLIBS_A): $(addprefix $(OBJPATH),$(THLIBS_OBJ))
	@echo " AR $@ $(THLIBS_OBJ)"
	@$(AR) cru $@ $(addprefix $(OBJPATH),$(THLIBS_OBJ))
	@echo " RANLIB $@"
	@$(RANLIB) $@

##
## Generate endian check and config.h
##
$(OBJPATH)endianchk.o: $(THLIBS)endianchk.c
	$(COMPILE_OBJ)

$(ENDIANCHK_BIN): $(OBJPATH)endianchk.o
	$(LINK_BIN)

config.h: $(ENDIANCHK_BIN)
	$(ENDIANCHK_BIN) > $@


##
## Special targets
##
clean:
	$(RM) $(TARGETS) $(NOINST_TARGETS) $(OBJPATH)*.o

srcclean: clean
	$(RM) *~

.PHONY: clean srcclean all
