/*
 * Simple I/O abstraction and context handling layer
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2012-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#include "th_ioctx.h"
#include "th_string.h"
#include "th_endian.h"
#include <stdio.h>


static void th_io_update_atime(th_ioctx *ctx)
{
    ctx->atime = time(NULL);
}


th_ioctx * th_io_new(const th_ioctx_ops *fops, const char *filename)
{
    th_ioctx *ctx = th_malloc0(sizeof(th_ioctx));
    if (ctx == NULL)
        return NULL;

    ctx->fops = fops;
    ctx->filename = th_strdup(filename);
    if (filename != NULL && ctx->filename == NULL)
        goto err;

    return ctx;

err:
    th_io_free(ctx);
    return NULL;
}


int th_io_open(th_ioctx *ctx, const char *mode)
{
    if (ctx == NULL)
        return THERR_NULLPTR;

    ctx->mode = th_strdup(mode);

    if (ctx->fops->fopen != NULL)
        ctx->status = ctx->fops->fopen(ctx);

    return ctx->status;
}


int th_io_fopen(th_ioctx **pctx, const th_ioctx_ops *fops, const char *filename, const char *mode)
{
    th_ioctx *ctx;
    int res;

    if ((*pctx = ctx = th_io_new(fops, filename)) == NULL)
        return THERR_MALLOC;

    if ((res = th_io_open(ctx, mode)) != THERR_OK)
        goto err;

    return THERR_OK;

err:
    th_io_free(ctx);
    *pctx = NULL;
    return res;
}


void th_io_close(th_ioctx *ctx)
{
    if (ctx != NULL)
    {
        if (ctx->fops->fclose != NULL)
            ctx->fops->fclose(ctx);
    }
}


void th_io_free(th_ioctx *ctx)
{
    if (ctx != NULL)
    {
        th_io_close(ctx);

        th_free_r(&ctx->filename);
        th_free_r(&ctx->mode);

        th_free(ctx);
    }
}


BOOL th_io_set_handlers(th_ioctx *ctx,
    void (*error)(th_ioctx *, const int, const char *msg),
    void (*msg)(th_ioctx *, const int, const char *msg))
{
    if (ctx == NULL)
        return FALSE;

    ctx->error = error;
    ctx->msg = msg;

    return TRUE;
}


void th_io_error_v(th_ioctx *ctx, const int err, const char *fmt, va_list ap)
{
    char *msg = th_strdup_vprintf(fmt, ap);

    if (ctx->error != NULL)
        ctx->error((struct th_ioctx *) ctx, err, msg);
    else
        THERR("'%s' #%" PRIu_SIZE_T ": %s\n", ctx->filename, ctx->line, msg);

    th_free(msg);
}


void th_io_error(th_ioctx *ctx, const int err, const char *fmt, ...)
{
    va_list ap;
    va_start(ap, fmt);
    th_io_error_v(ctx, err, fmt, ap);
    va_end(ap);
}


void th_io_msg_v(th_ioctx *ctx, const int level, const char *fmt, va_list ap)
{
    if (ctx->msg != NULL)
    {
        char *msg = th_strdup_vprintf(fmt, ap);
        ctx->msg((struct th_ioctx *) ctx, level, msg);
        th_free(msg);
    }
    else
        THMSG_V(level, fmt, ap);
}


void th_io_msg(th_ioctx *ctx, const int level, const char *fmt, ...)
{
    va_list ap;
    va_start(ap, fmt);
    th_io_msg_v(ctx, level, fmt, ap);
    va_end(ap);
}


int thfreset(th_ioctx *ctx)
{
    if (ctx == NULL)
        return THERR_NULLPTR;

    if (ctx->fops == NULL || ctx->fops->freset == NULL)
        return THERR_OK;

    return ctx->fops->freset(ctx);
}


int thferror(th_ioctx *ctx)
{
    th_io_update_atime(ctx);
    return ctx->fops->ferror(ctx);
}


int thfseek(th_ioctx *ctx, const off_t offset, int whence)
{
    th_io_update_atime(ctx);
    return ctx->fops->fseek(ctx, offset, whence);
}


off_t thfsize(th_ioctx *ctx)
{
    th_io_update_atime(ctx);
    return ctx->fops->fsize(ctx);
}


off_t thftell(th_ioctx *ctx)
{
    th_io_update_atime(ctx);
    return ctx->fops->ftell(ctx);
}


BOOL thfeof(th_ioctx *ctx)
{
    th_io_update_atime(ctx);
    return ctx->fops->feof(ctx);
}


int thfgetc(th_ioctx *ctx)
{
    th_io_update_atime(ctx);
    return ctx->fops->fgetc(ctx);
}


int thfputc(int v, th_ioctx *ctx)
{
    th_io_update_atime(ctx);
    return ctx->fops->fputc(v, ctx);
}


size_t thfread(void *ptr, size_t size, size_t nmemb, th_ioctx *ctx)
{
    th_io_update_atime(ctx);
    return ctx->fops->fread(ptr, size, nmemb, ctx);
}


size_t thfwrite(const void *ptr, size_t size, size_t nmemb, th_ioctx *ctx)
{
    th_io_update_atime(ctx);
    return ctx->fops->fwrite(ptr, size, nmemb, ctx);
}


char *thfgets(char *str, int size, th_ioctx *ctx)
{
    char *ptr = str, *end = str + size - 1;
    int c;

    if (size <= 0)
        return NULL;

    while (ptr < end && (c = ctx->fops->fgetc(ctx)) != EOF)
    {
        *ptr++ = c;
        if (c == '\n')
            break;
    }
    *ptr = 0;

    return (ptr > str) ? str : NULL;
}


int thfputs(const char *ptr, th_ioctx *ctx)
{
    if (ctx->fops->fputs != NULL)
        return ctx->fops->fputs(ptr, ctx);

    const char *p = ptr;
    int retv = 0;
    while (*p && (retv = ctx->fops->fputc(*p, ctx)) != EOF) p++;
    return retv;
}


int thvfprintf(th_ioctx *ctx, const char *fmt, va_list ap)
{
    if (ctx->fops->vfprintf != NULL)
        return ctx->fops->vfprintf(ctx, fmt, ap);
    else
    {
        char *msg = th_strdup_printf(fmt, ap);
        int rval = thfputs(msg, ctx);
        th_free(msg);
        return rval;
    }
}


int thfprintf(th_ioctx *ctx, const char *fmt, ...)
{
    int retv;
    va_list ap;
    va_start(ap, fmt);
    retv = thvfprintf(ctx, fmt, ap);
    va_end(ap);
    return retv;
}


BOOL thfread_str(th_ioctx *ctx, void *ptr, const size_t len)
{
    return (thfread(ptr, sizeof(uint8_t), len, ctx) == len);
}


BOOL thfread_u8(th_ioctx *ctx, uint8_t *val)
{
    return (thfread(val, sizeof(uint8_t), 1, ctx) == 1);
}


BOOL thfwrite_str(th_ioctx *ctx, const void *ptr, const size_t len)
{
    return (thfwrite(ptr, sizeof(uint8_t), len, ctx) == len);
}


BOOL thfwrite_u8(th_ioctx *ctx, const uint8_t val)
{
    return (thfwrite(&val, sizeof(uint8_t), 1, ctx) == 1);
}


//
// File routines for endian-dependant data
//
#define TH_DEFINE_FUNC(xname, xtype, xmacro)               \
BOOL thfread_ ## xname (th_ioctx *ctx, xtype *v)           \
{                                                          \
    xtype result;                                          \
    if (thfread(&result, sizeof( xtype ), 1, ctx) != 1)    \
        return FALSE;                                      \
    *v = TH_ ## xmacro ## _TO_NATIVE (result);             \
    return TRUE;                                           \
}                                                          \
                                                           \
BOOL thfwrite_ ## xname (th_ioctx *ctx, const xtype v)     \
{                                                          \
    xtype result = TH_NATIVE_TO_ ## xmacro (v);            \
    if (thfwrite(&result, sizeof( xtype ), 1, ctx) != 1)   \
        return FALSE;                                      \
    return TRUE;                                           \
}


TH_DEFINE_FUNC(le16, uint16_t, LE16)
TH_DEFINE_FUNC(le32, uint32_t, LE32)

TH_DEFINE_FUNC(be16, uint16_t, BE16)
TH_DEFINE_FUNC(be32, uint32_t, BE32)

TH_DEFINE_FUNC(le64, uint64_t, LE64)
TH_DEFINE_FUNC(be64, uint64_t, BE64)

#undef TH_DEFINE_FUNC


//
// stdio wrappers for I/O contexts
//
#define CTX_FH ((FILE *) ctx->data)


static int th_stdio_fopen(th_ioctx *ctx)
{
    ctx->data = (void *) fopen(ctx->filename, ctx->mode);
    ctx->status = th_get_error();
    return (ctx->data != NULL) ? THERR_OK : THERR_FOPEN;
}


static void th_stdio_fclose(th_ioctx *ctx)
{
    if (CTX_FH != NULL)
    {
        fclose(CTX_FH);
        ctx->data = NULL;
    }
}


static int th_stdio_ferror(th_ioctx *ctx)
{
    return ctx->status;
}


static off_t th_stdio_ftell(th_ioctx *ctx)
{
    return ftello(CTX_FH);
}


static int th_stdio_fseek(th_ioctx *ctx, const off_t pos, const int whence)
{
    int ret = fseeko(CTX_FH, pos, whence);
    ctx->status = th_get_error();
    return ret;
}


static int th_stdio_freset(th_ioctx *ctx)
{
    if (CTX_FH != NULL)
        return th_stdio_fseek(ctx, 0, SEEK_SET);
    else
        return THERR_OK;
}


static off_t th_stdio_fsize(th_ioctx *ctx)
{
    off_t savePos, fileSize;

    // Check if the size is cached
    if (ctx->size != 0)
        return ctx->size;

    // Get file size
    if ((savePos = th_stdio_ftell(ctx)) < 0)
        return -1;

    if (th_stdio_fseek(ctx, 0, SEEK_END) != 0)
        return -1;

    if ((fileSize = th_stdio_ftell(ctx)) < 0)
        return -1;

    if (th_stdio_fseek(ctx, savePos, SEEK_SET) != 0)
        return -1;

    ctx->size = fileSize;
    return fileSize;
}


static BOOL th_stdio_feof(th_ioctx *ctx)
{
    return feof(CTX_FH);
}


static int th_stdio_fgetc(th_ioctx *ctx)
{
    int ret = fgetc(CTX_FH);
    ctx->status = th_get_error();
    return ret;
}


static int th_stdio_fputc(int v, th_ioctx *ctx)
{
    int ret = fputc(v, CTX_FH);
    ctx->status = th_get_error();
    return ret;
}


static size_t th_stdio_fread(void *ptr, size_t size, size_t nmemb, th_ioctx *ctx)
{
    size_t ret = fread(ptr, size, nmemb, CTX_FH);
    ctx->status = th_get_error();
    return ret;
}


static size_t th_stdio_fwrite(const void *ptr, size_t size, size_t nmemb, th_ioctx *ctx)
{
    size_t ret = fwrite(ptr, size, nmemb, CTX_FH);
    ctx->status = th_get_error();
    return ret;
}


static char * th_stdio_fgets(char *str, int size, th_ioctx *ctx)
{
    char *ret = fgets(str, size, CTX_FH);
    ctx->status = th_get_error();
    return ret;
}


static int th_stdio_fputs(const char *str, th_ioctx *ctx)
{
    int ret = fputs(str, CTX_FH);
    ctx->status = th_get_error();
    return ret;
}


static int th_stdio_vfprintf(th_ioctx *ctx, const char *fmt, va_list ap)
{
    int ret = vfprintf(CTX_FH, fmt, ap);
    ctx->status = th_get_error();
    return ret;
}


const th_ioctx_ops th_stdio_io_ops =
{
    "stdio",

    th_stdio_fopen,
    th_stdio_fclose,

    th_stdio_freset,
    th_stdio_ferror,
    th_stdio_fseek,
    th_stdio_fsize,
    th_stdio_ftell,
    th_stdio_feof,
    th_stdio_fgetc,
    th_stdio_fputc,
    th_stdio_fread,
    th_stdio_fwrite,

    th_stdio_fgets,
    th_stdio_fputs,
    th_stdio_vfprintf,
};
