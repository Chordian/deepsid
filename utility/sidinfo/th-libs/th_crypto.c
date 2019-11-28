/*
 * MD5 implementation, modified for th-libs from
 * Colin Plumb's implementation by Matti 'ccr' Hämäläinen.
 *
 * This code implements the MD5 message-digest algorithm.
 * The algorithm is due to Ron Rivest.  This code was
 * written by Colin Plumb in 1993, no copyright is claimed.
 * This code is in the public domain; do with it what you wish.
 */
#include "th_crypto.h"
#include "th_endian.h"
#include "th_util.h"


#if TH_BYTEORDER == TH_LITTLE_ENDIAN
#  define th_md5_bytereverse(buf, len)    /* Nothing */
#elif TH_BYTEORDER == TH_BIG_ENDIAN
static void th_md5_bytereverse(uint8_t *buf, size_t l)
{
    do
    {
        uint32_t t = (uint32_t) ((uint32_t) buf[3] << 8 | buf[2]) << 16 | ((uint32_t) buf[1] << 8 | buf[0]);
        *(uint32_t *) buf = t;
        buf += sizeof(uint32_t);
    } while (--l);
}
#else
#  error Unsupported endianess!
#endif


/* Start MD5 accumulation.  Set bit count to 0 and buffer
 * to mysterious initialization constants.
 */
void th_md5_init(th_md5state_t *ctx)
{
    ctx->buf[0] = 0x67452301;
    ctx->buf[1] = 0xefcdab89;
    ctx->buf[2] = 0x98badcfe;
    ctx->buf[3] = 0x10325476;

    ctx->bits[0] = 0;
    ctx->bits[1] = 0;
}


/* The core of the MD5 algorithm, this alters an existing MD5 hash to
 * reflect the addition of 16 longwords of new data. th_md5_update()
 * blocks the data and converts bytes into longwords for this routine.
 */
#define F1(x, y, z) (z ^ (x & (y ^ z)))
#define F2(x, y, z) F1(z, x, y)
#define F3(x, y, z) (x ^ y ^ z)
#define F4(x, y, z) (y ^ (x | ~z))
#define MD5STEP(f, w, x, y, z, data, s) ( w += f(x, y, z) + data,  w = w<<s | w>>(32-s),  w += x )

static void th_md5_transform(uint32_t buf[4], uint32_t const in[16])
{
    register uint32_t a, b, c, d;

    a = buf[0];
    b = buf[1];
    c = buf[2];
    d = buf[3];

    MD5STEP(F1, a, b, c, d, in[ 0] + 0xd76aa478, 7);
    MD5STEP(F1, d, a, b, c, in[ 1] + 0xe8c7b756, 12);
    MD5STEP(F1, c, d, a, b, in[ 2] + 0x242070db, 17);
    MD5STEP(F1, b, c, d, a, in[ 3] + 0xc1bdceee, 22);
    MD5STEP(F1, a, b, c, d, in[ 4] + 0xf57c0faf, 7);
    MD5STEP(F1, d, a, b, c, in[ 5] + 0x4787c62a, 12);
    MD5STEP(F1, c, d, a, b, in[ 6] + 0xa8304613, 17);
    MD5STEP(F1, b, c, d, a, in[ 7] + 0xfd469501, 22);
    MD5STEP(F1, a, b, c, d, in[ 8] + 0x698098d8, 7);
    MD5STEP(F1, d, a, b, c, in[ 9] + 0x8b44f7af, 12);
    MD5STEP(F1, c, d, a, b, in[10] + 0xffff5bb1, 17);
    MD5STEP(F1, b, c, d, a, in[11] + 0x895cd7be, 22);
    MD5STEP(F1, a, b, c, d, in[12] + 0x6b901122, 7);
    MD5STEP(F1, d, a, b, c, in[13] + 0xfd987193, 12);
    MD5STEP(F1, c, d, a, b, in[14] + 0xa679438e, 17);
    MD5STEP(F1, b, c, d, a, in[15] + 0x49b40821, 22);

    MD5STEP(F2, a, b, c, d, in[ 1] + 0xf61e2562, 5);
    MD5STEP(F2, d, a, b, c, in[ 6] + 0xc040b340, 9);
    MD5STEP(F2, c, d, a, b, in[11] + 0x265e5a51, 14);
    MD5STEP(F2, b, c, d, a, in[ 0] + 0xe9b6c7aa, 20);
    MD5STEP(F2, a, b, c, d, in[ 5] + 0xd62f105d, 5);
    MD5STEP(F2, d, a, b, c, in[10] + 0x02441453, 9);
    MD5STEP(F2, c, d, a, b, in[15] + 0xd8a1e681, 14);
    MD5STEP(F2, b, c, d, a, in[ 4] + 0xe7d3fbc8, 20);
    MD5STEP(F2, a, b, c, d, in[ 9] + 0x21e1cde6, 5);
    MD5STEP(F2, d, a, b, c, in[14] + 0xc33707d6, 9);
    MD5STEP(F2, c, d, a, b, in[ 3] + 0xf4d50d87, 14);
    MD5STEP(F2, b, c, d, a, in[ 8] + 0x455a14ed, 20);
    MD5STEP(F2, a, b, c, d, in[13] + 0xa9e3e905, 5);
    MD5STEP(F2, d, a, b, c, in[ 2] + 0xfcefa3f8, 9);
    MD5STEP(F2, c, d, a, b, in[ 7] + 0x676f02d9, 14);
    MD5STEP(F2, b, c, d, a, in[12] + 0x8d2a4c8a, 20);

    MD5STEP(F3, a, b, c, d, in[ 5] + 0xfffa3942, 4);
    MD5STEP(F3, d, a, b, c, in[ 8] + 0x8771f681, 11);
    MD5STEP(F3, c, d, a, b, in[11] + 0x6d9d6122, 16);
    MD5STEP(F3, b, c, d, a, in[14] + 0xfde5380c, 23);
    MD5STEP(F3, a, b, c, d, in[ 1] + 0xa4beea44, 4);
    MD5STEP(F3, d, a, b, c, in[ 4] + 0x4bdecfa9, 11);
    MD5STEP(F3, c, d, a, b, in[ 7] + 0xf6bb4b60, 16);
    MD5STEP(F3, b, c, d, a, in[10] + 0xbebfbc70, 23);
    MD5STEP(F3, a, b, c, d, in[13] + 0x289b7ec6, 4);
    MD5STEP(F3, d, a, b, c, in[ 0] + 0xeaa127fa, 11);
    MD5STEP(F3, c, d, a, b, in[ 3] + 0xd4ef3085, 16);
    MD5STEP(F3, b, c, d, a, in[ 6] + 0x04881d05, 23);
    MD5STEP(F3, a, b, c, d, in[ 9] + 0xd9d4d039, 4);
    MD5STEP(F3, d, a, b, c, in[12] + 0xe6db99e5, 11);
    MD5STEP(F3, c, d, a, b, in[15] + 0x1fa27cf8, 16);
    MD5STEP(F3, b, c, d, a, in[ 2] + 0xc4ac5665, 23);

    MD5STEP(F4, a, b, c, d, in[ 0] + 0xf4292244, 6);
    MD5STEP(F4, d, a, b, c, in[ 7] + 0x432aff97, 10);
    MD5STEP(F4, c, d, a, b, in[14] + 0xab9423a7, 15);
    MD5STEP(F4, b, c, d, a, in[ 5] + 0xfc93a039, 21);
    MD5STEP(F4, a, b, c, d, in[12] + 0x655b59c3, 6);
    MD5STEP(F4, d, a, b, c, in[ 3] + 0x8f0ccc92, 10);
    MD5STEP(F4, c, d, a, b, in[10] + 0xffeff47d, 15);
    MD5STEP(F4, b, c, d, a, in[ 1] + 0x85845dd1, 21);
    MD5STEP(F4, a, b, c, d, in[ 8] + 0x6fa87e4f, 6);
    MD5STEP(F4, d, a, b, c, in[15] + 0xfe2ce6e0, 10);
    MD5STEP(F4, c, d, a, b, in[ 6] + 0xa3014314, 15);
    MD5STEP(F4, b, c, d, a, in[13] + 0x4e0811a1, 21);
    MD5STEP(F4, a, b, c, d, in[ 4] + 0xf7537e82, 6);
    MD5STEP(F4, d, a, b, c, in[11] + 0xbd3af235, 10);
    MD5STEP(F4, c, d, a, b, in[ 2] + 0x2ad7d2bb, 15);
    MD5STEP(F4, b, c, d, a, in[ 9] + 0xeb86d391, 21);

    buf[0] += a;
    buf[1] += b;
    buf[2] += c;
    buf[3] += d;
}


/* Update context to reflect the concatenation of another buffer full
 * of bytes.
 */
void th_md5_append(th_md5state_t *ctx, const uint8_t *buf, size_t len)
{
    uint32_t t;

    /* Update bitcount */
    t = ctx->bits[0];
    if ((ctx->bits[0] = t + ((uint32_t) len << 3)) < t)
        ctx->bits[1]++;    /* Carry from low to high */
    ctx->bits[1] += len >> 29;

    t = (t >> 3) & 0x3f;    /* Bytes already in shsInfo->data */

    /* Handle any leading odd-sized chunks */
    if (t)
    {
        uint8_t *p = (uint8_t *) ctx->in + t;

        t = 64 - t;
        if (len < t)
        {
            memcpy(p, buf, len);
            return;
        }
        memcpy(p, buf, t);
        th_md5_bytereverse(ctx->in, 16);
        th_md5_transform(ctx->buf, (uint32_t *) ctx->in);
        buf += t;
        len -= t;
    }

    /* Process data in 64-byte chunks */
    while (len >= 64)
    {
        memcpy(ctx->in, buf, 64);
        th_md5_bytereverse(ctx->in, 16);
        th_md5_transform(ctx->buf, (uint32_t *) ctx->in);
        buf += 64;
        len -= 64;
    }

    /* Handle any remaining bytes of data. */
    memcpy(ctx->in, buf, len);
}


/* Final wrapup - pad to 64-byte boundary with the bit pattern
 * 1 0* (64-bit count of bits processed, MSB-first)
 */
void th_md5_finish(th_md5state_t *ctx, th_md5hash_t digest)
{
    size_t count;
    uint8_t *p;

    /* Compute number of bytes mod 64 */
    count = (ctx->bits[0] >> 3) & 0x3F;

    /* Set the first char of padding to 0x80.  This is safe since there is
       always at least one byte free */
    p = ctx->in + count;
    *p++ = 0x80;

    /* Bytes of padding needed to make 64 bytes */
    count = 64 - 1 - count;

    /* Pad out to 56 mod 64 */
    if (count < 8)
    {
        /* Two lots of padding:  Pad the first block to 64 bytes */
        memset(p, 0, count);
        th_md5_bytereverse(ctx->in, 16);
        th_md5_transform(ctx->buf, (uint32_t *) ctx->in);

        /* Now fill the next block with 56 bytes */
        memset(ctx->in, 0, 56);
    }
    else
    {
        /* Pad block to 56 bytes */
        memset(p, 0, count - 8);
    }
    th_md5_bytereverse(ctx->in, 14);

    /* Append length in bits and transform */
    memcpy(((uint32_t *) ctx->in) + 14, &ctx->bits[0], sizeof(uint32_t));
    memcpy(((uint32_t *) ctx->in) + 15, &ctx->bits[1], sizeof(uint32_t));

    th_md5_transform(ctx->buf, (uint32_t *) ctx->in);
    th_md5_bytereverse((uint8_t *) ctx->buf, 4);
    memcpy(digest, ctx->buf, 16);
    memset(ctx, 0, sizeof(*ctx));
}


void th_md5_print(FILE *fp, const th_md5hash_t digest)
{
    int i;
    for (i = 0; i < TH_MD5HASH_LENGTH; i++)
        fprintf(fp, "%02x", digest[i]);
}


void th_md5_append_u8(th_md5state_t *ctx, uint8_t val)
{
    th_md5_append(ctx, &val, 1);
}


#define TH_DEFINE_FUNCS(xname, xtype) \
void th_md5_append_ ## xname ## 16 (th_md5state_t *ctx, uint16_t pval) \
{ \
    uint16_t val = TH_NATIVE_TO_ ## xtype ## 16 (pval); \
    th_md5_append(ctx, (uint8_t *) &val, sizeof(val)); \
} \
\
void th_md5_append_ ## xname ## 32 (th_md5state_t *ctx, uint32_t pval) \
{ \
    uint32_t val = TH_NATIVE_TO_ ## xtype ## 32 (pval); \
    th_md5_append(ctx, (uint8_t *) &val, sizeof(val)); \
} \
\
void th_md5_append_ ## xname ## 64 (th_md5state_t *ctx, uint64_t pval) \
{ \
    uint64_t val = TH_NATIVE_TO_ ## xtype ## 64 (pval); \
    th_md5_append(ctx, (uint8_t *) &val, sizeof(val)); \
}


TH_DEFINE_FUNCS(ne, NE)
TH_DEFINE_FUNCS(le, LE)
TH_DEFINE_FUNCS(be, BE)
