#include "th_types.h"
#include "th_args.h"
#include "th_util.h"
#include "th_string.h"
#include "th_crypto.h"


#define SET_BUF_SIZE           128
#define SET_BUF_SIZE_2         ((SET_BUF_SIZE) + 32)
#define SET_MAX_TESTS          64
#define SET_SENTINEL_BYTE      0x0e5


enum
{
    TST_SUPERFLUOUS = 0x0001,
    TST_CORNERCASE  = 0x0002,
    TST_OVERFLOW    = 0x0004,
    TST_ALL         = 0xffff,
};


typedef struct
{
    char *header, *res;
    BOOL shown;
} test_ctx;


// Globals
int tests_failed, tests_passed, tests_total, sets_total, sets_nenabled;
int sets_enabled[SET_MAX_TESTS];

char buf1[SET_BUF_SIZE_2], buf2[SET_BUF_SIZE_2];
int optFlags = TST_ALL;


// Define option arguments
static const th_optarg arg_opts[] =
{
    { 0, '?', "help",       "Show this help", OPT_NONE },
    { 1, 'v', "verbose",    "Be more verbose", OPT_NONE },
    { 2, 's', "sets",       "Perform test sets -s <set>[,<set2>..]", OPT_ARGREQ },
    { 3, 't', "tests",      "Perform only tests (see below)", OPT_ARGREQ },
};

static const int arg_nopts = sizeof(arg_opts) / sizeof(arg_opts[0]);


BOOL tprintv(const int level, const char *fmt, va_list ap)
{
    if (level <= th_verbosity)
    {
        vfprintf(stdout, fmt, ap);
        return TRUE;
    }
    else
        return FALSE;
}


BOOL tprint(const int level, const char *fmt, ...)
{
    BOOL retv;
    va_list ap;
    va_start(ap, fmt);
    retv = tprintv(level, fmt, ap);
    va_end(ap);
    return retv;
}


void arg_show_help(void)
{
    th_print_banner(stdout, th_prog_name, "[options]");
    th_args_help(stdout, arg_opts, arg_nopts, 0);
}


BOOL arg_handle_opt(const int optN, char *optArg, char *currArg)
{
    switch (optN)
    {
    case 0:
        arg_show_help();
        exit(0);
        break;

    case 1:
        th_verbosity++;
        break;

    case 2:
        {
            BOOL ret = TRUE;
            char *pos, *pstr, *next;
            pos = pstr = th_strdup(optArg);
            memset(sets_enabled, 0, sizeof(sets_enabled));

            do {
                next = strchr(pos, ',');
                if (next != NULL)
                    *next = 0;

                char *tmp = th_strdup_trim(pos, TH_TRIM_BOTH);
                if (tmp != NULL)
                {
                    int val = atoi(tmp);
                    if (val > 0 && val <= SET_MAX_TESTS)
                        sets_enabled[val - 1] = 1;
                    else
                    {
                        THERR("Invalid test number #%d, out of range [%d .. %d]\n", val, 1, SET_MAX_TESTS);
                        ret = FALSE;
                    }
                    th_free(tmp);
                }

                if (next != NULL)
                    pos = next + 1;
            } while (next != NULL);
            th_free(pstr);
            return ret;
        }
        break;

    case 3:
        optFlags = atoi(optArg);
        break;

    default:
        THERR("Unknown option '%s'.\n", currArg);
        return FALSE;
    }

    return TRUE;
}


void test_init(test_ctx *ctx)
{
    memset(ctx, 0, sizeof(test_ctx));
}


void test_end(test_ctx *ctx)
{
    th_free_r(&ctx->header);
    th_free_r(&ctx->res);
}


void test_start_v(test_ctx *ctx, const char *fmt, va_list ap)
{
    tests_total++;
    test_end(ctx);
    ctx->header = th_strdup_vprintf(fmt, ap);
}


void test_start(test_ctx *ctx, const char *fmt, ...)
{
    va_list ap;
    va_start(ap, fmt);
    test_start_v(ctx, fmt, ap);
    va_end(ap);
}


void test_result_msg_v(test_ctx *ctx, BOOL check, const char *fmt, va_list ap)
{
    if (check)
    {
        if (!ctx->shown && tprint(2, "%s: OK\n", ctx->header))
            ctx->shown = TRUE;

        tests_passed++;
    }
    else
    {
        if (!ctx->shown && tprint(0, "%s: FAIL\n", ctx->header))
            ctx->shown = TRUE;

        if (fmt != NULL)
        {
            tprint(0, "  - ");
            tprintv(0, fmt, ap);
            tprint(0, "\n");
        }
        if (ctx->res != NULL)
            tprint(0, "%s\n", ctx->res);
        tests_failed++;
    }
}


BOOL test_result_msg(test_ctx *ctx, BOOL check, const char *fmt, ...)
{
    va_list ap;
    va_start(ap, fmt);
    test_result_msg_v(ctx, check, fmt, ap);
    va_end(ap);
    return check;
}


BOOL test_result(test_ctx *ctx, BOOL check)
{
    test_result_msg_v(ctx, check, NULL, NULL);
    return check;
}



void test_snprintf_do(size_t len, const char *msg, const char *fmt, va_list ap)
{
    int ret1, ret2;
    va_list tmp;
    test_ctx ctx;

    // Test basic *printf() functionality
    test_init(&ctx);
    test_start(&ctx, "th_vsnprintf(%" PRIu_SIZE_T ", \"%s\", %s)", len, fmt, msg);

    memset(buf1, SET_SENTINEL_BYTE, SET_BUF_SIZE_2); buf1[SET_BUF_SIZE_2-1] = 0;
    memset(buf2, SET_SENTINEL_BYTE, SET_BUF_SIZE_2); buf2[SET_BUF_SIZE_2-1] = 0;

    va_copy(tmp, ap); ret1 = th_vsnprintf(buf1, len, fmt, tmp);
    va_copy(tmp, ap); ret2 = vsnprintf(buf2, len, fmt, tmp);

    test_result_msg(&ctx, ret1 == ret2, "retval mismatch %d [th] != %d [libc]", ret1, ret2);
    test_result_msg(&ctx, strcmp(buf1, buf2) == 0, "result mismatch '%s' [th] != '%s' [libc]", buf1, buf2);

    if (optFlags & TST_OVERFLOW)
    {
    test_result_msg(&ctx, (unsigned char) buf1[len] == SET_SENTINEL_BYTE, "buffer #1 overflow, sentinel 0x%02x", buf1[len]);
    test_result_msg(&ctx, (unsigned char) buf2[len] == SET_SENTINEL_BYTE, "buffer #2 overflow, sentinel 0x%02x", buf2[len]);
    }

    test_end(&ctx);
}


void test_snprintf(const char *msg, const char *fmt, ...)
{
    test_ctx ctx;
    va_list ap, tmp;
    va_start(ap, fmt);

    if (optFlags & TST_CORNERCASE)
    {
        va_copy(tmp, ap); test_snprintf_do(0, msg, fmt, tmp);
        va_copy(tmp, ap); test_snprintf_do(1, msg, fmt, tmp);
        va_copy(tmp, ap); test_snprintf_do(2, msg, fmt, tmp);
        va_copy(tmp, ap); test_snprintf_do(16, msg, fmt, tmp);
    }

    va_copy(tmp, ap); test_snprintf_do(SET_BUF_SIZE, msg, fmt, tmp);

    // Test th_strdup_vprintf()
    if (optFlags & TST_SUPERFLUOUS)
    {
        test_init(&ctx);
        test_start(&ctx, "th_strdup_vprintf('%s')", fmt);
        va_copy(tmp, ap);
        char *str = th_strdup_vprintf(fmt, tmp);
        test_result_msg(&ctx, str != NULL, "result NULL");
        th_free(str);
        test_end(&ctx);
    }

    va_end(ap);
    tprint(2,
        "-----------------------------------------------------\n");
}


BOOL test_set_start(const char *str)
{
    if (sets_enabled[sets_total++])
    {
        sets_nenabled++;
        tprint(1,
            "======================================================\n"
            " Set #%d : %s tests\n"
            "======================================================\n",
            sets_total, str);

        return TRUE;
    }
    else
        return FALSE;
}


#define NCOUNT(xxx) (sizeof(xxx) / sizeof(xxx[0]))


#define TEST2(fun, str1, str2, ret) do { \
        test_ctx ctx; test_init(&ctx); \
        test_start(&ctx, # fun  "('%s', '%s')", str1, str2); \
        test_result(&ctx, ( fun (str1, str2) == 0) == ret); \
        test_end(&ctx); \
    } while (0)

#define TEST2B(fun, str1, str2, ret) do { \
        test_ctx ctx; test_init(&ctx); \
        test_start(&ctx, # fun  "('%s', '%s')", str1, str2); \
        test_result(&ctx, fun (str1, str2) == ret); \
        test_end(&ctx); \
    } while (0)

#define TEST2C(fun, str1, str2, ret) do { \
        test_ctx ctx; test_init(&ctx); \
        test_start(&ctx, # fun  "('%s', '%s')", str1, str2); \
        test_result(&ctx, (fun (str1, str2) != NULL) == ret); \
        test_end(&ctx); \
    } while (0)

#define TEST3(fun, str1, str2, len, ret) do { \
        test_ctx ctx; test_init(&ctx); \
        test_start(&ctx, # fun  "('%s', '%s', %d)", str1, str2, len); \
        test_result(&ctx, ( fun (str1, str2, len) == 0) == ret); \
        test_end(&ctx); \
    } while (0)



int main(int argc, char *argv[])
{
    size_t i1, i2, i3, i4;
    char buf[64], buf2[64];

    //
    // Initialization
    //
    th_init("th-test", "th-libs unit tests", "0.1", NULL, NULL);
    th_verbosity = 0;

    if (sizeof(char) != sizeof(unsigned char))
    {
        THERR("sizeof(char) != sizeof(unsigned char)???\n");
        return -1;
    }

    tests_failed = tests_passed = tests_total = sets_total = sets_nenabled = 0;
    for (i1 = 0; i1 < SET_MAX_TESTS; i1++)
        sets_enabled[i1] = 1;

    //
    // Parse command line arguments
    //
    if (!th_args_process(argc, argv, arg_opts, arg_nopts,
        arg_handle_opt, NULL, 0))
        return 0;

    tprint(1, "Enabled test types are 0x%04x.\n", optFlags);

    //
    // Test series for printf()
    //
    char *i_fmts[] = { "", "05", "5", ".5", "8.5", "08.5", "3", "3.2", "3", ".0", "0" };
    char *i_mods[] = { "", "-", "+", "#", };
    char *i_types[] = { "d", "u", "i", "x", "X", "o", };
    if (test_set_start("printf() integer"))
    {
        int i_vals[] = { 0, -0, -1, 2, -2, 512, -1024, 612342, -612342, 0x1fff, 0x8000000, -123456789 };

        for (i1 = 0; i1 < NCOUNT(i_vals); i1++)
        {
            snprintf(buf, sizeof(buf), "%d", i_vals[i1]);

            for (i4 = 0; i4 < NCOUNT(i_mods); i4++)
            for (i3 = 0; i3 < NCOUNT(i_types); i3++)
            for (i2 = 0; i2 < NCOUNT(i_fmts); i2++)
            {
                snprintf(buf2, sizeof(buf2), "%%%s%s%s", i_mods[i4], i_fmts[i2], i_types[i3]);
                test_snprintf(buf, buf2, i_vals[i1]);
            }
        }
    }

    if (test_set_start("printf() integer 64bit"))
    {
        int64_t i_vals64[] = { 0, -0, -1, 2, -2, 612342, -612342, 0x3342344341fff, 0x1f8000000, };

        for (i1 = 0; i1 < NCOUNT(i_vals64); i1++)
        {
            snprintf(buf, sizeof(buf), "%" PRId64, i_vals64[i1]);

            for (i4 = 0; i4 < NCOUNT(i_mods); i4++)
            for (i3 = 0; i3 < NCOUNT(i_types); i3++)
            for (i2 = 0; i2 < NCOUNT(i_fmts); i2++)
            {
                snprintf(buf2, sizeof(buf2), "%%%s%sll%s", i_mods[i4], i_fmts[i2], i_types[i3]);
                test_snprintf(buf, buf2, i_vals64[i1]);
            }
        }
    }

    if (test_set_start("printf() float"))
    {
        double f_vals[] = { 1, 2, 3, 2.02, 612342.234, -2.07, -612342.12, 437692.9876543219, 0x1fff, 0x8000000, 0.15625 };
        char *f_fmts[] = { "%f", "%1.1f", "%8.5f", "%5f", "%-5f", "", "%-5.2f", "%08.5f" };

        for (i1 = 0; i1 < NCOUNT(f_vals); i1++)
        {
            snprintf(buf, sizeof(buf), "%f", f_vals[i1]);
            for (i2 = 0; i2 < NCOUNT(f_fmts); i2++)
                test_snprintf(buf, f_fmts[i2], f_vals[i1]);
        }
    }

    if (test_set_start("printf() string"))
    {
        char *s_vals[] = { "", "XYZXYZ", "xxx yyy zzz ppp fff", NULL, "X", "abcde", "dx", "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", };
        char *s_fmts[] = { "%s", "%2s", "%-2s", "%5s", "%-5s", "%16s", "%-16s", "%1s", "%-1s", "% 2s", "%03s", "% -12s", "% 03s", "%-.15s", "%.8s" };

        for (i1 = 0; i1 < NCOUNT(s_vals); i1++)
        {
            for (i2 = 0; i2 < NCOUNT(s_fmts); i2++)
                test_snprintf(s_vals[i1], s_fmts[i2], s_vals[i1]);
        }
    }

    if (test_set_start("printf() char"))
    {
        const char c_val = 'x';
        const char *c_msg = "x";
        char *c_fmts[] = { "a%cBC", "%c", "", "%0c", "%1c", "% c", "%-3c", "%3c", "%.3c", "%-.3c", "%-3.3c", "%.c", "%05c", "%-05c", };

        for (i1 = 0; i1 < NCOUNT(c_fmts); i1++)
            test_snprintf(c_msg, c_fmts[i1], c_val);
    }

    if (test_set_start("printf() pointers"))
    {
        char *p_fmts[] = { "%p", "%2p", "%.2p", "%03p", "%04p", "%-3p", "%0.3p", "%8p", "%32p", "%032p", "%-32p", "%-032p", "%16.8p", "%016.8p" };
        void *p_vals[] = { NULL, (void *) 1, &p_fmts, };

        for (i1 = 0; i1 < NCOUNT(p_vals); i1++)
        {
            snprintf(buf, sizeof(buf), "%p", p_vals[i1]);
            for (i2 = 0; i2 < NCOUNT(p_fmts); i2++)
                test_snprintf(buf, p_fmts[i2], p_vals[i1]);
        }
    }

    //
    // String matching functions
    //
    if (test_set_start("String compare #1"))
    {
        TEST2(th_strcasecmp, "aSdFq", "asdfq", TRUE);
        TEST2(th_strcasecmp, "aSdFq", "asFfq", FALSE);
        TEST2(th_strcasecmp, "abcde", "abcde", TRUE);
        TEST2(th_strcasecmp, "öäå", "öäå", TRUE);
        TEST2(th_strcasecmp, "aöäå", "aöäå", TRUE);
    }

    if (test_set_start("String compare #2"))
    {
        TEST3(th_strncasecmp, "aSdFq", "asFfqB", 4, FALSE);
        TEST3(th_strncasecmp, "aSdFq", "asFfqQ", 2, TRUE);
        TEST3(th_strncasecmp, "aSdFq", "asDfq", 3, TRUE);
        TEST3(th_strncasecmp, "aSdFq", "asDfq", 2, TRUE);
        TEST3(th_strncasecmp, "aSdFq", "asDfq", 0, TRUE);
        TEST3(th_strncasecmp, "aSdFq", "QsDfq", 0, TRUE);
        TEST3(th_strncasecmp, "aSdFq", "QsDfq", 1, FALSE);
    }

    if (test_set_start("String compare #3"))
    {
        TEST2C(th_strrcasecmp, "foo aSdFq", " asdfq", TRUE);
        TEST2C(th_strrcasecmp, "aSdFq", " asdfq", FALSE);
        TEST2C(th_strrcasecmp, "foo aSdFq baz", "asdfq", FALSE);
    }

    if (test_set_start("String matching #1"))
    {
        TEST2B(th_strmatch, "abba ABBAkukka lol", "*lol", TRUE);
        TEST2B(th_strmatch, "abba ABBAkukka lol", "*lo*", TRUE);
        TEST2B(th_strmatch, "abba ABBAkukka lol", "*lo", FALSE);
        TEST2B(th_strmatch, "abba ABBAkukka lol", "abba", FALSE);
        TEST2B(th_strmatch, "abba ABBAkukka lol", "*bba*", TRUE);
        TEST2B(th_strmatch, "abba ABBAkukka lol", "abba*", TRUE);
        TEST2B(th_strmatch, "abba ABBAkukka lol", "abbak*", FALSE);
        TEST2B(th_strmatch, "abba ABBAöökukka lol", "*abbaö?", FALSE);
    }

    if (test_set_start("String matching #2"))
    {
        TEST2B(th_strcasematch, "abba ABBAkukka lol", "abbak*", FALSE);
        TEST2B(th_strcasematch, "abba ABBAkukka lol", "*abbak*", TRUE);
        TEST2B(th_strcasematch, "abba ABBAkukka lol", "*ab?ak*", TRUE);
        TEST2B(th_strcasematch, "abba ABBAkukka lol", "*abbak?", FALSE);
        TEST2B(th_strcasematch, "abba ABBAkukka lol", "?bba?abba*", TRUE);
    }

    // Tests that test for things that do not work correctly yet
    // Unicode / multibyte UTF-8 causes problems here
    if (test_set_start("Invalid"))
    {
        TEST2(th_strcasecmp, "ÖÄÅ", "öäå", FALSE); // SHOULD match
        TEST3(th_strncasecmp, "Aäöå", "aöå", 2, TRUE); // should NOT match
        TEST2B(th_strmatch, "öriÖRI! lol", "?ri?RI!*", FALSE); // should match
    }

    //
    // Print summary and exit
    //
    tprint(1,
        "======================================================\n");

    tprint(0,
        "%d tests failed, %d passed (%d main tests), %d test sets of %d sets total.\n\n",
        tests_failed, tests_passed, tests_total, sets_nenabled, sets_total);

    return 0;
}
