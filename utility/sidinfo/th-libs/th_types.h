/*
 * Type definations
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
/* If your code uses "config.h", you need to #include
 * it before including this header.
 */
#ifndef TH_TYPES_H
#define TH_TYPES_H 1

// Check for standard headers
#if defined(HAVE_STDINT_H)
#  include <stdint.h>
#  ifndef HAVE_INT_TYPES
#    define HAVE_INT_TYPES 1
#  endif
#elif defined(HAVE_SYS_TYPES_H)
#  include <sys/types.h>
#  ifndef HAVE_INT_TYPES
#    define HAVE_INT_TYPES 1
#  endif
#endif

#ifdef HAVE_INTTYPES_H
#  include <inttypes.h>
#endif


// Check for arch bitness
#if UINTPTR_MAX == 0xffffffff
#  define TH_ARCH 32
#elif UINTPTR_MAX == 0xffffffffffffffff
#  define TH_ARCH 64
#endif


#if !defined(TH_ARCH)
#  if defined(__LP64__) || defined(_LP64)
#    define TH_ARCH 64
#  else
#    define TH_ARCH 32
#  endif
#endif

#if !defined(TH_ARCH) && (defined(__WIN64) || defined(_WIN64))
#  define TH_ARCH 64
#endif

#if !defined(TH_ARCH) && (defined(__WIN32) || defined(_WIN32))
#  define TH_ARCH 32
#endif


// Do we have a valid arch?
// If so, set some printf specifiers and other useful things
#if TH_ARCH == 32
#  define TH_ARCH_32BIT    1
#  ifndef HAVE_INTTYPES_H
//   If we don't have ISO C99 inttypes.h, define PRI* macros
#    define PRIu32        "u"
#    define PRId32        "d"
#    define PRIx32        "x"
#    define PRIi32        "i"
#    define PRIo32        "o"
#    define PRIu64        "llu"
#    define PRId64        "lld"
#    define PRIx64        "llx"
#    define PRIi64        "lli"
#    define PRIo64        "llo"
typedef long long int		intmax_t;
typedef unsigned long long int	uintmax_t;
#  endif
#  define PRIu_SIZE_T     "u"
#  define PRId_SSIZE_T    "d"
#  define PRIx_SIZE_T     "x"
#  ifndef TH_PTRSIZE
#    define TH_PTRSIZE 32
#  endif
#  ifndef INTPTR_MIN
#    define INTPTR_MIN    (-0x7fffffffL - 1)
#    define INTPTR_MAX    ( 0x7fffffffL)
#    define UINTPTR_MAX   ( 0xffffffffUL)
#  endif
#elif TH_ARCH == 64
#  define TH_ARCH_64BIT    1
#  ifndef HAVE_INTTYPES_H
#    define PRIu32        "u"
#    define PRId32        "d"
#    define PRIx32        "x"
#    define PRIi32        "i"
#    define PRIo32        "o"
#    define PRIu64        "lu"
#    define PRId64        "ld"
#    define PRIx64        "lx"
#    define PRIi64        "li"
#    define PRIo64        "lo"
#  endif
#  define PRIu_SIZE_T     "lu"
#  define PRId_SSIZE_T    "ld"
#  define PRIx_SIZE_T     "lx"
#  ifndef TH_PTRSIZE
#    define TH_PTRSIZE 64
#  endif
#  ifndef INTPTR_MIN
#    define INTPTR_MIN    (-0x7fffffffffffffffL - 1)
#    define INTPTR_MAX    ( 0x7fffffffffffffffL)
#    define UINTPTR_MAX   ( 0xffffffffffffffffUL)
#  endif
#else
#  error Could not determine architecture (32/64bit), please define TH_ARCH=32 or 64
#endif


// Shorthand types
typedef unsigned long int ulint_t;
typedef signed long int lint_t;
#ifndef HAVE_UINT_T // BOOST defines uint_t at least
typedef unsigned int uint_t;
#endif

/* Default assumptions for these types should be ok for most 32bit platforms...
 * feel free to define TH_TYPE_* if necessary to remedy
 */
#ifdef TH_TYPE_I8
typedef unsigned TH_TYPE_I8 uint8_t;    // 8 bits, unsigned
typedef signed TH_TYPE_I8 int8_t;    // 8 bits, signed
#elif !defined(HAVE_INT_TYPES)
typedef unsigned char uint8_t;
typedef signed char int8_t;
#endif


#ifdef TH_TYPE_I16
typedef unsigned TH_TYPE_I16 uint16_t;    // 16 bits, unsigned == 2 BYTEs
typedef signed TH_TYPE_I16 int16_t;    // 16 bits, signed
#elif !defined(HAVE_INT_TYPES)
typedef unsigned short int uint16_t;
typedef signed short int int16_t;
#endif

#ifdef TH_TYPE_I32
typedef unsigned TH_TYPE_I32 uint32_t;    // 32 bits, unsigned == 4 BYTES == 2 WORDs
typedef signed TH_TYPE_I32 int32_t;    // 32 bits, signed
#elif !defined(HAVE_INT_TYPES)
typedef unsigned int uint32_t;
typedef signed int int32_t;
#endif

#ifdef TH_TYPE_I64
typedef unsigned TH_TYPE_I64 uint64_t;    // 64 bits, unsigned == 8 BYTES == 2 DWORDs
typedef signed TH_TYPE_I64 int64_t;    // 64 bits, signed
#elif !defined(HAVE_INT_TYPES)
typedef unsigned long long uint64_t;
typedef signed long long int64_t;
#endif


#ifndef HAVE_INT_TYPES
#if TH_ARCH == 32
typedef long long int intmax_t;
typedef unsigned long long int uintmax_t;
#elif TH_ARCH == 64
typedef long int intmax_t;
typedef unsigned long int uintmax_t;
#endif
#endif


/* Define a boolean type, if needed
 */
#if !defined(FALSE) && !defined(TRUE) && !defined(BOOL)
typedef enum { FALSE = 0, TRUE = 1 } BOOL;
#endif

#ifndef BOOL
#    ifdef bool
#        define BOOL bool
#    else
#        define BOOL int
#    endif
#endif

#endif // TH_TYPES_H
