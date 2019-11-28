/*
 * Simple I/O abstraction and context handling layer
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2012-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#ifndef TH_IOCTX_H
#define TH_IOCTX_H

#include "th_util.h"
#include <time.h>


#ifdef __cplusplus
extern "C" {
#endif


// Typedefs and structures
//
struct th_ioctx;
struct th_ioctx_ops;


/** I/O context structure
 */
typedef struct th_ioctx
{
    char *filename;     ///< Context filename, if any. May be NULL.
    char *mode;         ///< Context's stdio open "mode", may also be NULL.
    void *data;         ///< Internal data
    time_t atime;       ///< Last access time
    int64_t size;       ///< Size in bytes
    int status;         ///< Status
    size_t line;        ///< Line number

    void (*error)(struct th_ioctx *ctx, const int err, const char *msg);
    void (*msg)(struct th_ioctx *ctx, const int level, const char *msg);

    const struct th_ioctx_ops *fops; ///< Pointer to I/O ops struct to be used with this context
} th_ioctx;


typedef struct th_ioctx_ops
{
    char    *name;                   ///< Name of this I/O ops definition

    int     (*fopen)(th_ioctx *ctx);
    void    (*fclose)(th_ioctx *ctx);

    int     (*freset)(th_ioctx *ctx);
    int     (*ferror)(th_ioctx *ctx);
    int     (*fseek)(th_ioctx *ctx, const off_t, const int whence);
    off_t   (*fsize)(th_ioctx *ctx);
    off_t   (*ftell)(th_ioctx *ctx);
    BOOL    (*feof)(th_ioctx *ctx);
    int     (*fgetc)(th_ioctx *ctx);
    int     (*fputc)(int, th_ioctx *ctx);
    size_t  (*fread)(void *ptr, const size_t, const size_t, th_ioctx *ctx);
    size_t  (*fwrite)(const void *ptr, const size_t, const size_t, th_ioctx *ctx);

    char *  (*fgets)(char *str, int size, th_ioctx *ctx);
    int     (*fputs)(const char *str, th_ioctx *ctx);
    int     (*vfprintf)(th_ioctx *ctx, const char *fmt, va_list ap);

} th_ioctx_ops;


//
// Some basic iops
//
extern const th_ioctx_ops th_stdio_io_ops;



//
// I/O context management functions
//
th_ioctx *   th_io_new(const th_ioctx_ops *fops, const char *filename);
int          th_io_open(th_ioctx *ctx, const char *mode);
int          th_io_fopen(th_ioctx **pctx, const th_ioctx_ops *fops, const char *filename, const char *mode);
void         th_io_close(th_ioctx *ctx);
void         th_io_free(th_ioctx *ctx);

BOOL         th_io_set_handlers(th_ioctx *ctx,
             void (*error)(th_ioctx *, const int, const char *msg),
             void (*msg)(th_ioctx *, const int, const char *msg));

void         th_io_error_v(th_ioctx *ctx, const int err, const char *fmt, va_list ap);
void         th_io_msg_v(th_ioctx *ctx, const int level, const char *fmt, va_list ap);
void         th_io_error(th_ioctx *ctx, const int err, const char *fmt, ...);
void         th_io_msg(th_ioctx *ctx, const int level, const char *fmt, ...);


//
// Basic I/O operations
//
int          thfreset(th_ioctx *ctx);
int          thferror(th_ioctx *ctx);
int          thfseek(th_ioctx *ctx, const off_t, const int whence);
off_t        thfsize(th_ioctx *ctx);
off_t        thftell(th_ioctx *ctx);
BOOL         thfeof(th_ioctx *ctx);
int          thfgetc(th_ioctx *ctx);
int          thfputc(int ch, th_ioctx *ctx);
size_t       thfread(void *ptr, const size_t, const size_t, th_ioctx *ctx);
size_t       thfwrite(const void *, const size_t, const size_t, th_ioctx *ctx);
char *       thfgets(char *ptr, int size, th_ioctx *ctx);
int          thfputs(const char *ptr, th_ioctx *ctx);
int          thvfprintf(th_ioctx *ctx, const char *fmt, va_list ap);
int          thfprintf(th_ioctx *ctx, const char *fmt, ...);

int          thfread_str(th_ioctx *ctx, void *ptr, const size_t len);
BOOL         thfread_u8(th_ioctx *ctx, uint8_t *);
int          thfwrite_str(th_ioctx *ctx, const void *ptr, const size_t len);
BOOL         thfwrite_u8(th_ioctx *ctx, const uint8_t);


//
// Endian-handling file read/write routines
//
BOOL         thfwrite_ne16(th_ioctx *ctx, const uint16_t v);
BOOL         thfwrite_ne32(th_ioctx *ctx, const uint32_t v);
BOOL         thfwrite_ne64(th_ioctx *ctx, const uint64_t v);
BOOL         thfread_ne16(th_ioctx *ctx, uint16_t *v);
BOOL         thfread_ne32(th_ioctx *ctx, uint32_t *v);
BOOL         thfread_ne64(th_ioctx *ctx, uint64_t *v);

BOOL         thfwrite_le16(th_ioctx *ctx, const uint16_t v);
BOOL         thfwrite_le32(th_ioctx *ctx, const uint32_t v);
BOOL         thfwrite_le64(th_ioctx *ctx, const uint64_t v);
BOOL         thfread_le16(th_ioctx *ctx, uint16_t *v);
BOOL         thfread_le32(th_ioctx *ctx, uint32_t *v);
BOOL         thfread_le64(th_ioctx *ctx, uint64_t *v);

BOOL         thfwrite_be16(th_ioctx *ctx, const uint16_t v);
BOOL         thfwrite_be32(th_ioctx *ctx, const uint32_t v);
BOOL         thfwrite_be64(th_ioctx *ctx, const uint64_t v);
BOOL         thfread_be16(th_ioctx *ctx, uint16_t *v);
BOOL         thfread_be32(th_ioctx *ctx, uint32_t *v);
BOOL         thfread_be64(th_ioctx *ctx, uint64_t *v);


#ifdef __cplusplus
}
#endif
#endif // TH_IOCTX_H
