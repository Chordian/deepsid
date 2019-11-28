/*
 * Miscellaneous string-handling related utility-functions
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
/// @file
/// @brief String utility functions
#ifndef TH_STRING_H
#define TH_STRING_H

#include "th_util.h"
#include <stdlib.h>
#include <ctype.h>
#include <stdarg.h>

#ifdef __cplusplus
extern "C" {
#endif

/** @def String utility wrapper macros
 */
#define th_isalnum(c)   isalnum((int)(unsigned char) c)
#define th_isalpha(c)   isalpha((int)(unsigned char) c)
#define th_isascii(c)   isascii((int)(unsigned char) c)
#define th_isblank(c)   isblank((int)(unsigned char) c)
#define th_iscntrl(c)   iscntrl((int)(unsigned char) c)
#define th_isdigit(c)   isdigit((int)(unsigned char) c)
#define th_isgraph(c)   isgraph((int)(unsigned char) c)
#define th_islower(c)   islower((int)(unsigned char) c)
#define th_isprint(c)   isprint((int)(unsigned char) c)
#define th_ispunct(c)   ispunct((int)(unsigned char) c)
#define th_isspace(c)   isspace((int)(unsigned char) c)
#define th_isupper(c)   isupper((int)(unsigned char) c)
#define th_isxdigit(c)  isxdigit((int)(unsigned char) c)
#define th_iscrlf(c)    ((c=='\r')||(c=='\n'))

#define th_isspecial(q) (((q >= 0x5b) && (q <= 0x60)) || ((q >= 0x7b) && (q <= 0x7d)))

#define th_tolower(c)   tolower((int)(unsigned char) c)
#define th_toupper(c)   toupper((int)(unsigned char) c)


/** @def String trimming option flags for th_strdup_trim()
 */
enum
{
    TH_TRIM_START    = 1,
    TH_TRIM_END      = 2,
    TH_TRIM_BOTH     = 3
};


/** @def Internal *printf() implementation flags
 */
enum
{
    TH_PF_NONE       = 0x0000,
    TH_PF_ALT        = 0x0001,
    TH_PF_SIGN       = 0x0002,
    TH_PF_SPACE      = 0x0004,
    TH_PF_GROUP      = 0x0008,

    TH_PF_ZERO       = 0x0100,
    TH_PF_LEFT       = 0x0200,

    TH_PF_LONG       = 0x1000,
    TH_PF_LONGLONG   = 0x2000,
    TH_PF_POINTER    = 0x4000,
    TH_PF_UPCASE     = 0x8000,
};


/** @def Internal *printf() context structure
 */
typedef struct
{
    char *buf;           ///< Resulting string buffer pointer (might not be used if printing to file or such)
    size_t size, pos;    ///< Size of result string buffer, and current position in it
    int ipos;            ///< Signed position
    void *data;          ///< Pointer to other data (for example a FILE pointer)
} th_vprintf_ctx;


/** @def putch() helper function typedef for internal printf() implementation
 */
typedef int (*th_vprintf_putch)(th_vprintf_ctx *ctx, const char ch);


/* Normal NUL-terminated string functions
 */
char    *th_strdup(const char *src);
char    *th_strndup(const char *src, const size_t n);
char    *th_strdup_trim(const char *, const int flags);
char    *th_strndup_trim(const char *, const size_t n, const int flags);

int     th_strcasecmp(const char *haystack, const char *needle);
int     th_strncasecmp(const char *haystack, const char *needle, size_t n);
char    *th_strrcasecmp(char *haystack, const char *needle);
void    th_strip_ctrlchars(char *str);

int     th_vsnprintf(char *buf, size_t size, const char *fmt, va_list ap);
int     th_snprintf(char *buf, size_t size, const char *fmt, ...);
int     th_vfprintf(FILE *fh, const char *fmt, va_list ap);
int     th_fprintf(FILE *fh, const char *fmt, ...);


char    *th_strdup_vprintf(const char *fmt, va_list ap);
char    *th_strdup_printf(const char *fmt, ...);

void    th_pstr_vprintf(char **buf, const char *fmt, va_list ap);
void    th_pstr_printf(char **buf, const char *fmt, ...);

int     th_pstr_cpy(char **pdst, const char *src);
int     th_pstr_cat(char **pdst, const char *src);


/* Internal printf() implementation. NOTICE! This API may be unstable.
 */
int     th_vprintf_do(th_vprintf_ctx *ctx, th_vprintf_putch vputch, const char *fmt, va_list ap);
int     th_vprintf_put_str(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
        const char *str, int f_flags, const int f_width, const int f_prec);
int 	th_vprintf_put_int(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
        va_list ap, const int f_radix, int f_flags, int f_width, int f_prec,
        const BOOL f_unsig, char *(f_alt)(const char *buf, const size_t blen, const int vret, const int flags));
int	 th_vprintf_put_int_format(th_vprintf_ctx *ctx, th_vprintf_putch vputch,
        char *buf, int f_flags, int f_width, int f_prec, int f_len, int vret,
        BOOL f_neg, BOOL f_unsig, char *(f_alt)(const char *buf, const size_t blen, const int vret, const int flags));

char *  th_vprintf_altfmt_oct(const char *buf, const size_t len, const int vret, const int flags);
char *  th_vprintf_altfmt_hex(const char *buf, const size_t len, const int vret, const int flags);


#define TH_PFUNC_NAME th_vprintf_buf_int
#define TH_PFUNC_TYPE_S int
#define TH_PFUNC_TYPE_U unsigned int
#define TH_PFUNC_HEADER 1
#include "th_printf1.c"


#define TH_PFUNC_NAME th_vprintf_buf_int64
#define TH_PFUNC_TYPE_S int64_t
#define TH_PFUNC_TYPE_U uint64_t
#define TH_PFUNC_HEADER 1
#include "th_printf1.c"


/* Parsing, matching
 */
const char    *th_findnext(const char *, size_t *);
const char    *th_findsep(const char *, size_t *, char);
const char    *th_findseporspace(const char *, size_t *, char);

BOOL    th_strmatch(const char *haystack, const char *pattern);
BOOL    th_strcasematch(const char *haystack, const char *pattern);

int     th_get_hex_triplet(const char *str);
BOOL    th_get_boolean(const char *str, BOOL *value);
BOOL    th_get_int(const char *str, unsigned int *value, BOOL *neg);

void    th_print_wrap(FILE *fh, const char *str, int spad, int rpad, int width);


#ifdef __cplusplus
}
#endif
#endif // TH_STRING_H
