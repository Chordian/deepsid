/*
 * SIDInfoLib - Way too simplistic PSID/RSID file library
 * Programmed and designed by Matti 'ccr' Hämäläinen <ccr@tnsp.org>
 * (C) Copyright 2014-2018 Tecnic Software productions (TNSP)
 */
#ifndef SIDLIB_H
#define SIDLIB_H 1

#include "th_util.h"
#include "th_ioctx.h"
#include "th_crypto.h"


#ifdef __cplusplus
extern "C" {
#endif


// Some constants
#define PSID_MAGIC_LEN    4
#define PSID_STR_LEN      32
#define PSID_BUFFER_SIZE  (1024 * 16)


typedef struct _SIDLibSLDBNode
{
    th_md5hash_t    hash;       // MD5 hash-digest
    int             nlengths;   // Number of lengths
    int             *lengths;   // Lengths in seconds
    struct _SIDLibSLDBNode *prev, *next;
} SIDLibSLDBNode;


typedef struct
{
    SIDLibSLDBNode  *nodes,
                    **pindex;
    size_t          nnodes;
} SIDLibSLDB;


typedef struct
{
    char magic[PSID_MAGIC_LEN + 1]; // "PSID" / "RSID" magic identifier
    uint16_t
        version,         // Version number
        dataOffset,      // Start of actual c64 data in file
        loadAddress,     // Loading address
        initAddress,     // Initialization address
        playAddress,     // Play one frame
        nSongs,          // Number of subsongs
        startSong;       // Default starting song
    uint32_t speed;      // Speed
    char *sidName;       // Descriptive text-fields, ASCIIZ
    char *sidAuthor;
    char *sidCopyright;

    // PSIDv2+ data
    uint16_t flags;      // Flags
    uint8_t  startPage, pageLength;
    uint8_t  sid2Addr, sid3Addr;

    // Extra data
    BOOL isRSID;
    size_t dataSize;     // Total size of data - header
    th_md5hash_t hash;   // Songlength database hash

    SIDLibSLDBNode *lengths; // Songlength information node pointer

} PSIDHeader;


enum
{
    PSF_PLAYER_TYPE   = 0x01, // 0 = built-in, 1 = Compute! SIDPlayer MUS
    PSF_PLAYSID_TUNE  = 0x02, // 0 = Real C64-compatible, 1 = PlaySID specific (v2NG)

    PSF_CLOCK_UNKNOWN = 0x00, // Video standard used (v2NG+)
    PSF_CLOCK_PAL     = 0x01,
    PSF_CLOCK_NTSC    = 0x02,
    PSF_CLOCK_ANY     = 0x03,
    PSF_CLOCK_MASK    = 0x03,

    PSF_MODEL_UNKNOWN = 0x00, // SID model (v2NG+)
    PSF_MODEL_MOS6581 = 0x01,
    PSF_MODEL_MOS8580 = 0x02,
    PSF_MODEL_ANY     = 0x03,
    PSF_MODEL_MASK    = 0x03,
};


//
// Functions
//
BOOL            si_read_sid_file(th_ioctx *ctx, PSIDHeader **ppsid, const BOOL newSLDB);
void            si_free_sid_file(PSIDHeader *psid);

const char *    si_get_sid_clock_str(const int flags);
const char *    si_get_sid_model_str(const int flags);

SIDLibSLDB *    si_sldb_new(void);
int             si_sldb_read(th_ioctx *ctx, SIDLibSLDB *dbh);
int             si_sldb_build_index(SIDLibSLDB *dbh);
void            si_sldb_free(SIDLibSLDB *dbh);
SIDLibSLDBNode *si_sldb_get_by_hash(SIDLibSLDB *dbh, th_md5hash_t hash);

int si_sldb_read_bin(th_ioctx *ctx, SIDLibSLDB *dbh);
int si_sldb_write_bin(th_ioctx *ctx, SIDLibSLDB *dbh);


#ifdef __cplusplus
}
#endif
#endif // SIDLIB_H
