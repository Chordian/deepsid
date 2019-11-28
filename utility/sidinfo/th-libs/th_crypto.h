/*
 * MD5 implementation, modified for th-libs from
 * Colin Plumb's implementation by Matti 'ccr' Hämäläinen.
 *
 * This code implements the MD5 message-digest algorithm.
 * The algorithm is due to Ron Rivest.  This code was
 * written by Colin Plumb in 1993, no copyright is claimed.
 * This code is in the public domain; do with it what you wish.
 */
/// @file
/// @brief Cryptography and hash related functions
#ifndef TH_CRYPTO_H
#define TH_CRYPTO_H 1

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "th_endian.h"
#include <stdio.h>

#ifdef __cplusplus
extern "C" {
#endif


/** @def MD5 digest related defines
 */
#define TH_MD5HASH_LENGTH       (16)
#define TH_MD5HASH_LENGTH_CH    (TH_MD5HASH_LENGTH * 2)


/** MD5 digest state structure
 */
typedef struct
{
    uint32_t bits[2];    ///< Message length in bits, lsw first
    uint32_t buf[4];     ///< Digest buffer
    uint8_t in[64];      ///< Accumulate block
} th_md5state_t;

typedef uint8_t th_md5hash_t[TH_MD5HASH_LENGTH]; ///< A structure containing MD5 digest hash


void    th_md5_init(th_md5state_t *ctx);
void    th_md5_append(th_md5state_t *ctx, const uint8_t *buf, size_t len);
void    th_md5_finish(th_md5state_t *ctx, th_md5hash_t digest);
void    th_md5_print(FILE *, const th_md5hash_t digest);

void    th_md5_append_u8(th_md5state_t *ctx, uint8_t val);

#define TH_DEFINE_HEADER(xname) \
void    th_md5_append_ ## xname ## 16 (th_md5state_t *ctx, uint16_t val); \
void    th_md5_append_ ## xname ## 32 (th_md5state_t *ctx, uint32_t val); \
void    th_md5_append_ ## xname ## 64 (th_md5state_t *ctx, uint64_t val);

TH_DEFINE_HEADER(ne)
TH_DEFINE_HEADER(le)
TH_DEFINE_HEADER(be)

#undef TH_DEFINE_HEADER


#ifdef __cplusplus
}
#endif
#endif /* TH_CRYPTO_H */
