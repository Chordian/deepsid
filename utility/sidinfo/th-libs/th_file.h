/*
 * File, directory etc helper functions
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2016-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
/// @file
/// @brief File, path, etc. related helper functions
#ifndef TH_FILE_H
#define TH_FILE_H

#include "th_util.h"

#ifdef __cplusplus
extern "C" {
#endif

// Platform specific defines
#if defined(TH_PLAT_WINDOWS)
#    define TH_DIR_SEPARATOR '\\'
#else
#    define TH_DIR_SEPARATOR '/'
#endif


// Flags for th_stat_path()
enum
{
    TH_IS_DIR      = 0x1000,
    TH_IS_SYMLINK  = 0x2000,

    TH_IS_WRITABLE = 0x0002,
    TH_IS_READABLE = 0x0004,
};


typedef struct
{
    int flags;
    uint64_t size;
    uint64_t atime, mtime, ctime;
} th_stat_data;


char *  th_get_home_dir();
char *  th_get_config_dir(const char *name);

BOOL    th_stat_path(const char *path, th_stat_data *data);
BOOL    th_mkdir_path(const char *cpath, int mode);


#ifdef __cplusplus
}
#endif
#endif // TH_FILE_H
