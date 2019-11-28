/*
 * Various data structure functions
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#ifndef TH_DATASTRUCT_H
#define TH_DATASTRUCT_H

#include "th_util.h"

#ifdef __cplusplus
extern "C" {
#endif


/* Doubly linked list handling
 */
typedef struct _th_llist_t
{
    size_t num;  // Number of nodes in the list, meaningful ONLY in the current root node of the list
    struct _th_llist_t *prev, *next;
    void *data;  // Pointer to data payload of this node
} th_llist_t;


th_llist_t * th_llist_new(void *data);
void      th_llist_free(th_llist_t *list);
void      th_llist_free_func(th_llist_t *list, void (*freefunc)(void *data));
void      th_llist_free_func_node(th_llist_t *list, void (*freefunc)(th_llist_t *node));

void      th_llist_append_node(th_llist_t **list, th_llist_t *node);
th_llist_t * th_llist_append(th_llist_t **list, void *data);
void      th_llist_prepend_node(th_llist_t **list, th_llist_t *node);
th_llist_t * th_llist_prepend(th_llist_t **list, void *data);
void      th_llist_delete(th_llist_t **list, const void *data);
void      th_llist_delete_node(th_llist_t **list, th_llist_t *node);
void      th_llist_delete_node_fast(th_llist_t **list, th_llist_t *node);

th_llist_t * th_llist_get_nth(th_llist_t *list, const size_t n);
size_t    th_llist_length(const th_llist_t *list);
ssize_t   th_llist_position(const th_llist_t *list, const th_llist_t *node);

void      th_llist_foreach(th_llist_t *list, void (*func)(th_llist_t *node, void *userdata), void *data);
int       th_llist_foreach_cond(th_llist_t *list, int (*func)(th_llist_t *node, void *userdata), void *data, th_llist_t **res);

th_llist_t * th_llist_find(th_llist_t *list, const void *data);
th_llist_t * th_llist_find_func(th_llist_t *list, const void *userdata, int (compare)(const void *, const void *));


/* Ringbuffer implementation
 */
typedef struct
{
    char **data;
    int n, size;
    void (*deallocator)(void *);
} th_ringbuf_t;

th_ringbuf_t * th_ringbuf_new(const size_t size, void (*mdeallocator)(void *));
BOOL         th_ringbuf_grow(th_ringbuf_t *buf, const size_t n);
void         th_ringbuf_free(th_ringbuf_t *buf);
void         th_ringbuf_add(th_ringbuf_t *buf, void *ptr);


/* Growing buffers
 */
#define TH_BUFGROW       (32)


typedef struct
{
    BOOL allocated;
    uint8_t *data;
    size_t size, len, mingrow;
} th_growbuf_t;


/* Simple growing string buffer
 */
BOOL    th_strbuf_grow(char **buf, size_t *bufsize, size_t *len, const size_t grow);
BOOL    th_strbuf_putch(char **buf, size_t *bufsize, size_t *len, const char ch);
BOOL    th_strbuf_putsn(char **buf, size_t *bufsize, size_t *len, const char *str, const size_t slen);
BOOL    th_strbuf_puts(char **buf, size_t *bufsize, size_t *len, const char *str);


/* Growing byte buffer
 */
void    th_growbuf_init(th_growbuf_t *buf, const size_t mingrow);
void    th_growbuf_clear(th_growbuf_t *buf);
th_growbuf_t *th_growbuf_new(const size_t mingrow);
void    th_growbuf_free(th_growbuf_t *buf);


BOOL    th_growbuf_grow(th_growbuf_t *buf, const size_t grow);
BOOL    th_growbuf_puts(th_growbuf_t *buf, const char *str, BOOL eos);
BOOL    th_growbuf_putch(th_growbuf_t *buf, const char ch);
BOOL    th_growbuf_put_str(th_growbuf_t *buf, const void *s, const size_t len);
BOOL    th_growbuf_put_u8(th_growbuf_t *buf, const uint8_t val);
BOOL    th_growbuf_put_u16_be(th_growbuf_t *buf, const uint16_t val);
BOOL    th_growbuf_put_u16_le(th_growbuf_t *buf, const uint16_t val);
BOOL    th_growbuf_put_u32_be(th_growbuf_t *buf, const uint32_t val);
BOOL    th_growbuf_put_u32_le(th_growbuf_t *buf, const uint32_t val);


#ifdef __cplusplus
}
#endif
#endif // TH_DATASTRUCT_H
