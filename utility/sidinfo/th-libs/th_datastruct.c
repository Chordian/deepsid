/*
 * Various data structure functions
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#include "th_datastruct.h"

/*
 * Doubly linked list handling
 *
 * In this implementation first node's prev points to last node of the list,
 * and last node's next is NULL. This way we can semi-efficiently traverse to
 * beginning and end of the list, assuming user does not do weird things.
 */
th_llist_t * th_llist_new(void *data)
{
    th_llist_t *res = th_malloc0(sizeof(th_llist_t));
    res->data = data;
    return res;
}


void th_llist_free_func_node(th_llist_t *list, void (*freefunc)(th_llist_t *))
{
    th_llist_t *curr = list;

    while (curr != NULL)
    {
        th_llist_t *next = curr->next;
        freefunc(curr);
        curr = next;
    }
}


void th_llist_free_func(th_llist_t *list, void (*freefunc)(void *data))
{
    th_llist_t *curr = list;

    while (curr != NULL)
    {
        th_llist_t *next = curr->next;
        if (curr->data != NULL)
            freefunc(curr->data);
        th_free(curr);
        curr = next;
    }
}


void th_llist_free(th_llist_t *list)
{
    th_llist_t *curr = list;

    while (curr != NULL)
    {
        th_llist_t *next = curr->next;
        th_free(curr);
        curr = next;
    }
}


void th_llist_append_node(th_llist_t **list, th_llist_t *node)
{
    if (*list != NULL)
    {
        node->prev = (*list)->prev;
        (*list)->prev->next = node;
        (*list)->prev = node;
        (*list)->num++;
    }
    else
    {
        *list = node;
        node->prev = node;
        (*list)->num = 1;
    }

    node->next = NULL;
}


th_llist_t *th_llist_append(th_llist_t **list, void *data)
{
    th_llist_t *node = th_llist_new(data);

    th_llist_append_node(list, node);

    return node;
}


void th_llist_prepend_node(th_llist_t **list, th_llist_t *node)
{
    if (*list != NULL)
    {
        node->prev = (*list)->prev;
        node->next = *list;
        (*list)->prev = node;
        node->num = (*list)->num + 1;
        *list = node;
    }
    else
    {
        *list = node->prev = node;
        node->next = NULL;
        (*list)->num = 1;
    }

}


th_llist_t *th_llist_prepend(th_llist_t **list, void *data)
{
    th_llist_t *node = th_llist_new(data);

    th_llist_prepend_node(list, node);

    return node;
}

/*
1) Remove a middle node

    node0->prev->next = node->next (node1)
    node0->next->prev = node->prev (list)

    node2 <- list <=> node0 <=> node1 <=> node2 -> NULL
    node2 <- list <=> node1 <=> node2 -> NULL

2) Remove first node when many items


    node2 <- list <=> node0 <=> node1 <=> node2 -> NULL
    node2 <- node0 <=> node1 <=> node2 -> NULL

    *list = node0

3) Remove last node in list

    if (node->next == NULL) {
        list->prev = node->prev;
        node->prev->next = NULL;
    }

    node2 <- list <=> node0 <=> node1 <=> node2 -> NULL
    node1 <- list <=> node0 <=> node1 -> NULL

4) Remove last

    list <- list -> NULL


*/
void th_llist_delete_node_fast(th_llist_t **list, th_llist_t *node)
{
    if (node == *list)
    {
        // First node in list
        th_llist_t *tmp = (*list)->next;
        if (tmp != NULL)
        {
            tmp->num = (*list)->num - 1;
            tmp->prev = (*list)->prev;
        }
        *list = tmp;
    }
    else
    {
        // Somewhere in middle or end
        if (node->prev != NULL)
            node->prev->next = node->next;

        if (node->next != NULL)
            node->next->prev = node->prev;
        else
            (*list)->prev = node; // Last node

        (*list)->num--;
    }

    node->next = node->prev = NULL;
}


void th_llist_delete_node(th_llist_t **list, th_llist_t *node)
{
    th_llist_t *curr = *list;

    while (curr != NULL)
    {
        th_llist_t *next = curr->next;
        if (curr == node)
        {
            th_llist_delete_node_fast(list, curr);
            th_free(node);
            break;
        }
        curr = next;
    }
}


void th_llist_delete(th_llist_t **list, const void *data)
{
    th_llist_t *curr = *list;

    while (curr != NULL)
    {
        th_llist_t *next = curr->next;
        if (curr->data == data)
        {
            th_llist_delete_node_fast(list, curr);
            th_free(curr);
            break;
        }
        curr = next;
    }
}


th_llist_t * th_llist_get_nth(th_llist_t *list, const size_t n)
{
    th_llist_t *curr = list;
    size_t i;

    for (i = 0; curr != NULL && i < n; curr = curr->next, i++);

    return (i == n) ? curr : NULL;
}


size_t th_llist_length(const th_llist_t *list)
{
    return (list == NULL) ? 0 : list->num;
}


ssize_t th_llist_position(const th_llist_t *list, const th_llist_t *node)
{
    const th_llist_t *curr = list;
    ssize_t i = 0;

    while (curr != NULL)
    {
        if (curr == node)
            return i;
        else
            i++;

        curr = curr->next;
    }

    return -1;
}


void th_llist_foreach(th_llist_t *list, void (*func)(th_llist_t *node, void *userdata), void *data)
{
    th_llist_t *curr = list;

    while (curr != NULL)
    {
        func(curr, data);
        curr = curr->next;
    }
}


int th_llist_foreach_cond(th_llist_t *list, int (*func)(th_llist_t *node, void *userdata), void *data, th_llist_t **ret)
{
    th_llist_t *curr = list;

    while (curr != NULL)
    {
        int res = func(curr, data);
        if (res != 0)
        {
            *ret = curr;
            return res;
        }
        curr = curr->next;
    }

    return 0;
}


th_llist_t * th_llist_find(th_llist_t *list, const void *data)
{
    th_llist_t *curr = list;

    while (curr != NULL)
    {
        if (curr->data == data)
            return curr;
        curr = curr->next;
    }

    return NULL;
}


th_llist_t * th_llist_find_func(th_llist_t *list, const void *userdata, int (compare)(const void *, const void *))
{
    th_llist_t *curr = list;

    while (curr != NULL)
    {
        if (compare(curr->data, userdata) == 0)
            return curr;
        curr = curr->next;
    }

    return NULL;
}


/*
 * Ringbuffers
 */
th_ringbuf_t * th_ringbuf_new(const size_t size, void (*mdeallocator)(void *data))
{
    th_ringbuf_t *res = th_malloc0(sizeof(th_ringbuf_t));

    res->data = (char **) th_calloc(size, sizeof(char *));
    res->size = size;
    res->n = 0;
    res->deallocator = mdeallocator;

    return res;
}


BOOL th_ringbuf_grow(th_ringbuf_t *buf, const size_t n)
{
    buf->data = (char **) th_realloc(buf->data, (buf->size + n) * sizeof(char *));
    if (buf->data != NULL)
    {
        memset(buf->data + buf->size, 0, sizeof(char *) * n);
        buf->size += n;
        return TRUE;
    } else
        return FALSE;
}


void th_ringbuf_free(th_ringbuf_t *buf)
{
    int i;

    for (i = 0; i < buf->size; i++)
    {
        if (buf->data[i] != NULL)
            buf->deallocator(buf->data[i]);
    }

    th_free(buf->data);
    th_free(buf);
}


void th_ringbuf_add(th_ringbuf_t *buf, void *ptr)
{
    if (buf->n < buf->size)
        buf->n++;

    th_free(buf->data[0]);
    memmove(&(buf->data[0]), &(buf->data[1]), (buf->size - 1) * sizeof(void *));
    buf->data[buf->size - 1] = ptr;
}


/*
 * Growing buffer
 */
void th_growbuf_clear(th_growbuf_t *buf)
{
    // Simply reset the current "length"
    buf->len = 0;
}


void th_growbuf_init(th_growbuf_t *buf, const size_t mingrow)
{
    // Initialize the buffer structure
    memset(buf, 0, sizeof(th_growbuf_t));
    buf->mingrow = mingrow;
}


th_growbuf_t *th_growbuf_new(const size_t mingrow)
{
    th_growbuf_t *buf;

    if ((buf = th_malloc(sizeof(th_growbuf_t))) == NULL)
        return NULL;

    th_growbuf_init(buf, mingrow);
    buf->allocated = TRUE;

    return buf;
}


void th_growbuf_free(th_growbuf_t *buf)
{
    th_free(buf->data);

    if (buf->allocated)
        th_free(buf);
}


BOOL th_growbuf_grow(th_growbuf_t *buf, const size_t amount)
{
    if (buf == NULL)
        return FALSE;

    if (buf->data == NULL || buf->len + amount >= buf->size)
    {
        buf->size += amount + (buf->mingrow > 0 ? buf->mingrow : TH_BUFGROW);
        if ((buf->data = th_realloc(buf->data, buf->size)) == NULL)
            return FALSE;
    }
    return TRUE;
}


BOOL th_growbuf_puts(th_growbuf_t *buf, const char *str, BOOL eos)
{
    size_t slen;
    if (str == NULL)
        return FALSE;

    slen = strlen(str);
    if (!th_growbuf_grow(buf, slen + 1))
        return FALSE;

    memcpy(buf->data + buf->len, str, slen + 1);
    buf->len += eos ? (slen + 1) : slen;

    return TRUE;
}


BOOL th_growbuf_putch(th_growbuf_t *buf, const char ch)
{
    if (!th_growbuf_grow(buf, sizeof(char)))
        return FALSE;

    buf->data[buf->len++] = (uint8_t) ch;

    return TRUE;
}


BOOL th_growbuf_put_str(th_growbuf_t *buf, const void *str, const size_t len)
{
    if (str == NULL)
        return FALSE;

    if (!th_growbuf_grow(buf, len + 1))
        return FALSE;

    memcpy(buf->data + buf->len, str, len + 1);
    buf->len += len;

    return TRUE;
}


BOOL th_growbuf_put_u8(th_growbuf_t *buf, const uint8_t val)
{
    if (!th_growbuf_grow(buf, sizeof(uint8_t)))
        return FALSE;

    buf->data[buf->len++] = val;

    return TRUE;
}


BOOL th_growbuf_put_u16_be(th_growbuf_t *buf, const uint16_t val)
{
    if (!th_growbuf_grow(buf, sizeof(uint16_t)))
        return FALSE;

    buf->data[buf->len++] = (val >> 8) & 0xff;
    buf->data[buf->len++] = val & 0xff;

    return TRUE;
}


BOOL th_growbuf_put_u16_le(th_growbuf_t *buf, const uint16_t val)
{
    if (!th_growbuf_grow(buf, sizeof(uint16_t)))
        return FALSE;

    buf->data[buf->len++] = val & 0xff;
    buf->data[buf->len++] = (val >> 8) & 0xff;

    return TRUE;
}


BOOL th_growbuf_put_u32_be(th_growbuf_t *buf, const uint32_t val)
{
    if (!th_growbuf_grow(buf, sizeof(uint32_t)))
        return FALSE;

    buf->data[buf->len++] = (val >> 24) & 0xff;
    buf->data[buf->len++] = (val >> 16) & 0xff;
    buf->data[buf->len++] = (val >> 8) & 0xff;
    buf->data[buf->len++] = val & 0xff;

    return TRUE;
}


BOOL th_growbuf_put_u32_le(th_growbuf_t *buf, const uint32_t val)
{
    if (!th_growbuf_grow(buf, sizeof(uint32_t)))
        return FALSE;

    buf->data[buf->len++] = val & 0xff;
    buf->data[buf->len++] = (val >> 8) & 0xff;
    buf->data[buf->len++] = (val >> 16) & 0xff;
    buf->data[buf->len++] = (val >> 24) & 0xff;

    return TRUE;
}


/*
 * Simple legacy string growing buffer
 */
BOOL th_strbuf_grow(char **buf, size_t *bufsize, size_t *len, size_t grow)
{
    if (*buf == NULL)
        *bufsize = *len = 0;

    if (*buf == NULL || *len + grow >= *bufsize)
    {
        *bufsize += grow + TH_BUFGROW;
        *buf = th_realloc(*buf, *bufsize);
        if (*buf == NULL)
            return FALSE;
    }
    return TRUE;
}


BOOL th_strbuf_putch(char **buf, size_t *bufsize, size_t *len, const char ch)
{
    if (!th_strbuf_grow(buf, bufsize, len, 1))
        return FALSE;

    (*buf)[*len] = ch;
    (*len)++;

    return TRUE;
}


BOOL th_strbuf_putsn(char **buf, size_t *bufsize, size_t *len, const char *str, const size_t slen)
{
    if (str == NULL)
        return FALSE;

    if (!th_strbuf_grow(buf, bufsize, len, slen + 1))
        return FALSE;

    memcpy(*buf + *len, str, slen);
    (*len) += slen;
    *(buf + *len + slen) = 0;

    return TRUE;
}


BOOL th_strbuf_puts(char **buf, size_t *bufsize, size_t *len, const char *str)
{
    size_t slen;
    if (str == NULL)
        return FALSE;

    slen = strlen(str);
    if (!th_strbuf_grow(buf, bufsize, len, slen + 1))
        return FALSE;

    memcpy(*buf + *len, str, slen + 1);
    (*len) += slen;

    return TRUE;
}
