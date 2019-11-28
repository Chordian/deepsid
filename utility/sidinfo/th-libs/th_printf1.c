/*
 * A printf() implementation helper function template
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2016-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */


int TH_PFUNC_NAME (char *buf, const int len, int *pos,
    TH_PFUNC_TYPE_S pval, const int f_radix, const BOOL f_upcase,
    const BOOL f_unsig, BOOL *f_neg)
#ifdef TH_PFUNC_HEADER
;
#else
{
    if (f_radix > 16)
        return EOF;

    // Check for negative value
    if (!f_unsig && pval < 0)
    {
        *f_neg = TRUE;
        pval = -pval;
    }
    else
        *f_neg = FALSE;

    // Render the value to a string in buf (reversed)
    TH_PFUNC_TYPE_U val = pval;

    // Special case for value of 0
    if (val == 0)
        return 0;

    *pos = 0;
    do
    {
        TH_PFUNC_TYPE_U digit = val % f_radix;
        if (digit < 10)
            buf[*pos] = '0' + digit;
        else
            buf[*pos] = (f_upcase ? 'A' : 'a') + digit - 10;
        val /= f_radix;
        (*pos)++;
    }
    while (val > 0 && *pos < len - 1);
    buf[*pos] = 0;

    return (val > 0) ? EOF : 1;
}
#endif


#undef TH_PFUNC_NAME
#undef TH_PFUNC_SIGNED
#undef TH_PFUNC_TYPE_S
#undef TH_PFUNC_TYPE_U
#undef TH_PFUNC_HEADER
