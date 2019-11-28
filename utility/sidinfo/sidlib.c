/*
 * SIDInfoLib - Way too simplistic PSID/RSID file library
 * Programmed and designed by Matti 'ccr' Hämäläinen <ccr@tnsp.org>
 * (C) Copyright 2014-2018 Tecnic Software productions (TNSP)
 */
#include "sidlib.h"
#include "th_endian.h"
#include "th_string.h"


#define SIDLIB_DB_MAGIC "SIDLibDB"
#define SIDLIB_DB_VERSION 0x0100


typedef struct
{
    char magic[8];      // File magic ID
    uint16_t version;   // Version
    uint32_t nnodes;    // Number of song nodes
//  struct {
//      th_md5hash_t hash;         // Hash for this SID file
//      uint16_t nlengths;         // Number of song (lengths)
//      uint16_t length[nlengths]; // Length in seconds
//  } * nnodes;
    th_md5hash_t hash;
} PSIDLibHdr;


BOOL si_fread_str(th_ioctx *ctx, char **str, const size_t len)
{
    char *tmp = th_malloc(len + 1);
    if (tmp == NULL)
        goto err;

    if (!thfread_str(ctx, tmp, len))
        goto err;

    tmp[len] = 0;

    *str = tmp;

    return TRUE;

err:
    th_free(tmp);
    return FALSE;
}


static BOOL si_read_hash_data(th_ioctx *ctx, PSIDHeader *psid,
    th_md5state_t *state, const BOOL newSLDB)
{
    uint8_t *data = NULL;
    BOOL ret = FALSE, first = TRUE;
    size_t read;

    if ((data = (uint8_t *) th_malloc(PSID_BUFFER_SIZE)) == NULL)
    {
        th_io_error(ctx, THERR_MALLOC,
            "Error allocating temporary data buffer of %d bytes.\n",
            PSID_BUFFER_SIZE);
        goto error;
    }

    psid->dataSize = 0;
    do
    {
        read = thfread(data, sizeof(uint8_t), PSID_BUFFER_SIZE, ctx);
        psid->dataSize += read;

        // If load address is 0 in header and we have the first block, grab it
        if (first && psid->loadAddress == 0)
        {
            if (read < 4)
            {
                th_io_error(ctx, THERR_FREAD,
                    "Error reading song data, unexpectedly small file.\n");
                goto error;
            }

            // Grab the load address
            psid->loadAddress = TH_LE16_TO_NATIVE(*(uint16_t *) data);

            // .. do not include the load address to the hash if NEW SLDB format
            if (newSLDB)
                th_md5_append(state, &data[2], read - 2);
            else
                th_md5_append(state, data, read);

            first = FALSE;
        }
        else
        if (read > 0)
        {
            // Append data "as is"
            th_md5_append(state, data, read);
        }
    } while (read > 0 && !thfeof(ctx));

    ret = TRUE;

error:
    th_free(data);
    return ret;
}


BOOL si_read_sid_file(th_ioctx *ctx, PSIDHeader **ppsid, const BOOL newSLDB)
{
    PSIDHeader *psid = NULL;
    th_md5state_t state;
    BOOL ret = FALSE;
    off_t hdrStart, hdrEnd;

    if ((psid = *ppsid = th_malloc0(sizeof(PSIDHeader))) == NULL)
    {
        th_io_error(ctx, THERR_MALLOC,
            "Error PSID context struct.\n");
        goto error;
    }

    hdrStart = thftell(ctx);

    // Read PSID header in
    if (!thfread_str(ctx, (uint8_t *) psid->magic, PSID_MAGIC_LEN) ||
        !thfread_be16(ctx, &psid->version) ||
        !thfread_be16(ctx, &psid->dataOffset) ||
        !thfread_be16(ctx, &psid->loadAddress) ||
        !thfread_be16(ctx, &psid->initAddress) ||
        !thfread_be16(ctx, &psid->playAddress) ||
        !thfread_be16(ctx, &psid->nSongs) ||
        !thfread_be16(ctx, &psid->startSong) ||
        !thfread_be32(ctx, &psid->speed))
    {
        th_io_error(ctx, ctx->status,
            "Could not read PSID/RSID header from '%s': %s.\n",
            ctx->filename, th_error_str(ctx->status));
        goto error;
    }

    psid->magic[PSID_MAGIC_LEN] = 0;

    if ((psid->magic[0] != 'R' && psid->magic[0] != 'P') ||
        psid->magic[1] != 'S' || psid->magic[2] != 'I' || psid->magic[3] != 'D' ||
        psid->version < 1 || psid->version > 4)
    {
        th_io_error(ctx, THERR_NOT_SUPPORTED,
            "Not a supported PSID or RSID file: %s\n",
            ctx->filename);
        goto error;
    }

    psid->isRSID = psid->magic[0] == 'R';

    if (!si_fread_str(ctx, &psid->sidName, PSID_STR_LEN) ||
        !si_fread_str(ctx, &psid->sidAuthor, PSID_STR_LEN) ||
        !si_fread_str(ctx, &psid->sidCopyright, PSID_STR_LEN))
    {
        th_io_error(ctx, ctx->status,
            "Error reading SID file header from '%s': %s.\n",
            ctx->filename, th_error_str(ctx->status));
        goto error;
    }

    // Check if we need to load PSIDv2NG header ...
    if (psid->version >= 2)
    {
        // Yes, we need to
        if (!thfread_be16(ctx, &psid->flags) ||
            !thfread_u8(ctx, &psid->startPage) ||
            !thfread_u8(ctx, &psid->pageLength) ||
            !thfread_u8(ctx, &psid->sid2Addr) ||
            !thfread_u8(ctx, &psid->sid3Addr))
        {
            th_io_error(ctx, ctx->status,
                "Error reading PSID/RSID v2+ extra header data from '%s': %s.\n",
                ctx->filename, th_error_str(ctx->status));
            goto error;
        }
    }

    hdrEnd = thftell(ctx);

    // Initialize MD5-hash calculation
    th_md5_init(&state);

    if (newSLDB)
    {
        // New Songlengths.md5 style hash calculation:
        // We just hash the whole file, so seek back to beginning ..
        thfseek(ctx, hdrStart, SEEK_SET);

        if (!si_read_hash_data(ctx, psid, &state, FALSE))
            goto error;

        psid->dataSize -= hdrEnd - hdrStart;
    }
    else
    {
        // "Old" Songlengths.txt style MD5 hash calculation
        // We need to separately hash data etc.
        if (!si_read_hash_data(ctx, psid, &state, TRUE))
            goto error;

        // Append header data to hash
        th_md5_append_le16(&state, psid->initAddress);
        th_md5_append_le16(&state, psid->playAddress);
        th_md5_append_le16(&state, psid->nSongs);

        // Append song speed data to hash
        uint8_t tmp8 = psid->isRSID ? 60 : 0;
        for (int index = 0; index < psid->nSongs && index < 32; index++)
        {
            if (psid->isRSID)
                tmp8 = 60;
            else
                tmp8 = (psid->speed & (1 << index)) ? 60 : 0;

            th_md5_append(&state, &tmp8, sizeof(tmp8));
        }

        // Rest of songs (more than 32)
        for (int index = 32; index < psid->nSongs; index++)
            th_md5_append(&state, &tmp8, sizeof(tmp8));

        // PSIDv2NG specific
        if (psid->version >= 2)
        {
            // REFER TO SIDPLAY HEADERS FOR MORE INFORMATION
            tmp8 = (psid->flags >> 2) & 3;
            if (tmp8 == 2)
                th_md5_append(&state, &tmp8, sizeof(tmp8));
        }
    }

    // Calculate the hash
    th_md5_finish(&state, psid->hash);
    ret = TRUE;

error:
    // Free buffer
    return ret;
}


void si_free_sid_file(PSIDHeader *psid)
{
    if (psid != NULL)
    {
        th_free(psid->sidName);
        th_free(psid->sidAuthor);
        th_free(psid->sidCopyright);
    }
}


const char *si_get_sid_clock_str(const int flags)
{
    switch (flags)
    {
        case PSF_CLOCK_UNKNOWN : return "Unknown";
        case PSF_CLOCK_PAL     : return "PAL 50Hz";
        case PSF_CLOCK_NTSC    : return "NTSC 60Hz";
        case PSF_CLOCK_ANY     : return "PAL / NTSC";
        default                : return "?";
    }
}


const char *si_get_sid_model_str(const int flags)
{
    switch (flags)
    {
        case PSF_MODEL_UNKNOWN : return "Unknown";
        case PSF_MODEL_MOS6581 : return "MOS6581";
        case PSF_MODEL_MOS8580 : return "MOS8580";
        case PSF_MODEL_ANY     : return "MOS6581 / MOS8580";
        default                : return "?";
    }
}


// Free memory allocated for given SLDB node
//
static void si_sldb_node_free(SIDLibSLDBNode *node)
{
    if (node != NULL)
    {
        th_free_r(&node->lengths);
        th_free_r(&node);
    }
}


// Insert given node to db linked list
//
static void si_sldb_node_insert(SIDLibSLDB *dbh, SIDLibSLDBNode *node)
{
    if (dbh->nodes != NULL)
    {
        node->prev = dbh->nodes->prev;
        dbh->nodes->prev->next = node;
        dbh->nodes->prev = node;
    }
    else
    {
        dbh->nodes = node;
        node->prev = node;
    }
    node->next = NULL;
}


// Parse a time-entry in SLDB format
//
static int si_sldb_get_value(const char *str, size_t *pos)
{
    int result = 0;

    while (isdigit(str[*pos]))
        result = (result * 10) + (str[(*pos)++] - '0');

    return result;
}


static int si_sldb_gettime(const char *str, size_t *pos)
{
    int result;

    // Check if it starts with a digit
    if (th_isdigit(str[*pos]))
    {
        // Get minutes-field
        result = si_sldb_get_value(str, pos) * 60;

        // Check the field separator char
        if (str[*pos] == ':')
        {
            // Get seconds-field
            (*pos)++;
            result += si_sldb_get_value(str, pos);
        }
        else
            result = -2;
    }
    else
        result = -1;

    // Ignore and skip the possible attributes
    while (str[*pos] && !th_isspace(str[*pos]))
        (*pos)++;

    return result;
}


// Parse one SLDB definition line, return SLDB node
//
SIDLibSLDBNode *si_sldb_parse_entry(th_ioctx *ctx, const char *line)
{
    SIDLibSLDBNode *node = NULL;
    size_t pos, tmpLen, savePos;
    BOOL isOK;
    int i;

    // Allocate new node
    node = (SIDLibSLDBNode *) th_malloc0(sizeof(SIDLibSLDBNode));
    if (node == NULL)
    {
        th_io_error(ctx, THERR_MALLOC,
            "Error allocating new node.\n");
        return NULL;
    }

    // Get hash value
    for (pos = 0, i = 0; i < TH_MD5HASH_LENGTH; i++, pos += 2)
    {
        unsigned int tmpu;
        sscanf(&line[pos], "%2x", &tmpu);
        node->hash[i] = tmpu;
    }

    // Get playtimes
    th_findnext(line, &pos);
    if (line[pos] != '=')
    {
        th_io_error(ctx, THERR_INVALID_DATA,
            "'=' expected on column #%d.\n", pos);
        goto error;
    }

    // First playtime is after '='
    savePos = ++pos;
    tmpLen = strlen(line);

    // Get number of sub-tune lengths
    isOK = TRUE;
    while (pos < tmpLen && isOK)
    {
        th_findnext(line, &pos);

        if (si_sldb_gettime(line, &pos) >= 0)
            node->nlengths++;
        else
            isOK = FALSE;
    }

    // Allocate memory for lengths
    if (node->nlengths == 0)
        goto error;

    node->lengths = (int *) th_calloc(node->nlengths, sizeof(int));
    if (node->lengths == NULL)
    {
        th_io_error(ctx, THERR_MALLOC,
            "Could not allocate memory for node.\n");
        goto error;
    }

    // Read lengths in
    for (i = 0, pos = savePos, isOK = TRUE;
         pos < tmpLen && i < node->nlengths && isOK; i++)
    {
        int l;
        th_findnext(line, &pos);

        l = si_sldb_gettime(line, &pos);
        if (l >= 0)
            node->lengths[i] = l;
        else
            isOK = FALSE;
    }

    return node;

error:
    si_sldb_node_free(node);
    return NULL;
}


SIDLibSLDB * si_sldb_new(void)
{
    return (SIDLibSLDB *) th_malloc0(sizeof(SIDLibSLDB));
}


// Read SLDB database to memory
//
int si_sldb_read(th_ioctx *ctx, SIDLibSLDB *dbh)
{
    char *line = NULL;

    if ((line = th_malloc(PSID_BUFFER_SIZE)) == NULL)
    {
        th_io_error(ctx, THERR_MALLOC,
            "Error allocating temporary data buffer of %d bytes.\n",
            PSID_BUFFER_SIZE);
        return ctx->status;
    }

    while (thfgets(line, PSID_BUFFER_SIZE, ctx) != NULL)
    {
        SIDLibSLDBNode *tmnode;
        size_t pos = 0;
        ctx->line++;

        th_findnext(line, &pos);

        // Check if it is datafield
        if (th_isxdigit(line[pos]))
        {
            // Check the length of the hash
            int hashLen;
            for (hashLen = 0; line[pos] && th_isxdigit(line[pos]); hashLen++, pos++);

            if (hashLen != TH_MD5HASH_LENGTH_CH)
            {
                th_io_error(ctx, THERR_INVALID_DATA,
                    "Invalid MD5-hash in SongLengthDB file '%s' line #%d:\n%s\n",
                    ctx->filename, ctx->line, line);
            }
            else
            {
                // Parse and add node to db
                if ((tmnode = si_sldb_parse_entry(ctx, line)) != NULL)
                {
                    si_sldb_node_insert(dbh, tmnode);
                }
                else
                {
                    th_io_error(ctx, THERR_INVALID_DATA,
                        "Invalid entry in SongLengthDB file '%s' line #%d:\n%s\n",
                        ctx->filename, ctx->line, line);
                }
            }
        }
        else
        if (line[pos] != ';' && line[pos] != '[' && line[pos] != 0)
        {
            th_io_error(ctx, THERR_INVALID_DATA,
                "Invalid line in SongLengthDB file '%s' line #%d:\n%s\n",
                ctx->filename, ctx->line, line);
        }
    }

    th_free(line);
    return THERR_OK;
}


// Compare two given MD5-hashes.
// Return: 0 if equal
//         negative if hash1 < hash2
//         positive if hash1 > hash2
//
static int si_sldb_compare_hash(th_md5hash_t hash1, th_md5hash_t hash2)
{
    int i, delta;

    for (i = delta = 0; i < TH_MD5HASH_LENGTH && !delta; i++)
        delta = hash1[i] - hash2[i];

    return delta;
}


// Compare two nodes.
// We assume here that we never ever get NULL-pointers.
static int si_sldb_compare_nodes(const void *node1, const void *node2)
{
    return si_sldb_compare_hash(
        (*(SIDLibSLDBNode **) node1)->hash,
        (*(SIDLibSLDBNode **) node2)->hash);
}


// (Re)create index
//
int si_sldb_build_index(SIDLibSLDB * dbh)
{
    SIDLibSLDBNode *node;

    // Free old index
    th_free_r(&dbh->pindex);

    // Get size of db
    for (node = dbh->nodes, dbh->nnodes = 0; node != NULL; node = node->next)
        dbh->nnodes++;

    // Check number of nodes
    if (dbh->nnodes > 0)
    {
        size_t i;

        // Allocate memory for index-table
        dbh->pindex = (SIDLibSLDBNode **) th_malloc(sizeof(SIDLibSLDBNode *) * dbh->nnodes);
        if (dbh->pindex == NULL)
            return THERR_MALLOC;

        // Get node-pointers to table
        for (i = 0, node = dbh->nodes; node && i < dbh->nnodes; node = node->next)
            dbh->pindex[i++] = node;

        // Sort the indexes
        qsort(dbh->pindex, dbh->nnodes, sizeof(SIDLibSLDBNode *), si_sldb_compare_nodes);
    }

    return THERR_OK;
}


//
// Read binary format SLDB
//
int si_sldb_read_bin(th_ioctx *ctx, SIDLibSLDB *dbh)
{
    PSIDLibHdr hdr;
    th_md5state_t state;
    th_md5hash_t hash;
    size_t n;

    // Check pointers
    if (ctx == NULL || dbh == NULL)
        return THERR_NULLPTR;

    if (!thfread_str(ctx, &hdr.magic, sizeof(hdr.magic)) ||
        !thfread_le16(ctx, &hdr.version) ||
        !thfread_le32(ctx, &hdr.nnodes))
        return THERR_FREAD;

    // Check magic and version
    if (memcmp(hdr.magic, SIDLIB_DB_MAGIC, sizeof(hdr.magic)) != 0)
        return THERR_NOT_SUPPORTED;

    if (hdr.version != SIDLIB_DB_VERSION)
        return THERR_NOT_SUPPORTED;

    // Make some reasonable assumptions about validity
    if (hdr.nnodes == 0 || hdr.nnodes > 256*1024)
        return THERR_INVALID_DATA;

    th_md5_init(&state);
    th_md5_append_ne16(&state, hdr.version);
    th_md5_append_ne32(&state, hdr.nnodes);

    // Allocate index
    dbh->nnodes = hdr.nnodes;
    dbh->pindex = (SIDLibSLDBNode **) th_malloc(sizeof(SIDLibSLDBNode *) * dbh->nnodes);
    if (dbh->pindex == NULL)
        return THERR_MALLOC;

    // Read nodes
    for (n = 0; n < dbh->nnodes; n++)
    {
        SIDLibSLDBNode *node;
        th_md5hash_t mhash;
        uint16_t tmpn;
        int index;

        // Read node hash and nlengths
        if (!thfread_str(ctx, mhash, TH_MD5HASH_LENGTH) ||
            !thfread_le16(ctx, &tmpn))
            return THERR_FREAD;

        // Sanity check
        if (tmpn == 0 || tmpn > 2048)
            return THERR_INVALID_DATA;

        // Append to hash
        th_md5_append(&state, mhash, TH_MD5HASH_LENGTH);
        th_md5_append_ne16(&state, tmpn);

        // Allocate node
        node = (SIDLibSLDBNode *) th_malloc0(sizeof(SIDLibSLDBNode));
        if (node == NULL)
            return THERR_MALLOC;

        node->nlengths = tmpn;
        memcpy(node->hash, mhash, sizeof(mhash));

        node->lengths = (int *) th_calloc(node->nlengths, sizeof(int));
        if (node->lengths == NULL)
        {
            th_free(node);
            th_io_error(ctx, THERR_MALLOC,
                "Could not allocate memory for node.\n");
            return ctx->status;
        }

        // Read node lenghts
        for (index = 0; index < node->nlengths; index++)
        {
            uint16_t tmpl;
            if (!thfread_le16(ctx, &tmpl))
                return THERR_FREAD;

            th_md5_append_ne16(&state, tmpl);
            node->lengths[index] = tmpl;
        }

        si_sldb_node_insert(dbh, node);
        dbh->pindex[n] = node;
    }

    // Read stored checksum hash
    if (!thfread_str(ctx, hdr.hash, sizeof(hdr.hash)))
        return THERR_FREAD;

    // Compare to what we get
    th_md5_finish(&state, hash);
    if (memcmp(hash, hdr.hash, sizeof(hdr.hash)) != 0)
        return THERR_INVALID_DATA;

    return THERR_OK;
}


int si_sldb_write_bin(th_ioctx *ctx, SIDLibSLDB *dbh)
{
    th_md5state_t state;
    th_md5hash_t hash;
    size_t n;

    // Check pointers
    if (ctx == NULL || dbh == NULL)
        return THERR_NULLPTR;

    // Write header
    if (!thfwrite_str(ctx, SIDLIB_DB_MAGIC, 8) ||
        !thfwrite_le16(ctx, SIDLIB_DB_VERSION) ||
        !thfwrite_le32(ctx, dbh->nnodes))
        return THERR_FWRITE;

    th_md5_init(&state);
    th_md5_append_ne16(&state, SIDLIB_DB_VERSION);
    th_md5_append_ne32(&state, dbh->nnodes);

    // Write nodes
    for (n = 0; n < dbh->nnodes; n++)
    {
        SIDLibSLDBNode *node = dbh->pindex[n];
        uint16_t tmpn;
        int index;

        // Sanity check
        if (node->nlengths <= 0 || node->nlengths > 2048)
            return THERR_INVALID_DATA;

        tmpn = node->nlengths;
        th_md5_append(&state, node->hash, TH_MD5HASH_LENGTH);
        th_md5_append_ne16(&state, tmpn);

        // Write node data
        if (!thfwrite_str(ctx, node->hash, TH_MD5HASH_LENGTH) ||
            !thfwrite_le16(ctx, tmpn))
            return THERR_FWRITE;

        for (index = 0; index < node->nlengths; index++)
        {
            if (node->lengths[index] > 16378)
                return THERR_INVALID_DATA;

            if (!thfwrite_le16(ctx, node->lengths[index]))
                return THERR_FWRITE;

            th_md5_append_ne16(&state, node->lengths[index]);
        }
    }

    th_md5_finish(&state, hash);
    if (!thfwrite_str(ctx, hash, sizeof(hash)))
        return THERR_FWRITE;

    return THERR_OK;
}


// Free a given song-length database
//
void si_sldb_free(SIDLibSLDB *dbh)
{
    if (dbh != NULL)
    {
        SIDLibSLDBNode *node = dbh->nodes;
        while (node != NULL)
        {
            SIDLibSLDBNode *next = node->next;
            si_sldb_node_free(node);
            node = next;
        }

        dbh->nodes = NULL;
        dbh->nnodes = 0;

        th_free_r(&dbh->pindex);
        th_free(dbh);
    }
}


SIDLibSLDBNode *si_sldb_get_by_hash(SIDLibSLDB *dbh, th_md5hash_t hash)
{
    SIDLibSLDBNode keyItem, *key, **item;

    memcpy(&keyItem.hash, hash, sizeof(th_md5hash_t));
    key = &keyItem;
    item = bsearch(&key, dbh->pindex, dbh->nnodes, sizeof(dbh->pindex[0]), si_sldb_compare_nodes);

    return (item != NULL) ? *item : NULL;
}
