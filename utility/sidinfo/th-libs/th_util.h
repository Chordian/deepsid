/*
 * Generic utility-functions, macros and defaults
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#ifndef TH_UTIL_H
#define TH_UTIL_H

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#if defined(__WIN64) || defined(_WIN64) || defined(__WIN32) || defined(_WIN32)
#  define TH_PLAT_WINDOWS 1
#else
#  define TH_PLAT_UNIX 1
#endif

#include "th_types.h"
#include <stdio.h>
#include <stdarg.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#ifndef HAVE_NO_ASSERT
#  include <assert.h>
#endif

#ifdef HAVE_STRING_H
#  include <string.h>
#else
#  ifdef HAVE_STRINGS_H
#    include <strings.h>
#  endif
#endif

#ifdef HAVE_MEMORY_H
#  include <memory.h>
#endif


#ifdef __cplusplus
extern "C" {
#endif


// Replacement for assert()
#ifdef HAVE_NO_ASSERT
#  ifdef NDEBUG
#    define assert(NEXPR) // stub
#  else
#    define assert(NEXPR) do { if (!(NEXPR)) { fprintf(stderr, "[%s:%d] assert(" # NEXPR ") failed!\n", __FILE__, __LINE__); abort(); } } while (0)
#  endif
#endif


/* Error codes
 */
enum
{
    // General error codes
    THERR_OK = 0,
    THERR_PROGRESS,     // Status OK, but operation in progress

    THERR_INTERNAL,
	
    THERR_FOPEN,
    THERR_FREAD,
    THERR_FWRITE,
    THERR_FSEEK,
    THERR_NOT_FOUND,    // Resource/data not found

    THERR_INVALID_DATA, // Some data was invalid
    THERR_MALLOC,       // Memory allocation failure
    THERR_ALREADY_INIT, // Resource has already been initialized
    THERR_INIT_FAIL,    // General initialization failure
    THERR_INVALID_ARGS,

    THERR_NULLPTR,      // NULL pointer specified in critical argument
    THERR_NOT_SUPPORTED,// Operation not supported
    THERR_OUT_OF_DATA,
    THERR_EXTRA_DATA,
    THERR_BOUNDS,

    THERR_TIMED_OUT,
    THERR_BUSY,
    THERR_IO_ERROR,

    // Network errors
    THERR_AUTH_FAILED,
};

#define TH_SYSTEM_ERRORS 100000


/* Log levels
 */
enum
{
    THLOG_NONE    = 0,
    THLOG_ERROR,
    THLOG_WARNING,
    THLOG_INFO,
    THLOG_DEBUG,
};


/* Global variables
 */
extern int  th_verbosity;
extern char *th_prog_name,
            *th_prog_desc,
            *th_prog_version,
            *th_prog_author,
            *th_prog_license;

/* Functions
 */
void    th_init(char *name, char *desc, char *version,
               char *author, char *license);
void    th_print_banner(FILE *outFile, const char *binName, const char *usage);

int     th_term_width();
int     th_term_height();

int     th_get_error();
int     th_errno_to_error(int error);
const char *th_error_str(int error);

void    THERR(const char *fmt, ...);
void    THMSG(int level, const char *fmt, ...);
void    THPRINT(int level, const char *fmt, ...);

void    THERR_V(const char *fmt, va_list ap);
void    THMSG_V(int level, const char *fmt, va_list ap);
void    THPRINT_V(int level, const char *fmt, va_list ap);

void    *th_malloc(size_t len);
void    *th_malloc0(size_t len);
void    *th_calloc(size_t n, size_t len);
void    *th_realloc(void *ptr, size_t len);
void    th_free(void *ptr);
void    th_free_r_real(void **ptr);

#define th_free_r(ptr) th_free_r_real((void **) ptr)


#ifdef __cplusplus
}
#endif
#endif // TH_UTIL_H
