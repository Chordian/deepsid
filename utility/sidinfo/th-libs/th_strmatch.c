/*
 * String glob match implementation
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2002-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */

BOOL TH_STRMATCH_FUNC (const char *haystack, const char *pattern)
{
    BOOL matched = TRUE, any = FALSE, end = FALSE;
    const char *tmp = NULL;

    // Check given pattern and string
    if (haystack == NULL || pattern == NULL)
        return FALSE;

    // Start comparision
    while (matched && !end)
    switch (*pattern)
    {
    case '?':
        // Any single character matches
        if (*haystack)
        {
            pattern++;
            haystack++;
        }
        else
            matched = FALSE;
        break;

    case '*':
        pattern++;
        if (!*pattern || *pattern == '?')
            end = TRUE;
        any = TRUE;
        tmp = pattern;
        break;

    case 0:
        if (any)
        {
            if (*haystack)
                haystack++;
            else
                end = TRUE;
        }
        else
        if (*haystack)
        {
            if (tmp)
            {
                any = TRUE;
                pattern = tmp;
            }
            else
                matched = FALSE;
        }
        else
            end = TRUE;
        break;

    default:
        if (any)
        {
            if (TH_STRMATCH_COLLATE(*pattern) == TH_STRMATCH_COLLATE(*haystack))
            {
                any = FALSE;
            }
            else
            if (*haystack)
                haystack++;
            else
                matched = FALSE;
        }
        else
        {
            if (TH_STRMATCH_COLLATE(*pattern) == TH_STRMATCH_COLLATE(*haystack))
            {
                if (*pattern)
                    pattern++;
                if (*haystack)
                    haystack++;
            }
            else
            if (tmp)
            {
                any = TRUE;
                pattern = tmp;
            }
            else
                matched = FALSE;
        }

        if (!*haystack && !*pattern)
            end = TRUE;

        break;
    }

    return matched;
}


#undef TH_STRMATCH_FUNC
#undef TH_STRMATCH_COLLATE

