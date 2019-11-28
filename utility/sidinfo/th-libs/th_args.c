/*
 * Simple commandline argument processing
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
/// @file
/// @brief Simple commandline argument processing functions
#ifndef TH_EXTERNAL
#include "th_util.h"
#include "th_args.h"
#include "th_string.h"
#endif


/**
 * Parse and optionally handle the given long or short option argument.
 * @param currArg current argument string
 * @param argIndex pointer to index of current argument in argv[]
 * @param argc number of arguments
 * @param argv argument string array
 * @param opts options list array
 * @param nopts number of elements in options list array
 * @param handle_option function pointer to callback that handles option arguments
 * @param doProcess if TRUE, actually handle the argument, aka call the handle_option() function. if FALSE, only validity of options are checked.
 * @param isLong TRUE if the option is a --long-format one
 */
static BOOL th_args_process_opt(
    char *currArg, int *argIndex,
    int argc, char *argv[],
    const th_optarg opts[], int nopts,
    BOOL (*handle_option)(int id, char *, char *),
    BOOL doProcess, BOOL isLong)
{
    const th_optarg *opt = NULL;
    char *optArg = NULL;
    int optIndex;

    for (optIndex = 0; optIndex < nopts; optIndex++)
    {
        const th_optarg *node = &opts[optIndex];
        if (isLong && node->o_long != NULL)
        {
            if (strcmp(currArg, node->o_long) == 0)
            {
                opt = node;
                optArg = NULL;
                break;
            }

            size_t len = strlen(node->o_long);
            if (strncmp(currArg, node->o_long, len) == 0 &&
                currArg[len] == '=')
            {
                opt = node;
                optArg = (&currArg[len+1] != 0) ? &currArg[len+1] : NULL;
                break;
            }
        }
        else
        if (!isLong && node->o_short != 0)
        {
            if (*currArg == node->o_short)
            {
                opt = node;
                optArg = (currArg[1] != 0) ? &currArg[1] : NULL;
            }
        }
    }

    if (opt != NULL)
    {
        // Check for the possible option argument
        if ((opt->flags & OPT_ARGMASK) == OPT_ARGREQ && optArg == NULL)
        {
            if (*argIndex < argc)
            {
                (*argIndex)++;
                optArg = argv[*argIndex];
            }

            if (optArg == NULL)
            {
                THERR("Option '%s%s' requires an argument.\n",
                    isLong ? "--" : "-",
                    currArg);
                return FALSE;
            }
        }

        // Option was given succesfully, try to process it
        if (doProcess && !handle_option(opt->id, optArg, currArg))
            return FALSE;
    }
    else
    {
        THERR("Unknown %s option '%s%s'\n",
            isLong ? "long" : "short",
            isLong ? "--" : "-",
            currArg);

        return FALSE;
    }

    return TRUE;
}


/**
 * Process given array of commandline arguments, handling short
 * and long options by calling the respective callback functions.
 *
 * @param argc number of arguments
 * @param argv argument list
 * @param opts supported option list array
 * @param nopts number of elements in the option list array
 * @param handle_option callback function
 * @param handle_other callback function
 * @param flags processing flags
 * @return return TRUE if all is well
 */
BOOL th_args_process(int argc, char *argv[],
     const th_optarg *opts, const int nopts,
     BOOL(*handle_option)(int id, char *, char *),
     BOOL(*handle_other)(char *), const int flags)
{
    int argIndex, handleFlags = flags & OPTH_ONLY_MASK;
    BOOL optionsOK = TRUE, endOfOptions = FALSE;

    for (argIndex = 1; argIndex < argc; argIndex++)
    {
        char *str = argv[argIndex];
        if (*str == '-' && !endOfOptions)
        {
            // Should we process options?
            BOOL doProcess = (handleFlags & OPTH_ONLY_OPTS) || handleFlags == 0;
            BOOL isLong;

            str++;
            if (*str == '-')
            {
                // Check for "--", which ends the options-list
                str++;
                if (*str == 0)
                {
                    endOfOptions = TRUE;
                    continue;
                }

                // We have a long option
                isLong = TRUE;
            }
            else
                isLong = FALSE;

            if (!th_args_process_opt(str, &argIndex, argc, argv,
                opts, nopts, handle_option, doProcess, isLong))
                optionsOK = FALSE;
        }
        else
        if (handleFlags == OPTH_ONLY_OTHER || handleFlags == 0)
        {
            // Was not option argument
            if (handle_other == NULL ||
                (handle_other != NULL && !handle_other(str)))
            {
                THERR("Invalid argument '%s'\n", str);
                optionsOK = FALSE;
            }
        }

        // Check if we bail out on invalid argument
        if (!optionsOK && (flags & OPTH_BAILOUT))
            return FALSE;
    }

    return optionsOK;
}


/**
 * Print help for commandline arguments/options
 * @param fh stdio file handle to output to
 * @param opts options list array
 * @param nopts number of elements in options list array
 * @param flags flags (currently unused)
 */
void th_args_help(FILE *fh,
    const th_optarg *opts, const int nopts,
    const int flags)
{
    int index;
    (void) flags;

    // Print out option list
    for (index = 0; index < nopts; index++)
    {
        const th_optarg *opt = &opts[index];
        char tmpStr[128];

        // Print short option
        if (opt->o_short != 0)
        {
            snprintf(tmpStr, sizeof(tmpStr),
                "-%c,", opt->o_short);
        }
        else
            tmpStr[0] = 0;

        fprintf(fh, " %-5s", tmpStr);

        // Print long option
        if (opt->o_long != NULL)
        {
            snprintf(tmpStr, sizeof(tmpStr), "--%s%s",
                opt->o_long,
                (opt->flags & OPT_ARGREQ) ? "=ARG" : "");
        }
        else
            tmpStr[0] = 0;

        fprintf(fh, "%-20s", tmpStr);

        th_print_wrap(fh, opt->desc, 26, 26, th_term_width() - 2);
    }
}
