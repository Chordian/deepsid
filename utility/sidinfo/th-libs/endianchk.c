#include <stdio.h>

int main(int argc, char *argv[])
{
    int val = 0x01020304, ret;
    unsigned char *s = (unsigned char *) &val;
    char *name = argv[0];
    (void) argc;

    if (sizeof(int) != 4)
    {
        fprintf(stderr, "%s: sizeof(int) is not 32 bits!\n", name);
        return -1;
    }

    if (s[0] == 0x01 && s[1] == 0x02 && s[2] == 0x03 && s[3] == 0x04)
        ret = 0;
    else
    if (s[0] == 0x04 && s[1] == 0x03 && s[2] == 0x02 && s[3] == 0x01)
        ret = 1;
    else
    {
        fprintf(stderr, "%s: Unsupported endianess.\n", name);
        return -2;
    }

    printf(
        "#ifndef MY_CONFIG_H\n"
        "#define MY_CONFIG_H 1\n"
        "\n"
        "#define TH_BYTEORDER TH_%s_ENDIAN\n"
        "\n"
        "#endif /* MY_CONFIG_H */\n"
        ,
        ret ? "LITTLE" : "BIG"
        );
    return 0;
}

