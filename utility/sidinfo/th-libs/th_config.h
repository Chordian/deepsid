/*
 * Very simple configuration file handling
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2004-2015 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#ifndef TH_CONFIG_H
#define TH_CONFIG_H

#include "th_util.h"
#include "th_datastruct.h"
#include "th_ioctx.h"

#ifdef __cplusplus
extern "C" {
#endif

/* Definitions
 */
enum ITEM_TYPE
{
    ITEM_SECTION = 1,
    ITEM_COMMENT,
    ITEM_STRING,
    ITEM_INT,
    ITEM_UINT,
    ITEM_BOOL,
    ITEM_FLOAT,
    ITEM_HEX_TRIPLET,

    ITEM_STRING_LIST,
    ITEM_HEX_TRIPLET_LIST
};


typedef struct _th_cfgitem_t
{
    th_llist_t node;

    int  type;
    char *name;
    union {
        int *val_int;
        unsigned int *val_uint;
        char **val_str;
        BOOL *val_bool;
        float *val_float;

        void *data;
        th_llist_t **list;
        struct _th_cfgitem_t *section;
    } v;
} th_cfgitem_t;


/* Functions
 */
int     th_cfg_read(th_ioctx *, th_cfgitem_t *);
void    th_cfg_free(th_cfgitem_t *);
int     th_cfg_write(th_ioctx *, const th_cfgitem_t *);

int     th_cfg_add_section(th_cfgitem_t **cfg, const char *name, th_cfgitem_t *data);
int     th_cfg_add_comment(th_cfgitem_t **cfg, const char *comment);

int     th_cfg_add_int(th_cfgitem_t **cfg, const char *name, int *data, int defValue);
int     th_cfg_add_uint(th_cfgitem_t **cfg, const char *name, unsigned int *data, unsigned int defValue);
int     th_cfg_add_float(th_cfgitem_t **cfg, const char *name, float *data, float defValue);
int     th_cfg_add_string(th_cfgitem_t **cfg, const char *name, char **data, char *defValue);
int     th_cfg_add_bool(th_cfgitem_t **cfg, const char *name, BOOL *data, BOOL defValue);
int     th_cfg_add_float(th_cfgitem_t **cfg, const char *name, float *data, float defValue);
int     th_cfg_add_hexvalue(th_cfgitem_t **cfg, const char *name, int *data, int defValue);
int     th_cfg_add_string_list(th_cfgitem_t **cfg, const char *name, th_llist_t **list);

th_cfgitem_t *th_cfg_find(th_cfgitem_t *cfg, const char *section, const char *name, const int type);

#ifdef __cplusplus
}
#endif
#endif // TH_CONFIG_H
