/*
 * Very simple configuration handling functions
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2004-2015 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#include "th_util.h"
#include "th_config.h"
#include "th_string.h"
#include <stdio.h>
#include <stdarg.h>

#define SET_MAX_BUF     (8192)


/* Free a given configuration (the values are not free'd)
 */
void th_cfg_free(th_cfgitem_t *cfg)
{
    th_cfgitem_t *node = cfg;

    while (node != NULL)
    {
        th_cfgitem_t *next = (th_cfgitem_t *) node->node.next;

        if (node->type == ITEM_SECTION)
            th_cfg_free((th_cfgitem_t *) node->v.data);

        th_free(node->name);
        th_free(node);
        node = next;
    }
}


/* Allocate and add new item to configuration
 */
static th_cfgitem_t *th_cfg_add(th_cfgitem_t **cfg, const char *name,
                             const int type, void *data)
{
    th_cfgitem_t *node;

    if (cfg == NULL)
        return NULL;

    // Allocate new item
    node = (th_cfgitem_t *) th_malloc0(sizeof(th_cfgitem_t));
    if (node == NULL)
        return NULL;

    // Set values
    node->type = type;
    node->v.data = data;
    node->name = th_strdup(name);

    // Insert into linked list
    th_llist_append_node((th_llist_t **) cfg, (th_llist_t *) node);

    return node;
}


/* Add integer type setting into give configuration
 */
int th_cfg_add_int(th_cfgitem_t **cfg, const char *name, int *itemData, int defValue)
{
    th_cfgitem_t *node;

    node = th_cfg_add(cfg, name, ITEM_INT, (void *) itemData);
    if (node == NULL)
        return -1;

    *itemData = defValue;

    return 0;
}


int th_cfg_add_hexvalue(th_cfgitem_t **cfg, const char *name,
                        int *itemData, int defValue)
{
    th_cfgitem_t *node;

    node = th_cfg_add(cfg, name, ITEM_HEX_TRIPLET, (void *) itemData);
    if (node == NULL)
        return -1;

    *itemData = defValue;

    return 0;
}


/* Add unsigned integer type setting into give configuration
 */
int th_cfg_add_uint(th_cfgitem_t **cfg, const char *name,
                    unsigned int *itemData, unsigned int defValue)
{
    th_cfgitem_t *node;

    node = th_cfg_add(cfg, name, ITEM_UINT, (void *) itemData);
    if (node == NULL)
        return -1;

    *itemData = defValue;

    return 0;
}


/* Add strint type setting into given configuration
 */
int th_cfg_add_string(th_cfgitem_t **cfg, const char *name,
                      char **itemData, char *defValue)
{
    th_cfgitem_t *node;

    node = th_cfg_add(cfg, name, ITEM_STRING, (void *) itemData);
    if (node == NULL)
        return -1;

    *itemData = th_strdup(defValue);

    return 0;
}


/* Add boolean type setting into given configuration
 */
int th_cfg_add_bool(th_cfgitem_t **cfg, const char *name,
                    BOOL *itemData, BOOL defValue)
{
    th_cfgitem_t *node;

    node = th_cfg_add(cfg, name, ITEM_BOOL, (void *) itemData);
    if (node == NULL)
        return -1;

    *itemData = defValue;

    return 0;
}


/* Add implicit comment
 */
int th_cfg_add_comment(th_cfgitem_t **cfg, const char *comment)
{
    th_cfgitem_t *node;

    node = th_cfg_add(cfg, comment, ITEM_COMMENT, NULL);
    if (node == NULL)
        return -1;

    return 0;
}


/* Add new section
 */
int th_cfg_add_section(th_cfgitem_t **cfg, const char *name, th_cfgitem_t *data)
{
    th_cfgitem_t *node;

    node = th_cfg_add(cfg, name, ITEM_SECTION, (void *) data);
    if (node == NULL)
        return -1;

    return 0;
}


int th_cfg_add_string_list(th_cfgitem_t **cfg, const char *name, th_llist_t **data)
{
    th_cfgitem_t *node;

    if (data == NULL)
        return -5;

    node = th_cfg_add(cfg, name, ITEM_STRING_LIST, (void *) data);
    if (node == NULL)
        return -1;

    return 0;
}


/* Read a given file into configuration structure and variables
 */
enum
{
    PM_EOF,
    PM_ERROR,
    PM_IDLE,
    PM_COMMENT,
    PM_NEXT,
    PM_KEYNAME,
    PM_KEYSET,
    PM_STRING,
    PM_NUMERIC,
    PM_BOOL,
    PM_SECTION,
    PM_ARRAY
};

#define VADDCH(ch) if (strPos < SET_MAX_BUF) { tmpStr[strPos++] = ch; }
#define VISEND(ch) (ch == '\r' || ch == '\n' || ch == ';' || th_isspace(c) || ch == '#')

static int th_cfg_read_sect(th_ioctx *ctx, th_cfgitem_t *cfg, int nesting)
{
    th_cfgitem_t *item = NULL;
    char *tmpStr = NULL;
    size_t strPos;
    int c, parseMode, prevMode, nextMode, tmpCh;
    BOOL isFound, isStart, isError, validError, fpSet;

    // Initialize values
    tmpCh = 0;
    strPos = 0;
    c = -1;
    fpSet = isFound = isStart = isError = validError = FALSE;
    nextMode = prevMode = parseMode = PM_IDLE;

    if ((tmpStr = th_malloc(SET_MAX_BUF + 1)) == NULL)
        goto out;

    // Parse the configuration
    while (parseMode != PM_EOF && parseMode != PM_ERROR)
    {
        if (c == -1)
        {
            // Get next character
            switch (c = thfgetc(ctx))
            {
            case EOF:
                if (parseMode != PM_IDLE)
                {
                    th_io_error(ctx, THERR_OUT_OF_DATA, "Unexpected end of file.\n");
                    parseMode = PM_ERROR;
                }
                else
                    parseMode = PM_EOF;
                break;

            case '\n':
                ctx->line++;
            }
        }

        switch (parseMode)
        {
        case PM_COMMENT:
            // Comment parsing mode
            if (c == '\n')
            {
                // End of line, end of comment
                parseMode = prevMode;
                prevMode = PM_COMMENT;
            }
            c = -1;
            break;

        case PM_IDLE:
            // Normal parsing mode
            if (c == '#')
            {
                prevMode = parseMode;
                parseMode = PM_COMMENT;
                c = -1;
            }
            else if (VISEND(c))
            {
                c = -1;
            }
            else if (c == '}')
            {
                if (nesting > 0)
                    // Check for validation errors
                    goto out;
                else
                {
                    th_io_error(ctx, THERR_INVALID_DATA,
                        "Invalid nesting sequence encountered.\n");
                    parseMode = PM_ERROR;
                }
            }
            else if (th_isalpha(c))
            {
                // Start of key name found
                prevMode = parseMode;
                parseMode = PM_KEYNAME;
                strPos = 0;
            }
            else
            {
                // Error! Invalid character found
                th_io_error(ctx, THERR_INVALID_DATA,
                    "Unexpected character '%c'.\n", c);
                parseMode = PM_ERROR;
            }
            break;

        case PM_KEYNAME:
            // Configuration KEY name parsing mode
            if (c == '#')
            {
                // Start of comment
                prevMode = parseMode;
                parseMode = PM_COMMENT;
                c = -1;
            }
            else if (th_iscrlf(c) || th_isspace(c) || c == '=')
            {
                // End of key name
                prevMode = parseMode;
                parseMode = PM_NEXT;
                nextMode = PM_KEYSET;
            }
            else if (th_isalnum(c) || c == '_' || c == '-')
            {
                // Add to key name string
                VADDCH(c)
                else
                {
                    // Error! Key name string too long!
                    th_io_error(ctx, THERR_INVALID_DATA, "Config key name too long!");
                    parseMode = PM_ERROR;
                }
                c = -1;
            }
            else
            {
                // Error! Invalid character found
                tmpStr[strPos] = 0;
                th_io_error(ctx, THERR_INVALID_DATA,
                    "Unexpected character '%c' in key name '%s'.\n",
                    c, tmpStr);
                parseMode = PM_ERROR;
            }
            break;

        case PM_KEYSET:
            if (c == '=')
            {
                // Find key from configuration
                tmpStr[strPos] = 0;
                isFound = FALSE;
                item = cfg;
                while (item != NULL && !isFound)
                {
                    if (item->name != NULL && strcmp(item->name, tmpStr) == 0)
                        isFound = TRUE;
                    else
                        item = (th_cfgitem_t *) item->node.next;
                }

                // Check if key was found
                if (isFound)
                {
                    // Okay, set next mode
                    switch (item->type)
                    {
                    case ITEM_HEX_TRIPLET:
                    case ITEM_STRING:
                        nextMode = PM_STRING;
                        break;

                    case ITEM_STRING_LIST:
                        nextMode = PM_ARRAY;
                        break;

                    case ITEM_INT:
                    case ITEM_UINT:
                    case ITEM_FLOAT:
                        nextMode = PM_NUMERIC;
                        break;

                    case ITEM_BOOL:
                        nextMode = PM_BOOL;
                        break;

                    case ITEM_SECTION:
                        nextMode = PM_SECTION;
                        break;
                    }

                    prevMode = parseMode;
                    parseMode = PM_NEXT;
                    isStart = TRUE;
                    fpSet = FALSE;
                    strPos = 0;
                }
                else
                {
                    // Error! No configuration key by this name found
                    th_io_error(ctx, THERR_INVALID_DATA,
                        "No such configuration setting ('%s')\n",
                        tmpStr);
                    parseMode = PM_ERROR;
                }

                c = -1;
            }
            else
            {
                // Error! '=' expected!
                th_io_error(ctx, THERR_INVALID_DATA,
                    "Unexpected character '%c', assignation '=' was expected.\n",
                    c);
                parseMode = PM_ERROR;
            }
            break;

        case PM_NEXT:
            // Search next item parsing mode
            if (c == '#')
            {
                // Start of comment
                prevMode = parseMode;
                parseMode = PM_COMMENT;
            }
            else if (th_isspace(c) || th_iscrlf(c))
            {
                // Ignore whitespaces and linechanges
                c = -1;
            }
            else
            {
                // Next item found
                prevMode = parseMode;
                parseMode = nextMode;
            }
            break;

        case PM_ARRAY:
            if (isStart)
            {
                switch (item->type)
                {
                case ITEM_STRING_LIST:
                    prevMode = parseMode;
                    parseMode = PM_STRING;
                    break;
                }
            }
            else if (c == ',')
            {
                switch (item->type)
                {
                case ITEM_STRING_LIST:
                    c = -1;
                    isStart = TRUE;
                    prevMode = parseMode;
                    parseMode = PM_NEXT;
                    nextMode = PM_STRING;
                    break;
                }
            }
            else
            {
                prevMode = parseMode;
                parseMode = PM_IDLE;
            }
            break;

        case PM_SECTION:
            // Section parsing mode
            if (c != '{')
            {
                // Error! Section start '{' expected!
                th_io_error(ctx, THERR_INVALID_DATA,
                    "Unexpected character '%c', section start '{' was expected.\n",
                    c);
                parseMode = PM_ERROR;
            }
            else
            {
                int res = th_cfg_read_sect(ctx, item->v.section, nesting + 1);
                c = -1;
                if (res > 0)
                    validError = TRUE;
                else if (res < 0)
                    parseMode = PM_ERROR;
                else
                {
                    prevMode = parseMode;
                    parseMode = PM_IDLE;
                }
            }
            break;

        case PM_STRING:
            // String parsing mode
            if (isStart)
            {
                // Start of string, get delimiter
                tmpCh = c;
                isStart = FALSE;
                strPos = 0;
            }
            else if (c == tmpCh)
            {
                // End of string, set the value
                tmpStr[strPos] = 0;

                switch (item->type)
                {
                case ITEM_HEX_TRIPLET:
                    *(item->v.val_int) = th_get_hex_triplet(tmpStr);
                    prevMode = parseMode;
                    parseMode = PM_IDLE;
                    break;
                case ITEM_STRING:
                    th_pstr_cpy(item->v.val_str, tmpStr);
                    prevMode = parseMode;
                    parseMode = PM_IDLE;
                    break;
                case ITEM_STRING_LIST:
                    th_llist_append(item->v.list, th_strdup(tmpStr));
                    prevMode = parseMode;
                    parseMode = PM_NEXT;
                    nextMode = PM_ARRAY;
                    break;
                }

            }
            else
            {
                // Add character to string
                VADDCH(c)
                else
                {
                    // Error! String too long!
                    th_io_error(ctx, THERR_INVALID_DATA,
                        "String too long! Maximum is %d characters.",
                        SET_MAX_BUF);
                    parseMode = PM_ERROR;
                }
            }

            c = -1;
            break;

        case PM_NUMERIC:
            // Integer parsing mode
            if (isStart && item->type == ITEM_UINT && c == '-')
            {
                // Error! Negative values not allowed for unsigned ints
                th_io_error(ctx, THERR_INVALID_DATA,
                    "Negative value specified for %s, unsigned value expected.",
                    item->name);
                parseMode = PM_ERROR;
            }
            else if (isStart && (c == '-' || c == '+'))
            {
                VADDCH(c)
                else
                isError = TRUE;
            }
            else if (isStart && item->type == ITEM_FLOAT && c == '.')
            {
                fpSet = TRUE;
                VADDCH('0')
                else
                isError = TRUE;

                VADDCH(c)
                else
                isError = TRUE;
            }
            else if (item->type == ITEM_FLOAT && c == '.' && !fpSet)
            {
                fpSet = TRUE;
                VADDCH(c)
                else
                isError = TRUE;
            }
            else if (th_isdigit(c))
            {
                VADDCH(c)
                else
                isError = TRUE;
            }
            else if (VISEND(c))
            {
                // End of integer parsing mode
                tmpStr[strPos] = 0;
                switch (item->type)
                {
                case ITEM_INT:
                    *(item->v.val_int) = atoi(tmpStr);
                    break;

                case ITEM_UINT:
                    *(item->v.val_uint) = atol(tmpStr);
                    break;

                case ITEM_FLOAT:
                    *(item->v.val_float) = atof(tmpStr);
                    break;
                }

                prevMode = parseMode;
                parseMode = PM_IDLE;
            }
            else
            {
                // Error! Unexpected character.
                th_io_error(ctx, THERR_INVALID_DATA,
                    "Unexpected character '%c' for numeric setting '%s'.",
                    c, item->name);
                parseMode = PM_ERROR;
            }

            if (isError)
            {
                // Error! String too long!
                th_io_error(ctx, THERR_INVALID_DATA,
                    "String too long! Maximum is %d characters.",
                    SET_MAX_BUF);
                parseMode = PM_ERROR;
            }

            isStart = FALSE;
            c = -1;
            break;

        case PM_BOOL:
            // Boolean parsing mode
            if (isStart)
            {
                isStart = FALSE;
                strPos = 0;
            }

            if (th_isalnum(c))
            {
                VADDCH(c)
                else
                isError = TRUE;
            }
            else
            if (VISEND(c))
            {
                BOOL tmpBool;
                tmpStr[strPos] = 0;
                isError = !th_get_boolean(tmpStr, &tmpBool);
                if (!isError)
                {
                    *(item->v.val_bool) = tmpBool;
                    prevMode = parseMode;
                    parseMode = PM_IDLE;
                }
            }

            if (isError)
            {
                th_io_error(ctx, THERR_INVALID_DATA,
                    "Invalid boolean value for '%s'.\n",
                    item->name);
                parseMode = PM_ERROR;
            }
            c = -1;
            break;
        }
    }

out:
    th_free(tmpStr);

    // Check for validation errors
    if (validError)
        return 1;

    // Return result
    if (parseMode == PM_ERROR)
        return -2;
    else
        return 0;
}


int th_cfg_read(th_ioctx *ctx, th_cfgitem_t *cfg)
{
    if (ctx == NULL || cfg == NULL)
        return -1;

    return th_cfg_read_sect(ctx, cfg, 0);
}


/* Write a configuration into file
 */
static void th_print_indent(th_ioctx *ctx, int nesting)
{
    int i;
    for (i = 0; i < nesting * 2; i++)
        thfputc(' ', ctx);
}


static int th_cfg_write_sect(th_ioctx *ctx, const th_cfgitem_t *item, int nesting)
{
    while (item != NULL)
    {
        if (item->type == ITEM_COMMENT)
        {
            th_print_indent(ctx, nesting);
            if (thfprintf(ctx, "# %s\n",
                (item->name != NULL) ? item->name : "") < 0)
                return -1;
        }
        else if (item->name != NULL)
        {
            th_print_indent(ctx, nesting);

            switch (item->type)
            {
            case ITEM_STRING:
                if (*(item->v.val_str) == NULL)
                {
                    if (thfprintf(ctx, "#%s = \"\"\n",
                        item->name) < 0)
                        return -3;
                }
                else
                {
                    if (thfprintf(ctx, "%s = \"%s\"\n",
                        item->name, *(item->v.val_str)) < 0)
                        return -3;
                }
                break;

            case ITEM_STRING_LIST:
                if (*(item->v.list) == NULL)
                {
                    if (thfprintf(ctx,
                        "#%s = \"\", \"\"\n", item->name) < 0)
                        return -3;
                }
                else
                {
                    th_llist_t *node = *(item->v.list);
                    size_t n = th_llist_length(node);
                    if (thfprintf(ctx, "%s = ", item->name) < 0)
                        return -3;

                    for (; node != NULL; node = node->next)
                    {
                        if (node->data != NULL)
                            thfprintf(ctx, "\"%s\"", (char *) node->data);

                        if (--n > 0)
                        {
                            thfprintf(ctx, ",\n");
                            th_print_indent(ctx, nesting);
                        }
                    }

                    if (thfprintf(ctx, "\n") < 0)
                        return -3;
                }
                break;

            case ITEM_INT:
                if (thfprintf(ctx, "%s = %i\n",
                    item->name, *(item->v.val_int)) < 0)
                    return -4;
                break;

            case ITEM_UINT:
                if (thfprintf(ctx, "%s = %d\n",
                    item->name, *(item->v.val_uint)) < 0)
                    return -5;
                break;

            case ITEM_FLOAT:
                if (thfprintf(ctx, "%s = %1.5f\n",
                    item->name, *(item->v.val_float)) < 0)
                    return -5;
                break;

            case ITEM_BOOL:
                if (thfprintf(ctx, "%s = %s\n", item->name,
                    *(item->v.val_bool) ? "yes" : "no") < 0)
                    return -6;
                break;

            case ITEM_SECTION:
                {
                    int res;
                    if (thfprintf(ctx, "%s = {\n", item->name) < 0)
                        return -7;
                    res = th_cfg_write_sect(ctx, item->v.section, nesting + 1);
                    if (res != 0)
                        return res;
                    if (thfprintf(ctx, "}\n\n") < 0)
                        return -8;
                }
                break;

            case ITEM_HEX_TRIPLET:
                if (thfprintf(ctx, "%s = \"%06x\"\n",
                    item->name, *(item->v.val_int)) < 0)
                    return -6;
                break;
            }
        }
        item = (th_cfgitem_t *) item->node.next;
    }

    return 0;
}


int th_cfg_write(th_ioctx *ctx, const th_cfgitem_t *cfg)
{
    if (ctx == NULL || cfg == NULL)
        return -1;

    thfprintf(ctx, "# Configuration written by %s %s\n\n",
            th_prog_desc, th_prog_version);

    return th_cfg_write_sect(ctx, cfg, 0);
}


/* Find a configuration item based on section, name, type.
 * Name MUST be defined. Section can be NULL and type -1,
 * first matching item will be returned.
 */
static th_cfgitem_t *th_cfg_find_do(th_cfgitem_t *item, BOOL *sect, const char *section, const char *name, const int type)
{
    while (item != NULL)
    {
        BOOL match = TRUE;

        if (item->type == ITEM_SECTION)
        {
            // Check section name if set
            if (section != NULL && strcmp(section, item->name) == 0)
                *sect = TRUE;

            // Recurse to sub-section
            th_cfgitem_t *tmp = th_cfg_find_do(item->v.section, sect, section, name, type);
            if (tmp != NULL)
                return tmp;
        }
        else
        // Has type check been set, and does it match?
        if (type != -1 && item->type != type)
            match = FALSE;
        else
        // Check name (not section name, tho)
        if (strcmp(name, item->name) != 0)
            match = FALSE;

        // Do we have a match?
        if (*sect && match)
            return item;
    }

    return NULL;
}


th_cfgitem_t *th_cfg_find(th_cfgitem_t *cfg, const char *section, const char *name, const int type)
{
    BOOL sect = FALSE;

    if (section == NULL)
        sect = TRUE;

    return th_cfg_find_do(cfg, &sect, section, name, type);
}
