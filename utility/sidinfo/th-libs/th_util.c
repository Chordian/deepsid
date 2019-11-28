/*
 * Generic utility-functions, macros and defaults
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2015 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#include "th_util.h"
#include <stdio.h>
#include <errno.h>


/* Default settings
 */
static BOOL    th_initialized = FALSE;
int            th_verbosity = 2;
char           *th_prog_name = NULL,
               *th_prog_desc = NULL,
               *th_prog_version = NULL,
               *th_prog_author = NULL,
               *th_prog_license = NULL;


/* Initialize th_util-library and global variables
 */
void th_init(char *name, char *desc, char *version,
    char *author, char *license)
{
    th_prog_name     = name;
    th_prog_desc     = desc;
    th_prog_version  = version;

#ifdef TH_PROG_AUTHOR
    th_prog_author   = author == NULL ? TH_PROG_AUTHOR : NULL;
#else
    th_prog_author   = author;
#endif

#ifdef TH_PROG_LICENSE
    th_prog_license  = license == NULL ? TH_PROG_LICENSE : NULL;
#else
    th_prog_license  = license;
#endif

    th_initialized = TRUE;
}


void th_print_banner(FILE *outFile, const char *name, const char *usage)
{
    fprintf(outFile, "%s", th_prog_name);
    if (th_prog_version != NULL)
        fprintf(outFile, " v%s", th_prog_version);
    if (th_prog_desc != NULL)
        fprintf(outFile, " (%s)", th_prog_desc);
    fprintf(outFile, "\n");

    if (th_prog_author != NULL)
        fprintf(outFile, "%s\n", th_prog_author);

    if (th_prog_license != NULL)
        fprintf(outFile, "%s\n", th_prog_license);

    fprintf(outFile, "Usage: %s %s\n", name, usage);
}


int th_term_width()
{
    char *var = getenv("COLUMNS");
    int res = (var != NULL) ? atoi(var) : 80;
    if (res < 5) res = 80;
    return res;
}


int th_term_height()
{
    char *var = getenv("LINES");
    int res = (var != NULL) ? atoi(var) : 25;
    if (res < 1) res = 1;
    return res;
}


/* Print formatted error, warning and information messages
 * TODO: Implement th_vfprintf() and friends?
 */
void THERR_V(const char *fmt, va_list ap)
{
    assert(th_initialized == TRUE);

    fprintf(stderr, "%s: ", th_prog_name);
    vfprintf(stderr, fmt, ap);
}


void THMSG_V(int level, const char *fmt, va_list ap)
{
    assert(th_initialized == TRUE);

    if (th_verbosity >= level)
    {
        fprintf(stderr, "%s: ", th_prog_name);
        vfprintf(stderr, fmt, ap);
    }
}


void THPRINT_V(int level, const char *fmt, va_list ap)
{
    assert(th_initialized == TRUE);

    if (th_verbosity >= level)
    {
        vfprintf(stderr, fmt, ap);
    }
}


void THERR(const char *fmt, ...)
{
    va_list ap;
    assert(th_initialized == TRUE);

    va_start(ap, fmt);
    THERR_V(fmt, ap);
    va_end(ap);
}


void THMSG(int level, const char *fmt, ...)
{
    va_list ap;
    assert(th_initialized == TRUE);

    va_start(ap, fmt);
    THMSG_V(level, fmt, ap);
    va_end(ap);
}


void THPRINT(int level, const char *fmt, ...)
{
    va_list ap;
    assert(th_initialized == TRUE);

    va_start(ap, fmt);
    THPRINT_V(level, fmt, ap);
    va_end(ap);
}


/* Error handling
 */
int th_get_error()
{
    return TH_SYSTEM_ERRORS + errno;
}


int th_errno_to_error(int error)
{
    return TH_SYSTEM_ERRORS + error;
}


const char *th_error_str(int error)
{
    if (error >= TH_SYSTEM_ERRORS)
        return strerror(error - TH_SYSTEM_ERRORS);

    switch (error)
    {
        case THERR_OK:               return "No error";
        case THERR_FOPEN:            return "File open error";
        case THERR_FREAD:            return "Read error";
        case THERR_FWRITE:           return "Write error";
        case THERR_FSEEK:            return "Seek error";
        case THERR_NOT_FOUND:        return "Resource not found";

        case THERR_INVALID_DATA:     return "Invalid data";
        case THERR_MALLOC:           return "Memory allocation failure";
        case THERR_ALREADY_INIT:     return "Already initialized";
        case THERR_INIT_FAIL:        return "Initialization failed";
        case THERR_INVALID_ARGS:     return "Invalid arguments";

        case THERR_NULLPTR:          return "NULL pointer";
        case THERR_NOT_SUPPORTED:    return "Operation not supported";
        case THERR_OUT_OF_DATA:      return "Out of data";
        case THERR_EXTRA_DATA:       return "Extra data";
        case THERR_BOUNDS:           return "Bounds check failed";

        case THERR_TIMED_OUT:        return "Operation timed out";

        case THERR_AUTH_FAILED:      return "Authentication failed";

        default:                     return "Unknown error";
    }
}


/* Memory handling routines
 */
void *th_malloc(size_t len)
{
    return malloc(len);
}


void *th_malloc0(size_t len)
{
    return calloc(1, len);
}


void *th_calloc(size_t n, size_t len)
{
    return calloc(n, len);
}


void *th_realloc(void *ptr, size_t len)
{
    return realloc(ptr, len);
}


void th_free(void *ptr)
{
    /* Check for NULL pointers for portability due to some libc
     * implementations not handling free(NULL) too well.
     */
    if (ptr != NULL) free(ptr);
}


void th_free_r_real(void **ptr)
{
    if (ptr != NULL)
    {
        th_free(*ptr);
        *ptr = NULL;
    }
}
