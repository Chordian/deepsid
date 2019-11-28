/*
 * SIDInfo - PSID/RSID information displayer
 * Programmed and designed by Matti 'ccr' Hämäläinen <ccr@tnsp.org>
 * (C) Copyright 2014-2018 Tecnic Software productions (TNSP)
 */
#include "th_args.h"
#include "th_string.h"
#include "th_file.h"
#include "th_datastruct.h"
#include "sidlib.h"
#include <sys/types.h>
#include <dirent.h>
#ifdef HAVE_ICONV
#  include <iconv.h>
#endif


// Some constants
#define SET_DEF_CHARSET   "utf8" // NOTE! Do not change unless you are using iconv()!!
                                 // The fallback converter does not handle other encodings.

#define SET_SLDB_FILEBASE "Songlengths"


enum
{
    OFMT_QUOTED    = 0x0001,
    OFMT_FORMAT    = 0x0002,
};


enum
{
    OTYPE_OTHER    = 0,
    OTYPE_STR      = 1,
    OTYPE_INT      = 2,
};


typedef struct
{
    int cmd;
    char *str;
    char chr;
    int flags;
    char *fmt;
} PSFStackItem;


typedef struct
{
    int nitems, nallocated;
    PSFStackItem *items;
} PSFStack;


typedef struct
{
    char *name;
    char *lname;
    int type;
} PSFOption;


static const PSFOption optPSOptions[] =
{
    { "Filename"     , NULL                   , OTYPE_STR },
    { "Type"         , NULL                   , OTYPE_STR },
    { "Version"      , NULL                   , OTYPE_STR },
    { "PlayerType"   , "Player type"          , OTYPE_STR },
    { "PlayerCompat" , "Player compatibility" , OTYPE_STR },
    { "VideoClock"   , "Video clock speed"    , OTYPE_STR },
    { "SIDModel"     , "SID model"            , OTYPE_STR },

    { "DataOffs"     , "Data offset"          , OTYPE_INT },
    { "DataSize"     , "Data size"            , OTYPE_INT },
    { "LoadAddr"     , "Load address"         , OTYPE_INT },
    { "InitAddr"     , "Init address"         , OTYPE_INT },
    { "PlayAddr"     , "Play address"         , OTYPE_INT },
    { "Songs"        , "Songs"                , OTYPE_INT },
    { "StartSong"    , "Start song"           , OTYPE_INT },

    { "SID2Model"    , "2nd SID model"        , OTYPE_INT },
    { "SID3Model"    , "3rd SID model"        , OTYPE_INT },
    { "SID2Addr"     , "2nd SID address"      , OTYPE_INT },
    { "SID3Addr"     , "3rd SID address"      , OTYPE_INT },

    { "Name"         , NULL                   , OTYPE_STR },
    { "Author"       , NULL                   , OTYPE_STR },
    { "Copyright"    , NULL                   , OTYPE_STR },
    { "Hash"         , NULL                   , OTYPE_STR },

    { "Songlengths"  , "Song lengths"         , OTYPE_OTHER },
};

static const int noptPSOptions = sizeof(optPSOptions) / sizeof(optPSOptions[0]);


// Option variables
char   *setHVSCPath = NULL,
       *setSLDBPath = NULL;
BOOL	setSLDBNewFormat = FALSE,
        optParsable = FALSE,
        optNoNamePrefix = FALSE,
        optHexadecimal = FALSE,
        optFieldOutput = TRUE,
        optRecurseDirs = FALSE;
char    *optOneLineFieldSep = NULL,
        *optEscapeChars = NULL;
int     optNFiles = 0;

PSFStack optFormat;

SIDLibSLDB *sidSLDB = NULL;

BOOL    setUseChConv;
#ifdef HAVE_ICONV
iconv_t setChConv;
#endif


// Define option arguments
static const th_optarg optList[] =
{
    { 0, '?', "help",       "Show this help", OPT_NONE },
    { 1, 'v', "verbose",    "Be more verbose", OPT_NONE },
    {10,   0, "license",    "Print out this program's license agreement", OPT_NONE },

    { 2, 'p', "parsable",   "Output in script-parsable format", OPT_NONE },
    { 5, 'n', "noprefix",   "Output without field name prefix", OPT_NONE },
    { 6, 'l', "line",       "Output in one line format, -l <field separator>", OPT_ARGREQ },
    {11, 'e', "escape",     "Escape these characters in fields (see note)", OPT_ARGREQ },
    { 3, 'f', "fields",     "Show only specified field(s)", OPT_ARGREQ },
    { 4, 'x', "hex",        "Use hexadecimal values", OPT_NONE },
    { 7, 'F', "format",     "Use given format string (see below)", OPT_ARGREQ },
    { 8, 'H', "hvsc",       "Specify path to HVSC documents directory", OPT_ARGREQ },
    { 9, 'S', "sldb",       "Specify Songlengths.(txt|md5) file (use -H if possible)", OPT_ARGREQ },
    {12, 'R', "recurse",    "Recurse into sub-directories", OPT_NONE },
};

static const int optListN = sizeof(optList) / sizeof(optList[0]);


void argShowLicense(void)
{
    printf("%s - %s\n%s\n", th_prog_name, th_prog_desc, th_prog_author);
    printf(
    "\n"
    "Redistribution and use in source and binary forms, with or without\n"
    "modification, are permitted provided that the following conditions\n"
    "are met:\n"
    "\n"
    " 1. Redistributions of source code must retain the above copyright\n"
    "    notice, this list of conditions and the following disclaimer.\n"
    "\n"
    " 2. Redistributions in binary form must reproduce the above copyright\n"
    "    notice, this list of conditions and the following disclaimer in\n"
    "    the documentation and/or other materials provided with the\n"
    "    distribution.\n"
    "\n"
    " 3. The name of the author may not be used to endorse or promote\n"
    "    products derived from this software without specific prior written\n"
    "    permission.\n"
    "\n"
    "THIS SOFTWARE IS PROVIDED BY THE AUTHOR \"AS IS\" AND ANY EXPRESS OR\n"
    "IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED\n"
    "WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE\n"
    "ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,\n"
    "INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES\n"
    "(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR\n"
    "SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)\n"
    "HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,\n"
    "STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING\n"
    "IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE\n"
    "POSSIBILITY OF SUCH DAMAGE.\n"
    );
}


void argShowHelp(void)
{
    int index, len;

    th_print_banner(stdout, th_prog_name, "[options] <sid filename> [sid filename #2 ..]");
    th_args_help(stdout, optList, optListN, 0);
    printf(
        "\n"
        "Available fields:\n");

    for (len = index = 0; index < noptPSOptions; index++)
    {
        const PSFOption *opt = &optPSOptions[index];
        len += strlen(opt->name) + 3;
        if (len >= 72)
        {
            printf("\n");
            len = 0;
        }
        printf("%s%s", opt->name, (index < noptPSOptions - 1) ? ", " : "\n\n");
    }

    printf(
        "Example: %s -x -p -f hash,copyright somesidfile.sid\n"
        "\n"
        "Format strings for '-F' option are composed of @fields@ that\n"
        "are expanded to their value. Also, escape sequences \\r, \\n and \\t\n"
        "can be used: -F \"hash=@hash@\\ncopy=@copyright@\\n\"\n"
        "\n"
        "The -F fields can be further formatted via printf-style specifiers:\n"
        "-F \"@copyright:'%%-30s'@\"\n"
        "\n"
        "When specifying HVSC path, it is preferable to use -H/--hvsc option,\n"
        "as STIL.txt and Songlengths.txt will be automatically used from there.\n"
        "\n"
        "NOTE: One line output (-l <field separator>) also sets escape characters\n"
        "(option -e <chars>), if escape characters have NOT been separately set.\n"
        , th_prog_name);
}


int argMatchPSField(const char *field)
{
    int index, found = -1;
    for (index = 0; index < noptPSOptions; index++)
    {
        const PSFOption *opt = &optPSOptions[index];
        if (th_strcasecmp(opt->name, field) == 0)
        {
            if (found >= 0)
                return -2;
            found = index;
        }
    }

    return found;
}


int argMatchPSFieldError(const char *field)
{
    int found = argMatchPSField(field);
    switch (found)
    {
        case -1:
            THERR("No such field '%s'.\n", field);
            break;

        case -2:
            THERR("Field '%s' is ambiguous.\n", field);
            break;
    }
    return found;
}


BOOL siStackAddItem(PSFStack *stack, const PSFStackItem *item)
{
    if (stack->items == NULL || stack->nitems + 1 >= stack->nallocated)
    {
        stack->nallocated += 16;
        if ((stack->items = th_realloc(stack->items, stack->nallocated * sizeof(PSFStackItem))) == NULL)
        {
            THERR("Could not allocate memory for format item stack.\n");
            return FALSE;
        }
    }

    memcpy(stack->items + stack->nitems, item, sizeof(PSFStackItem));
    stack->nitems++;
    return TRUE;
}


void siClearStack(PSFStack *stack)
{
    if (stack != NULL)
    {
        if (stack->nitems > 0 && stack->items != NULL)
        {
            int n;
            for (n = 0; n < stack->nitems; n++)
            {
                if (stack->items[n].cmd == -1)
                    th_free(stack->items[n].str);
            }
            th_free(stack->items);
        }
        memset(stack, 0, sizeof(PSFStack));
    }
}


BOOL argParsePSFields(PSFStack *stack, const char *fmt)
{
    const char *start = fmt;
    siClearStack(stack);

    while (*start)
    {
        PSFStackItem item;
        const char *end = strchr(start, ',');
        char *field = (end != NULL) ?
            th_strndup_trim(start, end - start, TH_TRIM_BOTH) :
            th_strdup_trim(start, TH_TRIM_BOTH);

        if (field != NULL)
        {
            int found = argMatchPSFieldError(field);
            th_free(field);

            if (found < 0)
                return FALSE;

            item.cmd = found;
            item.str = NULL;
            if (!siStackAddItem(stack, &item))
                return FALSE;
        }

        if (!end)
            break;

        start = end + 1;
    }

    return TRUE;
}


char *siConvertCharset(const char *src)
{
#ifdef HAVE_ICONV
    size_t srcLeft = strlen(src) + 1;
    size_t outLeft = srcLeft * 2;
    char *srcPtr = (char *) src;
    char *outBuf, *outPtr;

    if ((outBuf = outPtr = th_malloc(outLeft + 1)) == NULL)
        return NULL;

    while (srcLeft > 0)
    {
        size_t ret = iconv(setChConv, &srcPtr, &srcLeft, &outPtr, &outLeft);
        if (ret == (size_t) -1)
            break;
    }

#else
    // Fallback ISO-8859-1 to UTF-8 conversion
    size_t srcSize = strlen(src),
           outSize = srcSize * 2 + 1;
    const uint8_t *srcPtr = (const uint8_t *) src;
    uint8_t *outBuf, *outPtr;
    if ((outBuf = outPtr = th_malloc(outSize + 1)) == NULL)
        return NULL;

    while (srcSize > 0 && outSize >= 2)
    {
        if (*srcPtr < 0x80)
        {
            *outPtr++ = *srcPtr;
            outSize--;
        }
        else
        if (*srcPtr < 0xBF)
        {
            *outPtr++ = 0xC2;
            *outPtr++ = *srcPtr;
            outSize -= 2;
        }
        else
        {
            *outPtr++ = 0xC3;
            *outPtr++ = (*srcPtr - 0x40);
            outSize -= 2;
        }
        srcPtr++;
        srcSize--;
    }

    *outPtr++ = 0;
#endif

    return (char *) outBuf;
}


int siItemFormatStrPutInt(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
    const int value, const int f_radix, int f_flags, int f_width, int f_prec,
    const BOOL f_unsig, char *(f_alt)(const char *buf, const size_t blen, const int vret, const int flags))
{
    char buf[64];
    int f_len = 0, vret;
    BOOL f_neg = FALSE;

    vret = th_vprintf_buf_int(buf, sizeof(buf), &f_len, value,
         f_radix, f_flags & TH_PF_UPCASE, f_unsig, &f_neg);

    if (vret == EOF)
        return 0;

    return th_vprintf_put_int_format(ctx, vputch, buf, f_flags, f_width, f_prec, f_len, vret, f_neg, f_unsig, f_alt);
}


int siItemFormatStrPrintDo(th_vprintf_ctx *ctx, th_vprintf_putch vputch, const char *fmt,
    const PSFOption *opt, const char *d_str, const int d_int)
{
    int ret = 0;

    while (*fmt)
    {
        if (*fmt != '%')
        {
            if ((ret = vputch(ctx, *fmt)) == EOF)
                goto out;
        }
        else
        {
            int f_width = -1, f_prec = -1, f_flags = 0;
            BOOL end = FALSE;

            fmt++;

            // Check for flags
            while (!end)
            {
                switch (*fmt)
                {
                    case '#':
                        f_flags |= TH_PF_ALT;
                        break;

                    case '+':
                        f_flags |= TH_PF_SIGN;
                        break;

                    case '0':
                        f_flags |= TH_PF_ZERO;
                        break;

                    case '-':
                        f_flags |= TH_PF_LEFT;
                        break;

                    case ' ':
                        f_flags |= TH_PF_SPACE;
                        break;

                    case '\'':
                        f_flags |= TH_PF_GROUP;
                        break;

                    default:
                        end = TRUE;
                        break;
                }
                if (!end) fmt++;
            }

            // Get field width
            if (*fmt == '*')
            {
                return -101;
            }
            else
            {
                f_width = 0;
                while (th_isdigit(*fmt))
                    f_width = f_width * 10 + (*fmt++ - '0');
            }

            // Check for field precision
            if (*fmt == '.')
            {
                fmt++;
                if (*fmt == '*')
                {
                    return -102;
                }
                else
                {
                    // If no digit after '.', precision is to be 0
                    f_prec = 0;
                    while (th_isdigit(*fmt))
                        f_prec = f_prec * 10 + (*fmt++ - '0');
                }
            }


            // Check for length modifiers (only some are supported currently)
            switch (*fmt)
            {
                case 0:
                    return -104;

                case 'o':
                    if (opt->type != OTYPE_INT) return -120;
                    if ((ret = siItemFormatStrPutInt(ctx, vputch, d_int, 8, f_flags, f_width, f_prec, TRUE, th_vprintf_altfmt_oct)) == EOF)
                        goto out;
                    break;

                case 'u':
                case 'i':
                case 'd':
                    if (opt->type != OTYPE_INT) return -120;
                    if ((ret = siItemFormatStrPutInt(ctx, vputch, d_int, 10, f_flags, f_width, f_prec, *fmt == 'u', NULL)) == EOF)
                        goto out;
                    break;

                case 'x':
                case 'X':
                    if (opt->type != OTYPE_INT) return -120;
                    if (*fmt == 'X')
                        f_flags |= TH_PF_UPCASE;
                    if ((ret = siItemFormatStrPutInt(ctx, vputch, d_int, 16, f_flags, f_width, f_prec, TRUE, th_vprintf_altfmt_hex)) == EOF)
                        goto out;
                    break;

                case 's':
                    if (opt->type != OTYPE_STR) return -121;
                    if ((ret = th_vprintf_put_str(ctx, vputch, d_str, f_flags, f_width, f_prec)) == EOF)
                        goto out;
                    break;

                //case '%':
                default:
                    if ((ret = vputch(ctx, *fmt)) == EOF)
                        goto out;
                    break;
            }
        }
        fmt++;
    }

out:
    return ret == EOF ? ret : ctx->ipos;
}


static int siItemFormatStrPutCH(th_vprintf_ctx *ctx, const char ch)
{
    if (ctx->pos + 1 >= ctx->size)
    {
        ctx->size += 64;
        if ((ctx->buf = th_realloc(ctx->buf, ctx->size)) == NULL)
            return EOF;
    }

    ctx->buf[ctx->pos] = ch;

    ctx->pos++;
    ctx->ipos++;
    return ch;
}


char * siItemFormatStrPrint(const char *fmt, const PSFOption *opt, const char *d_str, const int d_int)
{
    th_vprintf_ctx ctx;

    ctx.size = 128;
    ctx.buf = th_malloc(ctx.size);
    ctx.pos = 0;
    ctx.ipos = 0;

    if (ctx.buf == NULL)
        return NULL;

    if (siItemFormatStrPrintDo(&ctx, siItemFormatStrPutCH, fmt, opt, d_str, d_int) <= 0)
        goto err;

    if (siItemFormatStrPutCH(&ctx, 0) < 0)
        goto err;

    return ctx.buf;

err:
    th_free(ctx.buf);
    return NULL;
}


static int siItemFormatStrPutCHNone(th_vprintf_ctx *ctx, const char ch)
{
    ctx->pos++;
    ctx->ipos++;
    return ch;
}


BOOL siItemFormatStrCheck(const char *fmt, const PSFOption *opt)
{
    th_vprintf_ctx ctx;

    memset(&ctx, 0, sizeof(ctx));

    return siItemFormatStrPrintDo(&ctx, siItemFormatStrPutCHNone, fmt, opt, NULL, 0) >= 0;
}


//
// Parse a format string into a PSFStack structure
//
BOOL argParsePSFormatStr(PSFStack *stack, const char *fmt)
{
    PSFStackItem item;
    const char *start = NULL;
    int mode = 0;
    BOOL rval = TRUE;

    siClearStack(stack);

    while (mode != -1)
    switch (mode)
    {
        case 0:
            if (*fmt == '@')
            {
                start = fmt + 1;
                mode = 1;
            }
            else
            {
                start = fmt;
                mode = 2;
            }
            fmt++;
            break;

        case 1:
            if (*fmt != '@')
            {
                if (*fmt == 0)
                    mode = -1;
                fmt++;
                break;
            }

            if (fmt - start == 0)
            {
                // "@@" sequence, just print out @
                item.cmd = -2;
                item.str = NULL;
                item.chr = '@';
                if (!siStackAddItem(stack, &item))
                    return FALSE;
            }
            else
            {
                char *fopt = NULL, *pfield, *field = th_strndup_trim(start, fmt - start, TH_TRIM_BOTH);
                if ((pfield = strchr(field, ':')) != NULL)
                {
                    *pfield = 0;
                    fopt = th_strdup_trim(pfield + 1, TH_TRIM_BOTH);
                }

                int ret = argMatchPSFieldError(field);
                if (ret >= 0)
                {
                    item.cmd = ret;
                    item.flags = 0;
                    item.fmt = NULL;
                    item.str = NULL;

                    if (fopt != NULL)
                    {
                        if (siItemFormatStrCheck(fopt, &optPSOptions[item.cmd]))
                        {
                            item.flags |= OFMT_FORMAT;
                            item.fmt = th_strdup(fopt);
                        }
                        else
                        {
                            THERR("Invalid field format specifier '%s' in '%s'.\n", fopt, field);
                            rval = FALSE;
                        }
                    }

                    if (!siStackAddItem(stack, &item))
                        rval = FALSE;
                }
                else
                    rval = FALSE;

                th_free(fopt);
                th_free(field);
            }

            mode = 0;
            fmt++;
            break;

        case 2:
            if (*fmt == 0 || *fmt == '@')
            {
                item.cmd = -1;
                item.str = th_strndup(start, fmt - start);
                if (!siStackAddItem(stack, &item))
                    return FALSE;

                mode = (*fmt == 0) ? -1 : 0;
            }
            else
                fmt++;
            break;
    }

    return rval;
}


BOOL argHandleOpt(const int optN, char *optArg, char *currArg)
{
    switch (optN)
    {
    case 0:
        argShowHelp();
        exit(0);
        break;

    case 10:
        argShowLicense();
        exit(0);
        break;

    case 1:
        th_verbosity++;
        break;

    case 2:
        optParsable = TRUE;
        break;

    case 3:
        if (!argParsePSFields(&optFormat, optArg))
            return FALSE;
        break;

    case 4:
        optHexadecimal = TRUE;
        break;

    case 5:
        optNoNamePrefix = TRUE;
        break;

    case 6:
        optOneLineFieldSep = optArg;
        break;

    case 7:
        optFieldOutput = FALSE;
        if (!argParsePSFormatStr(&optFormat, optArg))
            return FALSE;
        break;

    case 8:
        setHVSCPath = th_strdup(optArg);
        break;

    case 9:
        setSLDBPath = th_strdup(optArg);
        break;

    case 11:
        optEscapeChars = optArg;
        break;

    case 12:
        optRecurseDirs = TRUE;
        break;

    default:
        THERR("Unknown option '%s'.\n", currArg);
        return FALSE;
    }

    return TRUE;
}


static char * siEscapeString(const char *str, const char *esc)
{
    if (str == NULL)
        return NULL;

    if (esc == NULL)
        return th_strdup(str);

    size_t len = 0, size = strlen(str) + 1;
    char *buf = th_malloc(size);
    if (buf == NULL)
        return NULL;

    while (*str)
    {
        if (strchr(esc, *str) != NULL || *str == '\\')
        {
            if (!th_strbuf_putch(&buf, &size, &len, '\\'))
                goto err;
        }
        if (!th_strbuf_putch(&buf, &size, &len, *str))
            goto err;

        str++;
    }

    if (!th_strbuf_putch(&buf, &size, &len, 0))
        goto err;

    return buf;

err:
    th_free(buf);
    return NULL;
}


static void siPrintStrEscapes(FILE *outFile, const char *str)
{
    while (*str)
    {
        if (*str == '\\')
        switch (*(++str))
        {
            case 'n': fputc('\n', outFile); break;
            case 'r': fputc('\r', outFile); break;
            case 't': fputc('\r', outFile); break;
            case '\\': fputc('\\', outFile); break;
            default: fputc(*str, outFile); break;
        }
        else
            fputc(*str, outFile);

        str++;
    }
}


static void siPrintFieldPrefix(FILE *outFile, const PSFOption *opt)
{
    const char *name = (optParsable || opt->lname == NULL) ? opt->name : opt->lname;
    if (!optNoNamePrefix && optFieldOutput)
        fprintf(outFile, optParsable ? "%s=" : "%-20s : ", name);
}


static void siPrintFieldSeparator(FILE *outFile)
{
    if (optFieldOutput)
        fputs(optOneLineFieldSep != NULL ? optOneLineFieldSep : "\n", outFile);
}


static void siPrintPSIDInfoLine(FILE *outFile, BOOL *shown, const PSFStackItem *item, const char *d_str, const int d_int, const BOOL useConv)
{
    const PSFOption *opt = &optPSOptions[item->cmd];
    char *fmt, *str, *tmp;

    switch (opt->type)
    {
        case OTYPE_INT:
            if (item->flags & OFMT_FORMAT)
                fmt = item->fmt;
            else
                fmt = optHexadecimal ? "$%04x" : "%d";
            break;

        case OTYPE_STR:
            if (item->flags & OFMT_FORMAT)
                fmt = item->fmt;
            else
                fmt = "%s";
            break;

        default:
            return;
    }

    if (setUseChConv && d_str != NULL && useConv)
    {
        char *tmp2 = siConvertCharset(d_str);
        tmp = siEscapeString(tmp2, optEscapeChars);
        th_free(tmp2);
    }
    else
        tmp = siEscapeString(d_str, optEscapeChars);

    siPrintFieldPrefix(outFile, opt);

    if ((str = siItemFormatStrPrint(fmt, opt, tmp, d_int)) != NULL)
        fputs(str, outFile);

    siPrintFieldSeparator(outFile);
    th_free(str);
    th_free(tmp);

    *shown = TRUE;
}


#define PRS(d_str, d_conv) siPrintPSIDInfoLine(outFile, shown, item, d_str, -1, d_conv)
#define PRI(d_int) siPrintPSIDInfoLine(outFile, shown, item, NULL, d_int, FALSE)


static void siPrintPSIDInformationField(FILE *outFile, const char *filename, const PSIDHeader *psid, BOOL *shown, const PSFStackItem *item)
{
    const PSFOption *opt = &optPSOptions[item->cmd];
    char tmp[128];

    switch (item->cmd)
    {
        case  0: PRS(filename, FALSE); break;
        case  1: PRS(psid->magic, FALSE); break;
        case  2:
            snprintf(tmp, sizeof(tmp), "%d.%d", (psid->version & 0xff), (psid->version >> 8));
            PRS(tmp, FALSE);
            break;
        case  3:
            PRS((psid->flags & PSF_PLAYER_TYPE) ? "Compute! SIDPlayer MUS" : "Normal built-in", FALSE);
            break;
        case  4:
            if (psid->version >= 2)
                PRS((psid->flags & PSF_PLAYSID_TUNE) ? (psid->isRSID ? "C64 BASIC" : "PlaySID") : "C64 compatible", FALSE);
            break;
        case  5:
            if (psid->version >= 2)
                PRS(si_get_sid_clock_str((psid->flags >> 2) & PSF_CLOCK_MASK), FALSE);
            break;
        case  6:
            if (psid->version >= 2)
                PRS(si_get_sid_model_str((psid->flags >> 4) & PSF_MODEL_MASK), FALSE);
            break;

        case  7: PRI(psid->dataOffset); break;
        case  8: PRI(psid->dataSize); break;
        case  9: PRI(psid->loadAddress); break;
        case 10: PRI(psid->initAddress); break;
        case 11: PRI(psid->playAddress); break;
        case 12: PRI(psid->nSongs); break;
        case 13: PRI(psid->startSong); break;

        case 14:
            if (psid->version >= 3)
            {
                int flags = (psid->flags >> 6) & PSF_MODEL_MASK;
                if (flags == PSF_MODEL_UNKNOWN)
                    flags = (psid->flags >> 4) & PSF_MODEL_MASK;

                PRS(si_get_sid_model_str(flags), FALSE);
            }
            break;
        case 15:
            if (psid->version >= 4)
            {
                int flags = (psid->flags >> 8) & PSF_MODEL_MASK;
                if (flags == PSF_MODEL_UNKNOWN)
                    flags = (psid->flags >> 4) & PSF_MODEL_MASK;

                PRS(si_get_sid_model_str(flags), FALSE);
            }
            break;
        case 16:
            if (psid->version >= 3)
                PRI(0xD000 | (psid->sid2Addr << 4));
            break;
        case 17:
            if (psid->version >= 4)
                PRI(0xD000 | (psid->sid3Addr << 4));
            break;

        case 18: PRS(psid->sidName, TRUE); break;
        case 19: PRS(psid->sidAuthor, TRUE); break;
        case 20: PRS(psid->sidCopyright, TRUE); break;

        case 21:
            {
                size_t i, k;
                for (i = k = 0; i < TH_MD5HASH_LENGTH && k < sizeof(tmp) - 1; i++, k += 2)
                    sprintf(&tmp[k], "%02x", psid->hash[i]);

                PRS(tmp, FALSE);
            }
            break;

        case 22:
            if (psid->lengths != NULL && psid->lengths->nlengths > 0)
            {
                int i;
                siPrintFieldPrefix(outFile, opt);
                for (i = 0; i < psid->lengths->nlengths; i++)
                {
                    int len = psid->lengths->lengths[i];
                    fprintf(outFile, "%d:%d%s", len / 60, len % 60,
                        (i < psid->lengths->nlengths - 1) ? " " : "");
                }
                siPrintFieldSeparator(outFile);
            }
            break;
    }
}


void siError(th_ioctx *fh, const int err, const char *msg)
{
    (void) fh;
    (void) err;
    fprintf(stderr, "%s", msg);
}


BOOL siHandleSIDFile(const char *filename)
{
    PSIDHeader *psid = NULL;
    th_ioctx *inFile = NULL;
    FILE *outFile;
    BOOL shown = FALSE;
    int res;

    outFile = stdout;

    if ((res = th_io_fopen(&inFile, &th_stdio_io_ops, filename, "rb")) != THERR_OK)
    {
        THERR("Could not open file '%s': %s\n",
            filename, th_error_str(res));
        goto error;
    }

    th_io_set_handlers(inFile, siError, NULL);

    // Read PSID data
    if (!si_read_sid_file(inFile, &psid, setSLDBNewFormat))
        goto error;

    // Get songlength information, if any
    if (sidSLDB != NULL)
        psid->lengths = si_sldb_get_by_hash(sidSLDB, psid->hash);

    // Output
    for (int index = 0; index < optFormat.nitems; index++)
    {
        PSFStackItem *item = &optFormat.items[index];
        switch (item->cmd)
        {
            case -1:
                siPrintStrEscapes(outFile, item->str);
                break;

            case -2:
                fputc(item->chr, outFile);
                break;

            default:
                siPrintPSIDInformationField(outFile, filename, psid, &shown, item);
                break;
        }
    }

    if (optFieldOutput && shown)
    {
        fprintf(outFile, "\n");
    }

    // Shutdown
error:
    si_free_sid_file(psid);
    th_io_free(inFile);

    return TRUE;
}


BOOL argHandleFileDir(const char *path, const char *filename, const char *pattern)
{
    th_stat_data sdata;
    char *npath;
    BOOL ret = TRUE;

    if (filename != NULL)
        npath = th_strdup_printf("%s%c%s", path, TH_DIR_SEPARATOR, filename);
    else
        npath = th_strdup(path);

    if (!th_stat_path(npath, &sdata))
    {
        THERR("File or path '%s' does not exist.\n", npath);
        ret = FALSE;
        goto out;
    }

    optNFiles++;

    if (sdata.flags & TH_IS_DIR)
    {
        DIR *dirh;
        struct dirent *entry;

        // Check if recursion is disabled
        if (!optRecurseDirs && optNFiles > 1)
            goto out;

        if ((dirh = opendir(npath)) == NULL)
        {
            int err = th_get_error();
            THERR("Could not open directory '%s': %s\n",
                path, th_error_str(err));
            ret = FALSE;
            goto out;
        }

        while ((entry = readdir(dirh)) != NULL)
        if (entry->d_name[0] != '.')
        {
            if (!argHandleFileDir(npath, entry->d_name, pattern))
            {
                ret = FALSE;
                goto out;
            }
        }

        closedir(dirh);
    }
    else
    if (pattern == NULL || th_strmatch(filename, pattern))
    {
        siHandleSIDFile(npath);
    }

out:
    th_free(npath);
    return ret;
}


BOOL argHandleFile(char *path)
{
    char *pattern = NULL, *filename = NULL, *pt, *npath = th_strdup(path);
    BOOL ret;

    if (npath == NULL)
        return FALSE;

    // Check if we have path separators
    if ((pt = strrchr(npath, '/')) != NULL ||
        (pt = strrchr(npath, '\\')) != NULL)
    {
        *pt++ = 0;
    }
    else
    {
        th_free(npath);
        npath = th_strdup(".");
        pt = strcmp(path, npath) != 0 ? path : NULL;
    }

    // Check if we have glob pattern chars
    if (pt != NULL && *pt != 0)
    {
        if (strchr(pt, '*') || strchr(pt, '?'))
            pattern = th_strdup(pt);
        else
            filename = th_strdup(pt);
    }

    ret = argHandleFileDir(npath, filename, pattern);

    th_free(pattern);
    th_free(npath);
    th_free(filename);
    return ret;
}


char *siCheckHVSCFilePath(const char *filebase, const char *fext)
{
    th_stat_data sdata;
    char *npath = th_strdup_printf("%s%c%s%s", setHVSCPath, TH_DIR_SEPARATOR, filebase, fext);

    if (npath != NULL &&
        th_stat_path(npath, &sdata) &&
        (sdata.flags & TH_IS_READABLE) &&
        (sdata.flags & TH_IS_DIR) == 0)
        return npath;

    th_free(npath);
    return NULL;
}


int main(int argc, char *argv[])
{
    char *setLang = th_strdup(getenv("LANG"));

    // Initialize
    th_init("SIDInfo", "PSID/RSID information displayer", "0.7.9",
        "By Matti 'ccr' Hamalainen (C) Copyright 2014-2018 TNSP",
        "This program is distributed under a 3-clause BSD -style license.");

    th_verbosity = 0;

    memset(&optFormat, 0, sizeof(optFormat));

    // Get environment language
    if (setLang != NULL)
    {
        // Get the character encoding part (e.g. "UTF-8" etc.) and
        // strip out and lowercase everything (e.g. "utf8")
        size_t i;
        char *ptr = strchr(setLang, '.');
        ptr = (ptr == NULL) ? setLang : ptr + 1;

        for (i = 0; *ptr; ptr++)
        {
            if (*ptr != '-')
                setLang[i++] = th_tolower(*ptr);
        }
        setLang[i] = 0;
    }

#ifdef HAVE_ICONV
    // Initialize iconv, check if we have language/charset
    setChConv = iconv_open(setLang != NULL ? setLang : SET_DEF_CHARSET, "iso88591");
    setUseChConv = setChConv != (iconv_t) -1;
#else
    setUseChConv = setLang != NULL && strcmp(setLang, SET_DEF_CHARSET) == 0;
#endif

    th_free(setLang);

    // Parse command line arguments
    if (!th_args_process(argc, argv, optList, optListN,
        argHandleOpt, NULL, OPTH_ONLY_OPTS))
        return -1;

    if (optOneLineFieldSep != NULL)
    {
        // For one-line format, disable parsing and prefixes
        optParsable = FALSE;
        optNoNamePrefix = TRUE;

        // If no escape chars have been set, use the field separator(s)
        if (optEscapeChars == NULL)
            optEscapeChars = optOneLineFieldSep;
    }

    if (optFieldOutput && optFormat.nitems == 0)
    {
        // For standard field output, push standard items to format stack
        PSFStackItem item;

        memset(&item, 0, sizeof(item));
        siClearStack(&optFormat);

        for (int i = 0; i < noptPSOptions; i++)
        {
            item.cmd = i;
            siStackAddItem(&optFormat, &item);
        }
    }

    // Check if HVSC path is set
    if (setHVSCPath != NULL)
    {
        // If SLDB path is not set, autocheck for .md5 and .txt
        if (setSLDBPath == NULL)
            setSLDBPath = siCheckHVSCFilePath(SET_SLDB_FILEBASE, ".md5");
        if (setSLDBPath == NULL)
            setSLDBPath = siCheckHVSCFilePath(SET_SLDB_FILEBASE, ".txt");
    }

    if (setSLDBPath != NULL)
    {
        // Initialize SLDB
        int ret = THERR_OK;
        th_ioctx *inFile = NULL;

        setSLDBNewFormat = th_strrcasecmp(setSLDBPath, ".md5") != NULL;

        if ((ret = th_io_fopen(&inFile, &th_stdio_io_ops, setSLDBPath, "r")) != THERR_OK)
        {
            THERR("Could not open SLDB '%s': %s\n",
                setSLDBPath, th_error_str(ret));
            goto err;
        }

        THMSG(1, "Reading SLDB.\n");
        if ((sidSLDB = si_sldb_new()) == NULL)
        {
            THERR("Could not allocate SLDB structure!\n");
            goto err;
        }

        if ((ret = si_sldb_read(inFile, sidSLDB)) != THERR_OK)
        {
            THERR("Error parsing SLDB: %d, %s\n",
                ret, th_error_str(ret));
            goto err;
        }
        th_io_close(inFile);

        if ((ret = si_sldb_build_index(sidSLDB)) != THERR_OK)
        {
            THERR("Error building SLDB index: %d, %s.\n",
                ret, th_error_str(ret));
            goto err;
        }

err:
        th_io_close(inFile);
    }

    // Process files
    if (!th_args_process(argc, argv, optList, optListN,
        NULL, argHandleFile, OPTH_ONLY_OTHER))
        goto out;

    if (optNFiles == 0)
    {
        THERR("No filename(s) specified. Try --help.\n");
    }

out:

#ifdef HAVE_ICONV
    if (setUseChConv)
        iconv_close(setChConv);
#endif

    th_free(setHVSCPath);
    th_free(setSLDBPath);
    si_sldb_free(sidSLDB);
    return 0;
}
