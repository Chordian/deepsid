/*
 * Endianess handling
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#ifndef TH_ENDIAN_H
#define TH_ENDIAN_H

#ifdef HAVE_CONFIG_H
#  include "config.h"
#endif
#include "th_types.h"

#ifdef __cplusplus
extern "C" {
#endif

/* Check endianess
 */
#ifndef TH_BYTEORDER
#  error Undefined byteorder (TH_BYTEORDER not set.)
#endif

#define TH_BIG_ENDIAN     1234
#define TH_LITTLE_ENDIAN  4321


/* Endianess swapping macros
 */
#define TH_SWAP_16_LE_BE(value)    ((uint16_t) (   \
    (uint16_t) ((uint16_t) (value) >> 8) |      \
    (uint16_t) ((uint16_t) (value) << 8)) )


#define TH_SWAP_32_LE_BE(value) ((uint32_t) (               \
    (((uint32_t) (value) & (uint32_t) 0x000000ffU) << 24) | \
    (((uint32_t) (value) & (uint32_t) 0x0000ff00U) <<  8) | \
    (((uint32_t) (value) & (uint32_t) 0x00ff0000U) >>  8) | \
    (((uint32_t) (value) & (uint32_t) 0xff000000U) >> 24)))

#define TH_SWAP_64_LE_BE(value) ((uint64_t) (                           \
    (((uint64_t) (value) & (uint64_t) 0x00000000000000ffULL) << 56) |   \
    (((uint64_t) (value) & (uint64_t) 0x000000000000ff00ULL) << 40) |   \
    (((uint64_t) (value) & (uint64_t) 0x0000000000ff0000ULL) << 24) |   \
    (((uint64_t) (value) & (uint64_t) 0x00000000ff000000ULL) <<  8) |   \
    (((uint64_t) (value) & (uint64_t) 0x000000ff00000000ULL) >>  8) |   \
    (((uint64_t) (value) & (uint64_t) 0x0000ff0000000000ULL) >> 24) |   \
    (((uint64_t) (value) & (uint64_t) 0x00ff000000000000ULL) >> 40) |   \
    (((uint64_t) (value) & (uint64_t) 0xff00000000000000ULL) >> 56)))


/* Macros that swap only when needed ...
 */
#if (TH_BYTEORDER == TH_BIG_ENDIAN)

#define TH_LE16_TO_NATIVE(value) TH_SWAP_16_LE_BE(value)
#define TH_LE32_TO_NATIVE(value) TH_SWAP_32_LE_BE(value)
#define TH_NATIVE_TO_LE16(value) TH_SWAP_16_LE_BE(value)
#define TH_NATIVE_TO_LE32(value) TH_SWAP_32_LE_BE(value)

#define TH_BE16_TO_NATIVE(value) ((uint16_t) (value))
#define TH_BE32_TO_NATIVE(value) ((uint32_t) (value))
#define TH_NATIVE_TO_BE16(value) ((uint16_t) (value))
#define TH_NATIVE_TO_BE32(value) ((uint32_t) (value))

#define TH_LE64_TO_NATIVE(value) TH_SWAP_64_LE_BE(value)
#define TH_NATIVE_TO_LE64(value) TH_SWAP_64_LE_BE(value)
#define TH_BE64_TO_NATIVE(value) ((uint64_t) (value))
#define TH_NATIVE_TO_BE64(value) ((uint64_t) (value))

// !TH_BIG_ENDIAN
#elif (TH_BYTEORDER == TH_LITTLE_ENDIAN)

#define TH_LE16_TO_NATIVE(value) ((uint16_t) (value))
#define TH_LE32_TO_NATIVE(value) ((uint32_t) (value))
#define TH_NATIVE_TO_LE16(value) ((uint16_t) (value))
#define TH_NATIVE_TO_LE32(value) ((uint32_t) (value))

#define TH_BE16_TO_NATIVE(value) TH_SWAP_16_LE_BE(value)
#define TH_BE32_TO_NATIVE(value) TH_SWAP_32_LE_BE(value)
#define TH_NATIVE_TO_BE16(value) TH_SWAP_16_LE_BE(value)
#define TH_NATIVE_TO_BE32(value) TH_SWAP_32_LE_BE(value)

#define TH_LE64_TO_NATIVE(value) ((uint64_t) (value))
#define TH_NATIVE_TO_LE64(value) ((uint64_t) (value))
#define TH_BE64_TO_NATIVE(value) TH_SWAP_64_LE_BE(value)
#define TH_NATIVE_TO_BE64(value) TH_SWAP_64_LE_BE(value)

#else
#    error Unsupported byte order!
#endif

//
// NE = Native Endian, aka same as native
// Provided for completeness
//
#define TH_NE16_TO_NATIVE(value) (value)
#define TH_NE32_TO_NATIVE(value) (value)
#define TH_NE64_TO_NATIVE(value) (value)

#define TH_NATIVE_TO_NE16(value) (value)
#define TH_NATIVE_TO_NE32(value) (value)
#define TH_NATIVE_TO_NE64(value) (value)


#ifdef __cplusplus
}
#endif
#endif // TH_ENDIAN_H
