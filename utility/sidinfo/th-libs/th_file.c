/*
 * File, directory etc helper functions
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2016-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#include "th_file.h"
#include "th_string.h"
#include <unistd.h>
#ifdef TH_PLAT_WINDOWS
#  include <shlwapi.h>
#  include <shfolder.h>
#else
//#  include <sys/wait.h>
#  include <sys/stat.h>
#  include <sys/types.h>
#endif


char * th_get_home_dir()
{
#if defined(TH_PLAT_WINDOWS)
    char tmpPath[MAX_PATH];
    if (SHGetFolderPath(NULL, CSIDL_APPDATA | CSIDL_FLAG_CREATE, NULL, 0, tmpPath) == S_OK)
        return th_strdup(tmpPath);
#endif
    return th_strdup(getenv("HOME"));
}


char * th_get_data_dir()
{
#if defined(TH_PLAT_WINDOWS)
    char tmpPath[MAX_PATH];
    if (SHGetFolderPath(NULL, CSIDL_APPDATA | CSIDL_FLAG_CREATE, NULL, 0, tmpPath) == S_OK)
        return th_strdup(tmpPath);
#endif
    return th_strdup(getenv("HOME"));
}


char * th_get_config_dir(const char *name)
{
#if defined(TH_PLAT_WINDOWS)
    // For Windows, we just use the appdata directory
    (void) name;
    return th_get_data_dir();
#elif defined(USE_XDG)
    const char *xdgConfigDir = getenv("XDG_CONFIG_HOME");

    // If XDG is enabled, try the environment variable first
    if (xdgConfigDir != NULL && strcmp(xdgConfigDir, ""))
        return th_strdup_printf("%s%c%s%c", xdgConfigDir, TH_DIR_SEPARATOR, name, TH_DIR_SEPARATOR);
    else
    {
        // Nope, try the obvious alternative
        char *data = th_get_data_dir();
        char *dir = th_strdup_printf("%s%c%s%c%s%c", data, TH_DIR_SEPARATOR, ".config", TH_DIR_SEPARATOR, name, TH_DIR_SEPARATOR);
        th_free(data);
        return dir;
    }
#else
    // XDG not enabled
    (void) name;
    return th_get_data_dir();
#endif
}


#ifdef TH_PLAT_WINDOWS
static uint64_t th_convert_windows_time(FILETIME ftime)
{
    uint64_t value = (((uint64_t) ftime.dwHighDateTime) << 32ULL) | ((uint64_t) ftime.dwLowDateTime);

    // Naive conversion (1000 ns / 100) * ns - difference_between_win_to_unix_epoch
    return (value / ((1000 / 100) * 1000 * 1000)) - 11644473600ULL;;
}
#endif


BOOL th_stat_path(const char *path, th_stat_data *data)
{
#ifdef TH_PLAT_WINDOWS
    WIN32_FILE_ATTRIBUTE_DATA fdata;
    if (!GetFileAttributesExA(path, GetFileExInfoStandard, &fdata))
        return FALSE;

    data->size   = (((uint64_t) fdata.nFileSizeHigh) << 32ULL) | ((uint64_t) fdata.nFileSizeLow);
    data->ctime  = th_convert_windows_time(fdata.ftCreationTime);
    data->atime  = th_convert_windows_time(fdata.ftLastAccessTime);
    data->mtime  = th_convert_windows_time(fdata.ftLastWriteTime);

    data->flags  = (fdata.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY) ? TH_IS_DIR : 0;
    data->flags |= (fdata.dwFileAttributes & FILE_ATTRIBUTE_READONLY) ? 0 : TH_IS_WRITABLE;
    data->flags |= TH_IS_READABLE;
#else
    uid_t uid = geteuid();
    gid_t gid = getegid();
    struct stat sb;
    if (stat(path, &sb) < 0)
        return FALSE;

    data->size   = sb.st_size;
    data->ctime  = sb.st_ctime;
    data->atime  = sb.st_atime;
    data->mtime  = sb.st_mtime;

    data->flags  = S_ISDIR(sb.st_mode) ? TH_IS_DIR : 0;

    if ((uid == sb.st_uid && (sb.st_mode & S_IWUSR)) ||
        (gid == sb.st_gid && (sb.st_mode & S_IWGRP)) ||
        (sb.st_mode & S_IWOTH))
        data->flags |= TH_IS_WRITABLE;

    if ((uid == sb.st_uid && (sb.st_mode & S_IRUSR)) ||
        (gid == sb.st_gid && (sb.st_mode & S_IRGRP)) ||
        (sb.st_mode & S_IROTH))
        data->flags |= TH_IS_READABLE;
#endif

    return TRUE;
}


BOOL th_mkdir_path(const char *cpath, int mode)
{
    char save, *path = th_strdup(cpath);
    size_t start = 0, end;
    BOOL res = FALSE;

    // If mode is 0, default to something sensible
    if (mode == 0)
        mode = 0711;

    // Start creating the directory stucture
    do
    {
        // Split foremost path element out
        for (save = 0, end = start; path[end] != 0; end++)
        if (path[end] == TH_DIR_SEPARATOR)
        {
            save = path[end];
            path[end] = 0;
            break;
        }

        // If the element is there, create it
        if (path[start] != 0)
        {
            th_stat_data sdata;
            BOOL exists = th_stat_path(path, &sdata);
            if (exists && (sdata.flags & TH_IS_DIR) == 0)
                goto error;

            if (!exists)
            {
#ifdef TH_PLAT_WINDOWS
                if (!CreateDirectory(path, NULL))
                    goto error;
#else
                if (mkdir(path, mode) < 0)
                    goto error;
#endif
            }
        }

        // Restore separator character and jump to next element
        path[end] = save;
        start = end + 1;
    } while (save != 0);

    res = TRUE;

error:
    th_free(path);
    return res;
}
