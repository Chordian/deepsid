/*
 * Miscellaneous string-handling related utility-functions
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#include "th_util.h"
#include "th_string.h"


/* Implementation of strdup() with a NULL check
 */
char *th_strdup(const char *src)
{
    char *res;
    if (src == NULL)
        return NULL;

    if ((res = th_malloc(strlen(src) + 1)) == NULL)
        return NULL;

    strcpy(res, src);
    return res;
}


/* Implementation of strndup() with NULL check
 */
char *th_strndup(const char *src, const size_t n)
{
    char *res;
    size_t len;

    if (src == NULL)
        return NULL;

    len = strlen(src);
    if (len > n)
        len = n;

    if ((res = th_malloc(len + 1)) == NULL)
        return NULL;

    memcpy(res, src, len);
    res[len] = 0;

    return res;
}


/* Like strdup, but trims whitespace from the string according to specified flags.
 * See TH_TRIM_* in th_string.h. If the resulting string would be empty (length 0),
 * NULL is returned.
 */
static char * th_strdup_trim_do(const char *src, size_t len, const int flags)
{
    char *res;
    size_t start, end;

    if (len == 0)
        return NULL;

    // Trim start: find first non-whitespace character
    if (flags & TH_TRIM_START)
        for (start = 0; start < len && th_isspace(src[start]); start++);
    else
        start = 0;

    // Trim end: find last non-whitespace character
    if (flags & TH_TRIM_END)
        for (end = len - 1; end > start && th_isspace(src[end]); end--);
    else
        end = len;

    // Allocate memory for result
    if (src[end] == 0 || th_isspace(src[end]))
        return NULL;

    len = end - start + 1;
    if ((res = th_malloc(len + 1)) == NULL)
        return NULL;

    memcpy(res, src + start, len);
    res[len] = 0;
    return res;
}


char *th_strdup_trim(const char *src, const int flags)
{
    if (src == NULL)
        return NULL;

    return th_strdup_trim_do(src, strlen(src), flags);
}


char *th_strndup_trim(const char *src, const size_t n, const int flags)
{
    size_t len;
    if (src == NULL || n == 0)
        return NULL;

    for (len = 0; len < n && src[len]; len++);

    return th_strdup_trim_do(src, len, flags);
}


//
// Simple implementations of printf() type functions
//
static int th_vprintf_put_pstr(th_vprintf_ctx *ctx, th_vprintf_putch vputch, const char *str)
{
    while (*str)
    {
        int ret;
        if ((ret = vputch(ctx, *str++)) == EOF)
            return ret;
    }
    return 0;
}


static int th_vprintf_put_repch(th_vprintf_ctx *ctx, th_vprintf_putch vputch, int count, const char ch)
{
    while (count-- > 0)
    {
        int ret;
        if ((ret = vputch(ctx, ch)) == EOF)
            return ret;
    }
    return 0;
}


static int th_printf_pad_pre(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
    int f_width, const int f_flags)
{
    if (f_width > 0 && (f_flags & TH_PF_LEFT) == 0)
        return th_vprintf_put_repch(ctx, vputch, f_width, ' ');
    else
        return 0;
}


static int th_printf_pad_post(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
    int f_width, const int f_flags)
{
    if (f_width > 0 && (f_flags & TH_PF_LEFT))
        return th_vprintf_put_repch(ctx, vputch, f_width, ' ');
    else
        return 0;
}


#define TH_PFUNC_NAME th_vprintf_buf_int
#define TH_PFUNC_TYPE_S int
#define TH_PFUNC_TYPE_U unsigned int
#include "th_printf1.c"


#define TH_PFUNC_NAME th_vprintf_buf_int64
#define TH_PFUNC_TYPE_S int64_t
#define TH_PFUNC_TYPE_U uint64_t
#include "th_printf1.c"


#ifdef TH_PRINTF_DEBUG
static void pflag(char *buf, const char *str, const int sep, const int flags, const int flg)
{
    strcat(buf, (flags & flg) ? str : "   ");
    if (sep)
        strcat(buf, "|");
}


static const char *get_flags(const int flags)
{
    static char buf[256];

    buf[0] = 0;

    pflag(buf, "ALT", 1, flags, TH_PF_ALT);
    pflag(buf, "SGN", 1, flags, TH_PF_SIGN);
    pflag(buf, "SPC", 1, flags, TH_PF_SPACE);
    pflag(buf, "GRP", 1, flags, TH_PF_GROUP);
    pflag(buf, "ZER", 1, flags, TH_PF_ZERO);
    pflag(buf, "LFT", 0, flags, TH_PF_LEFT);

    return buf;
}

#define PP_PRINTF(...) fprintf(stdout, __VA_ARGS__)
#else
#define PP_PRINTF(...) /* stub */
#endif


int th_vprintf_put_int_format(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
    char *buf, int f_flags, int f_width, int f_prec, int f_len, int vret,
    BOOL f_neg, BOOL f_unsig, char *(f_alt)(const char *buf, const size_t blen, const int vret, const int flags))
{
    int ret = 0, nwidth, nprec;
    char f_sign, *f_altstr;

    // Special case for value of 0
    if (vret == 0)
    {
        if (f_flags & TH_PF_POINTER)
        {
            strcpy(buf, ")lin(");
            f_len = 5;
        }
        else
        if (f_prec != 0)
        {
            buf[f_len++] = '0';
            buf[f_len] = 0;
        }
    }

    // Get alternative format string, if needed and available
    f_altstr = (f_flags & TH_PF_ALT) && f_alt != NULL ? f_alt(buf, f_len, vret, f_flags) : NULL;

    // Are we using a sign prefix?
    f_sign = f_unsig ? 0 : ((f_flags & TH_PF_SIGN) ?
        (f_neg ? '-' : '+') :
        (f_neg ? '-' : ((f_flags & TH_PF_SPACE) ? ' ' : 0)));

    // Calculate necessary padding, etc
    //
    // << XXX TODO FIXME: The logic here is not very elegant, and it's incorrect
    // at least for some alternate format modifier cases.
    //

    int nlen =  (f_sign ? 1 : 0) + (f_altstr ? strlen(f_altstr) : 0);
    int qlen = (f_prec > f_len ? f_prec : f_len) + nlen;

    if (f_flags & TH_PF_LEFT)
        f_flags &= ~TH_PF_ZERO;

    if (f_flags & TH_PF_POINTER && vret == 0)
    {
        PP_PRINTF("^");
        qlen = f_len + nlen;
        nwidth = f_width > qlen ? f_width - qlen : 0;
        nprec = 0;
    }
    else
    if ((f_flags & TH_PF_ZERO) && f_prec < 0 && f_width > 0)
    {
        PP_PRINTF("#");
        nprec = f_width - qlen;
        nwidth = 0;
    }
    else
    {
        PP_PRINTF("$");
        nprec = (f_prec >= 0) ? f_prec - f_len : 0;
        nwidth = (f_width >= 0) ? f_width - qlen : 0;
    }

    PP_PRINTF(": vret=%3d, f_flags=[%s], f_unsig=%d, f_sign='%c', f_len=%3d, f_width=%3d, f_prec=%3d, nwidth=%3d, nprec=%3d, qlen=%3d\n",
        vret, get_flags(f_flags), f_unsig, f_sign ? f_sign : '?', f_len, f_width, f_prec, nwidth, nprec, qlen);

    // << XXX TODO FIXME

    // Prefix padding
    if ((ret = th_printf_pad_pre(ctx, vputch, nwidth, f_flags)) == EOF)
        return ret;

    // Sign prefix
    if (f_sign && (ret = vputch(ctx, f_sign)) == EOF)
        return ret;

    // Alternative format string
    if (f_altstr && (ret = th_vprintf_put_pstr(ctx, vputch, f_altstr)) == EOF)
        return ret;

    // Zero padding
    if (nprec > 0 && (ret = th_vprintf_put_repch(ctx, vputch, nprec, '0')) == EOF)
        return ret;

    // Output the value
    while (f_len-- > 0)
    {
        if ((ret = vputch(ctx, buf[f_len])) == EOF)
            return ret;
    }

    // Postfix padding?
    return th_printf_pad_post(ctx, vputch, nwidth, f_flags);
}


int th_vprintf_put_int(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
    va_list ap, const int f_radix, int f_flags, int f_width, int f_prec,
    const BOOL f_unsig, char *(f_alt)(const char *buf, const size_t blen, const int vret, const int flags))
{
    char buf[64];
    int f_len = 0, vret;
    BOOL f_neg = FALSE;

    if (f_flags & TH_PF_LONGLONG)
    {
        vret = th_vprintf_buf_int64(buf, sizeof(buf), &f_len, va_arg(ap, int64_t),
            f_radix, f_flags & TH_PF_UPCASE, f_unsig, &f_neg);
    }
    else
    {
       vret = th_vprintf_buf_int(buf, sizeof(buf), &f_len, va_arg(ap, unsigned int),
            f_radix, f_flags & TH_PF_UPCASE, f_unsig, &f_neg);
    }

    if (vret == EOF)
        return 0;

    return th_vprintf_put_int_format(ctx, vputch, buf, f_flags, f_width, f_prec, f_len, vret, f_neg, f_unsig, f_alt);
}


int th_vprintf_put_str(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
    const char *str, int f_flags, const int f_width, const int f_prec)
{
    int nwidth, f_len, ret = 0;

    // Check for null strings
    if (str == NULL)
        str = "(null)";

    f_len = strlen(str);
    if (f_prec >= 0 && f_len > f_prec)
        f_len = f_prec;

    nwidth = f_width - f_len;

    // Prefix padding?
    if ((ret = th_printf_pad_pre(ctx, vputch, nwidth, f_flags)) == EOF)
        return ret;

    while (*str && f_len--)
    {
        if ((ret = vputch(ctx, *str++)) == EOF)
            return ret;
    }

    // Postfix padding?
    return th_printf_pad_post(ctx, vputch, nwidth, f_flags);
}


#ifdef WIP_FLOAT_SUPPORT
int th_vprintf_put_float(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
    va_list ap, int f_flags, int f_width, int f_prec)
{
    double pval = va_arg(ap, double);   // This needs to be double for type promotion to occur
    int64_t val;

    // This is a hack, trying to avoid dereferencing a type-punned
    // pointer and all that stuff related to strict aliasing (although
    // double and int64_t should be same size and have same aliasing rules.)
    memcpy(&val, &pval, sizeof(int64_t));

    // We have sign, exponent and mantissa
    BOOL f_sign    = (val >> 63) & 0x01;
    int64_t d_exp  = (val >> 52) & 0x7ff;
    uint64_t d_man = val & 0x0fffffffffffff;

    return 0;
}
#endif


char * th_vprintf_altfmt_oct(const char *buf, const size_t len, const int vret, const int flags)
{
    (void) vret;
    (void) flags;
    PP_PRINTF("BUF='%s', '%c'\n", buf, buf[len - 1]);
    return (buf[len - 1] != '0') ? "0" : "";
}


char * th_vprintf_altfmt_hex(const char *buf, const size_t len, const int vret, const int flags)
{
    (void) buf;
    (void) vret;
    (void) len;
    if (vret != 0)
        return (flags & TH_PF_UPCASE) ? "0X" : "0x";
    else
        return "";
}


int th_vprintf_do(th_vprintf_ctx *ctx, th_vprintf_putch vputch, const char *fmt, va_list ap)
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
                fmt++;
                f_width = va_arg(ap, int);
                if (f_width < 0)
                {
                    f_flags |= TH_PF_LEFT;
                    f_width = -f_width;
                }
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
                    fmt++;
                    f_prec = va_arg(ap, int);
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
                case 'l':
                    if (*++fmt == 'l')
                    {
                        f_flags |= TH_PF_LONGLONG;
                        fmt++;
                    }
                    else
                        f_flags |= TH_PF_LONG;
                    break;

                case 'L':
                    fmt++;
                    f_flags |= TH_PF_LONGLONG;
                    break;

                case 'h':
                case 'j':
                case 'z':
                case 't':
                    return -202;
            }

            switch (*fmt)
            {
                case 0:
                    return -104;

                case 'c':
                    if ((ret = th_printf_pad_pre(ctx, vputch, f_width - 1, f_flags)) == EOF)
                        goto out;
                    if ((ret = vputch(ctx, va_arg(ap, int))) == EOF)
                        goto out;
                    if ((ret = th_printf_pad_post(ctx, vputch, f_width - 1, f_flags)) == EOF)
                        goto out;
                    break;

                case 'o':
                    if ((ret = th_vprintf_put_int(ctx, vputch, ap, 8, f_flags, f_width, f_prec, TRUE, th_vprintf_altfmt_oct)) == EOF)
                        goto out;
                    break;

                case 'u':
                case 'i':
                case 'd':
                    if ((ret = th_vprintf_put_int(ctx, vputch, ap, 10, f_flags, f_width, f_prec, *fmt == 'u', NULL)) == EOF)
                        goto out;
                    break;

                case 'x':
                case 'X':
                    if (*fmt == 'X')
                        f_flags |= TH_PF_UPCASE;
                    if ((ret = th_vprintf_put_int(ctx, vputch, ap, 16, f_flags, f_width, f_prec, TRUE, th_vprintf_altfmt_hex)) == EOF)
                        goto out;
                    break;

                case 'p':
                    if (f_flags & (TH_PF_LONG | TH_PF_LONGLONG))
                        return -120;

#if (TH_PTRSIZE == 32)
                    f_flags |= TH_PF_LONG;
#elif (TH_PTRSIZE == 64)
                    f_flags |= TH_PF_LONGLONG;
#endif
                    f_flags |= TH_PF_ALT | TH_PF_POINTER;
                    if ((ret = th_vprintf_put_int(ctx, vputch, ap, 16, f_flags, f_width, f_prec, TRUE, th_vprintf_altfmt_hex)) == EOF)
                        goto out;
                    break;

#ifdef WIP_FLOAT_SUPPORT
                case 'f':
                case 'F':
                    if ((ret = th_vprintf_put_float(ctx, vputch, ap,
                        f_flags, f_width, f_prec)) == EOF)
                        goto out;
                    break;
#endif

                case 's':
                    if ((ret = th_vprintf_put_str(ctx, vputch, va_arg(ap, char *),
                        f_flags, f_width, f_prec)) == EOF)
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


#ifdef TH_USE_INTERNAL_SPRINTF
static int th_pbuf_vputch(th_vprintf_ctx *ctx, const char ch)
{
    if (ctx->pos < ctx->size)
        ctx->buf[ctx->pos] = ch;

    ctx->pos++;
    ctx->ipos++;
    return ch;
}


static int th_stdio_vputch(th_vprintf_ctx *ctx, const char ch)
{
    ctx->pos++;
    ctx->ipos++;
    return fputc(ch, (FILE *) ctx->data);
}
#endif


int th_vsnprintf(char *buf, size_t size, const char *fmt, va_list ap)
{
#ifdef TH_USE_INTERNAL_SPRINTF
    int ret;
    th_vprintf_ctx ctx;
    ctx.buf = buf;
    ctx.size = size;
    ctx.pos = 0;
    ctx.ipos = 0;

    ret = th_vprintf_do(&ctx, th_pbuf_vputch, fmt, ap);

    if (ctx.pos < size)
        buf[ctx.pos] = 0;
    else
    if (size > 0)
        buf[size - 1] = 0;

    return ret;
#else
    return vsnprintf(buf, size, fmt, ap);
#endif
}


int th_snprintf(char *buf, size_t size, const char *fmt, ...)
{
    int n;
    va_list ap;
    va_start(ap, fmt);
#ifdef TH_USE_INTERNAL_SPRINTF
    n = th_vsnprintf(buf, size, fmt, ap);
#else
    n = vsnprintf(buf, size, fmt, ap);
#endif
    va_end(ap);
    return n;
}


int th_vfprintf(FILE *fh, const char *fmt, va_list ap)
{
#ifdef TH_USE_INTERNAL_SPRINTF
    th_vprintf_ctx ctx;
    ctx.data = (void *) fh;
    ctx.pos = 0;
    ctx.ipos = 0;

    return th_vprintf_do(&ctx, th_stdio_vputch, fmt, ap);
#else
    return vfprintf(fh, fmt, ap);
#endif
}


int th_fprintf(FILE *fh, const char *fmt, ...)
{
    int ret;
#ifdef TH_USE_INTERNAL_SPRINTF
    th_vprintf_ctx ctx;
#endif
    va_list ap;
    va_start(ap, fmt);
#ifdef TH_USE_INTERNAL_SPRINTF
    ctx.data = (void *) fh;
    ctx.pos = 0;
    ctx.ipos = 0;

    ret = th_vprintf_do(&ctx, th_stdio_vputch, fmt, ap);
#else
    ret = fprintf(fh, fmt, ap);
#endif
    va_end(ap);
    return ret;
}


/* Simulate a sprintf() that allocates memory
 */
char *th_strdup_vprintf(const char *fmt, va_list args)
{
    int size = 64;
    char *buf, *tmp;

    if (fmt == NULL)
        return NULL;

    if ((buf = th_malloc(size)) == NULL)
        return NULL;

    while (1)
    {
        int n;
        va_list ap;
        va_copy(ap, args);
        n = vsnprintf(buf, size, fmt, ap);
        va_end(ap);

        if (n > -1 && n < size)
            return buf;
        if (n > -1)
            size = n + 1;
        else
            size *= 2;

        if ((tmp = th_realloc(buf, size)) == NULL)
        {
            th_free(buf);
            return NULL;
        }
        else
            buf = tmp;
    }
}


char *th_strdup_printf(const char *fmt, ...)
{
    char *res;
    va_list ap;

    va_start(ap, fmt);
    res = th_strdup_vprintf(fmt, ap);
    va_end(ap);

    return res;
}


void th_pstr_vprintf(char **buf, const char *fmt, va_list ap)
{
    char *tmp = th_strdup_vprintf(fmt, ap);
    th_free(*buf);
    *buf = tmp;
}


void th_pstr_printf(char **buf, const char *fmt, ...)
{
    char *tmp;
    va_list ap;

    va_start(ap, fmt);
    tmp = th_strdup_vprintf(fmt, ap);
    va_end(ap);

    th_free(*buf);
    *buf = tmp;
}


/* Compare two strings ignoring case [strcasecmp, strncasecmp]
 */
int th_strcasecmp(const char *haystack, const char *needle)
{
    const char *s1 = haystack, *s2 = needle;
    assert(haystack != NULL);
    assert(needle != NULL);

    if (haystack == needle)
        return 0;

    while (*s1 && *s2)
    {
        int k = th_tolower(*s1) - th_tolower(*s2);
        if (k != 0)
            return k;
        s1++;
        s2++;
    }

    return 0;
}


int th_strncasecmp(const char *haystack, const char *needle, size_t n)
{
    const char *s1 = haystack, *s2 = needle;
    assert(haystack != NULL);
    assert(needle != NULL);

    if (haystack == needle)
        return 0;

    while (n > 0 && *s1 && *s2)
    {
        int k = th_tolower(*s1) - th_tolower(*s2);
        if (k != 0)
            return k;
        s1++;
        s2++;
        n--;
    }

    return 0;
}


/* Check if end of the given string str matches needle
 * case-insensitively, return pointer to start of the match,
 * if found, NULL otherwise.
 */
char *th_strrcasecmp(char *str, const char *needle)
{
    if (str == NULL || needle == NULL)
        return NULL;

    const size_t
        slen = strlen(str),
        nlen = strlen(needle);

    if (slen < nlen)
        return NULL;

    if (th_strcasecmp(str + slen - nlen, needle) == 0)
        return str + slen - nlen;
    else
        return NULL;
}


/* Remove all occurences of control characters, in-place.
 * Resulting string is always shorter or same length than original.
 */
void th_strip_ctrlchars(char *str)
{
    char *i, *j;
    assert(str != NULL);

    i = str;
    j = str;
    while (*i)
    {
        if (!th_iscntrl(*i))
            *(j++) = *i;
        i++;
    }

    *j = 0;
}


/* Copy a given string over in *pdst.
 */
int th_pstr_cpy(char **pdst, const char *src)
{
    assert(pdst != NULL);

    if (src == NULL)
        return -1;

    th_free(*pdst);
    if ((*pdst = th_malloc(strlen(src) + 1)) == NULL)
        return -2;

    strcpy(*pdst, src);
    return 0;
}


/* Concatenates a given string into string pointed by *pdst.
 */
int th_pstr_cat(char **pdst, const char *src)
{
    assert(pdst != NULL);

    if (src == NULL)
        return -1;

    if (*pdst != NULL)
    {
        *pdst = th_realloc(*pdst, strlen(*pdst) + strlen(src) + 1);
        if (*pdst == NULL)
            return -1;

        strcat(*pdst, src);
    }
    else
    {
        *pdst = th_malloc(strlen(src) + 1);
        if (*pdst == NULL)
            return -1;

        strcpy(*pdst, src);
    }

    return 0;
}


/* Find next non-whitespace character in string.
 * Updates iPos into the position of such character and
 * returns pointer to the string.
 */
const char *th_findnext(const char *str, size_t *pos)
{
    assert(str != NULL);

    // Terminating NULL-character is not whitespace!
    while (th_isspace(str[*pos]))
        (*pos)++;

    return &str[*pos];
}


/* Find next sep-character from string
 */
const char *th_findsep(const char *str, size_t *pos, char sep)
{
    assert(str != NULL);

    while (str[*pos] && str[*pos] != sep)
        (*pos)++;

    return &str[*pos];
}


/* Find next sep- or whitespace from string
 */
const char *th_findseporspace(const char *str, size_t *pos, char sep)
{
    assert(str != NULL);

    while (!th_isspace(str[*pos]) && str[*pos] != sep)
        (*pos)++;

    return &str[*pos];
}


/* Compare a string to a pattern. Case-SENSITIVE version.
 * The matching pattern can consist of any normal characters plus
 * wildcards ? and *. "?" matches any character and "*" matches
 * any number of characters.
 */
#define TH_STRMATCH_FUNC th_strmatch
#define TH_STRMATCH_COLLATE(px) (px)
#include "th_strmatch.c"


/* Compare a string to a pattern. Case-INSENSITIVE version.
 */
#define TH_STRMATCH_FUNC th_strcasematch
#define TH_STRMATCH_COLLATE(px) th_tolower(px)
#include "th_strmatch.c"


int th_get_hex_triplet(const char *str)
{
    const char *p = str;
    int len, val = 0;

    for (len = 0; *p && len < 6; p++, len++)
    {
        if (*p >= '0' && *p <= '9')
        {
            val *= 16;
            val += (*p - '0');
        }
        else
        if (*p >= 'A' && *p <= 'F')
        {
            val *= 16;
            val += (*p - 'A') + 10;
        }
        else
        if (*p >= 'a' && *p <= 'f')
        {
            val *= 16;
            val += (*p - 'a') + 10;
        }
        else
            return -1;
    }

    return (len == 6) ? val : -1;
}


BOOL th_get_boolean(const char *str, BOOL *value)
{
    if (!th_strcasecmp(str, "yes") ||
        !th_strcasecmp(str, "on") ||
        !th_strcasecmp(str, "true") ||
        !th_strcasecmp(str, "1"))
    {
        *value = TRUE;
        return TRUE;
    }
    else
    if (!th_strcasecmp(str, "no") ||
        !th_strcasecmp(str, "off") ||
        !th_strcasecmp(str, "false") ||
        !th_strcasecmp(str, "0"))
    {
        *value = FALSE;
        return TRUE;
    }
    else
        return FALSE;
}


BOOL th_get_int(const char *str, unsigned int *value, BOOL *neg)
{
    int ch;
    BOOL hex = FALSE;

    // Is the value negative?
    if (*str == '-')
    {
        if (neg == NULL)
            return FALSE;

        *neg = TRUE;
        str++;
    }
    else
    if (neg != NULL)
        *neg = FALSE;

    // Is it hexadecimal?
    if (*str == '$')
    {
        hex = TRUE;
        str++;
    }
    else
    if (str[0] == '0' && str[1] == 'x')
    {
        hex = TRUE;
        str += 2;
    }

    // Parse the value
    *value = 0;
    if (hex)
    {
        while ((ch = *str++))
        {
            if (ch >= '0' && ch <= '9')
            {
                *value <<= 4;
                *value |= ch - '0';
            }
            else
            if (ch >= 'A' && ch <= 'F')
            {
                *value <<= 4;
                *value |= ch - 'A' + 10;
            }
            else
            if (ch >= 'a' && ch <= 'f')
            {
                *value <<= 4;
                *value |= ch - 'a' + 10;
            }
            else
                return FALSE;
        }
    }
    else
    {
        while ((ch = *str++))
        {
            if (ch >= '0' && ch <= '9')
            {
                *value *= 10;
                *value += ch - '0';
            }
            else
                return FALSE;
        }
    }
    return TRUE;
}


static void th_pad(FILE *outFile, int count)
{
    while (count--)
        fputc(' ', outFile);
}


void th_print_wrap(FILE *fh, const char *str, int spad, int rpad, int width)
{
    size_t pos = 0;
    BOOL first = TRUE;

    while (str[pos])
    {
        // Pre-pad line
        int linelen = first ? spad : rpad;
        th_pad(fh, first ? 0 : rpad);
        first = FALSE;

        // Skip whitespace at line start
        while (th_isspace(str[pos]) || str[pos] == '\n') pos++;

        // Handle each word
        while (str[pos] && str[pos] != '\n')
        {
            size_t next;
            int wlen;

            // Find word length and next break
            for (wlen = 0, next = pos; str[next] && !th_isspace(str[next]) && str[next] != '\n'; next++, wlen++);

            // Check if we have too much of text?
            if (linelen + wlen >= width)
                break;

            // Print what we have
            for (;pos < next; pos++, linelen++)
                fputc(str[pos], fh);

            // Check if we are at end of input or hard linefeed
            if (str[next] == '\n' || str[next] == 0)
                break;
            else
            {
                fputc(str[pos], fh);
                pos++;
                linelen++;
            }
        }
        fprintf(fh, "\n");
    }
}
