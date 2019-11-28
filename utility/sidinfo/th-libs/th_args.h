/*
 * Simple commandline argument processing functions
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
/// @file
/// @brief Simple commandline argument processing functions
#ifndef TH_ARGS_H
#define TH_ARGS_H

#include "th_util.h"
#include <stdio.h>

#ifdef __cplusplus
extern "C" {
#endif

/** @def Option argument flags
 */
#define OPT_NONE             (0)    ///< Simple option with no arguments
#define OPT_ARGREQ           (1)    ///< Option requires an argument
#define OPT_ARGMASK          (1)    ///< Mask for option argument flags

/** @def Option processing flags
 */
#define OPTH_BAILOUT         0x0001 ///< Bail out on errors
#define OPTH_ONLY_OPTS       0x0010 ///< Handle only options
#define OPTH_ONLY_OTHER      0x0020 ///< Handle only "non-options"
#define OPTH_ONLY_MASK       0x00f0 ///< Mask


/** Option argument structure
 */
typedef struct
{
    int id;           ///< Option ID (should be unique for each option)
    char o_short;     ///< Short option name (one character)
    char *o_long;     ///< Long option name
    char *desc;       ///< Option description
    int flags;        ///< Flags (see OPT_*)
} th_optarg;


BOOL th_args_process(int argc, char *argv[],
     const th_optarg *opts, const int nopts,
     BOOL (*handle_option)(int id, char *, char *),
     BOOL (*handle_other)(char *), const int flags);

void th_args_help(FILE *fh, const th_optarg *opts,
     const int nopts, const int flags);

#ifdef __cplusplus
}
#endif
#endif // TH_ARGS_H
