/*
 * SIDId V1.09 - Quick & dirty HVSC playroutine identity scanner
 * Written by Cadaver (loorni@gmail.com), playroutine signatures provided by Ian
 * Coog, Ice00, Yodelking and Wilfred/HVSC
 * 
 * Copyright (C) 2006-2012 by the author & contributors. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ''AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
 * EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

#ifdef __WIN32__
#include <windows.h>
#endif

#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <dirent.h>
#include <sys/stat.h>

#define MAX_SIGSIZE 4096
#define MAX_PATHNAME 256
#define END -1
#define ANY -2
#define AND -3
#define NAME -4

typedef struct
{
  char *name;
  int count;
  void *firstsig;
  void *next;
} SIDID;

typedef struct
{
  int *bytes;
  void *next;
} SIDSIG;

int main(int argc, char **argv);
void readconfig(char *name);
void identifydir(void);
void identifyfile(char *name, char *fullname);
int identifybuffer(SIDID *id, unsigned char *buffer, int length);
int identifybytes(int *bytes, unsigned char *buffer, int length);
int ishex(char c);
int gethex(char c);
void printstats(void);

int examined = 0;
int identified = 0;
int unidentified = 0;
int multiscan = 0;
int unknown = 0;
int onlyunknown = 0;
int allfiles = 0;
int nosubdirs = 0;

SIDID *firstid = NULL;
SIDID *lastid = NULL;
SIDID *playerid = NULL;

char scanbasedir[MAX_PATHNAME];
char playername[MAX_PATHNAME];

int main(int argc, char **argv)
{
  char basedir[MAX_PATHNAME];
  char configfilename[MAX_PATHNAME];
  int c;

  configfilename[0] = 0;
  playername[0] = 0;
  getcwd(basedir, MAX_PATHNAME);
  strcpy(scanbasedir, basedir);

  #ifdef __WIN32__
  GetModuleFileName(NULL, configfilename, MAX_PATHNAME);
  configfilename[strlen(configfilename)-3] = 'c';
  configfilename[strlen(configfilename)-2] = 'f';
  configfilename[strlen(configfilename)-1] = 'g';
  #endif

  if (getenv("SIDIDCFG")) strcpy(configfilename, getenv("SIDIDCFG"));

  for (c = 1; c < argc; c++)
  {
    if (argv[c][0] == '-')
    {
      switch(tolower(argv[c][1]))
      {
        default:
        if (strcmp("-help", &argv[c][1])) break;
        case '?':
        printf("Usage: sidid [directory to scan] [options]\n\n"
               "Options:\n"
               "-a             Scan all files, not just those with .sid extension\n"
               "-c<configfile> Configfile to use (env.variable SIDIDCFG can also be used)\n"
               "-d             Do not recurse subdirs\n"
               "-m             Scan each file for multiple signatures\n"
               "-o             List only unidentified files\n"
               "-s<playername> Scan only for specific player\n"
               "-u             List also unidentified files\n"
               "-? or --help   Display usage information\n");
        return 0;

    	  case 'm':
        multiscan = 1;
        break;

        case 'u':
        unknown = 1;
        break;

        case 'o':
        onlyunknown = 1;
        unknown = 0;
        break;

        case 'a':
        allfiles = 1;
        break;

        case 'd':
        nosubdirs = 1;
        break;

        case 's':
        strcpy(playername, &argv[c][2]);
        break;
        
        case 'c':
        strcpy(configfilename, &argv[c][2]);
        break;
      }
    }
    else strcpy(scanbasedir, argv[c]);
  }

  readconfig(configfilename);
  if (!firstid)
  {
    printf("No signatures defined!\n");
    return 1;
  }
  else printf("\n");

  chdir(scanbasedir);
  getcwd(scanbasedir, MAX_PATHNAME);
  identifydir();
  chdir(basedir);

  printstats();
  return 0;
}

void readconfig(char *name)
{
  char tokenstr[MAX_PATHNAME];
  int temp[MAX_SIGSIZE];
  int sigsize = 0;
  SIDSIG *lastsig = NULL;

  printf("Using configfile %s\n", name);
  FILE *in = fopen(name, "rt");
  if (!in) return;

  for (;;)
  {
    int len;

    tokenstr[0] = 0;
    fscanf(in, "%s", tokenstr);
    len = strlen(tokenstr);

    if (len)
    {
      int token = NAME;

      if (!strcmp("??", tokenstr)) token = ANY;
      if ((!strcmp("end", tokenstr)) || (!strcmp("END", tokenstr))) token = END;
      if ((!strcmp("and", tokenstr)) || (!strcmp("AND", tokenstr))) token = AND;
      if ((len == 2) && (ishex(tokenstr[0])) && (ishex(tokenstr[1])))
      {
        token = gethex(tokenstr[0]) * 16 + gethex(tokenstr[1]);
      }

      switch (token)
      {
        case NAME:
        {
          SIDID *newid = malloc(sizeof (SIDID));
          if (!newid)
          {
          	printf("Out of memory!\n");
          	goto CONFIG_ERROR;
          }
          newid->name = strdup(tokenstr);
          newid->firstsig = NULL;
          newid->next = NULL;
          newid->count = 0;

          if (!strcmp(playername, newid->name)) playerid = newid;

          if (!firstid)
          {
          	firstid = newid;
          }
          else
          {
            if (lastid) lastid->next = (void *)newid;
          }
          lastid = newid;

          sigsize = 0;
        }
        break;

        case END:
        if (sigsize >= MAX_SIGSIZE)
        {
          printf("Maximum signature size exceeded!\n");
          goto CONFIG_ERROR;
        }
        else
        {
          temp[sigsize++] = END;
          if (sigsize > 1)
          {
            int c;

            SIDSIG *newsig = malloc(sizeof (SIDSIG));
            int *newbytes = malloc(sigsize * sizeof (int));
            if ((!newsig) || (!newbytes))
            {
              printf("Out of memory!\n");
              goto CONFIG_ERROR;
            }
            newsig->bytes = newbytes;
            newsig->next = NULL;
            for (c = 0; c < sigsize; c++)
            {
              newsig->bytes[c] = temp[c];
            }

            if (!lastid)
          	{
              printf("No playername defined before signature!\n");
              goto CONFIG_ERROR;
          	}
            else
            {
              if (!lastid->firstsig)
              {
              	lastid->firstsig = (void *)newsig;
              }
              else
              {
                if (lastsig)
                {
                  lastsig->next = (void *)newsig;
                }
              }
            }
            lastsig = newsig;
          }
        }
        sigsize = 0;
        break;

        default:
        if (sigsize >= MAX_SIGSIZE)
        {
          printf("Maximum signature size exceeded!\n");
          goto CONFIG_ERROR;
        }
        temp[sigsize++] = token;
        break;
      }
    }
    else break;
  }
  CONFIG_ERROR:
  fclose(in);
}

void identifydir(void)
{
  DIR *dir;
  struct dirent *de;
  struct stat st;
  char currentdir[MAX_PATHNAME];
  char fullname[MAX_PATHNAME];

  getcwd(currentdir, MAX_PATHNAME);

  dir = opendir(".");
  if (dir)
  {
    while ((de = readdir(dir)))
    {
      stat(de->d_name, &st);
      if (st.st_mode & S_IFDIR)
      {
        if ((strcmp(".", de->d_name)) && (strcmp("..", de->d_name)) && (!nosubdirs))
        {
          chdir(de->d_name);
          identifydir();
          chdir("..");
        }
      }
      else
      {
        if (strlen(currentdir) > strlen(scanbasedir))
        {
          strcpy(fullname, &currentdir[strlen(scanbasedir)+1]);
          #ifdef __WIN32__
          strcat(fullname, "\\");
          #else
          strcat(fullname, "/");
          #endif
        }
        else fullname[0] = 0;
        strcat(fullname, de->d_name);

        identifyfile(de->d_name, fullname);
      }
    }
    closedir(dir);
  }
}

void identifyfile(char *name, char *fullname)
{
  unsigned char *buffer = NULL;
  SIDID *id;
  int length;
  int found = 0;

  if (!playerid) id = firstid;
  else id = playerid;

  if (!allfiles)
  {
    if (strlen(name) < 3) return;
    if (tolower(name[strlen(name) - 3]) != 's') return;
    if (tolower(name[strlen(name) - 2]) != 'i') return;
    if (tolower(name[strlen(name) - 1]) != 'd') return;
  }

  FILE *in = fopen(name, "rb");
  if (!in) return;

  fseek(in, 0, SEEK_END);
  length = ftell(in);
  fseek(in, 0, SEEK_SET);
  buffer = malloc(length);
  if (!buffer)
  {
    printf("Out of memory with file %s!\n", name);
    fclose(in);
    return;
  }
  fread(buffer, 1, length, in);
  fclose(in);

  if (!playerid)
    fullname[100] = 0;
  
  while (id)
  {
    if (identifybuffer(id, buffer, length))
    {
      id->count++;
      if (!found)
      {
        found = 1;
        identified++;
      }
      else
      {
        fullname[0] = 0;
      }
      if (!onlyunknown)
      {
        if (!playerid)
          printf("%-100s %s\n", fullname, id->name);
        else
          printf("%s\n", fullname);
      }
      if (!multiscan) break;
    }
    if (!playerid) id = (SIDID *)id->next;
    else break;
  }
  if (!found)
  {
    unidentified++;
    if ((unknown) || (onlyunknown))
    {
      fullname[100] = 0;
      printf("%-100s *Unidentified*\n", fullname);
    }
  }
  examined++;

  free(buffer);
}

int identifybuffer(SIDID *id, unsigned char *buffer, int length)
{
  SIDSIG *sig = id->firstsig;

  while (sig)
  {
    if (identifybytes(sig->bytes, buffer, length)) return 1;
    sig = (SIDSIG *)sig->next;
  }
  return 0;
}

int identifybytes(int *bytes, unsigned char *buffer, int length)
{
  int c = 0, d = 0, rc = 0, rd = 0;

  while (c < length)
  {
    if (d == rd)
    {
      if (buffer[c] == bytes[d])
      {
        rc = c+1;
        d++;
      }
      c++;
    }
    else
    {
      if (bytes[d] == END) return 1;
      if (bytes[d] == AND)
      {
        d++;
        while (c < length)
        {
          if (buffer[c] == bytes[d])
          {
            rc = c+1;
            rd = d;
            break;
          }
          c++;
        }
        if (c >= length)
          return 0;
      }
      if ((bytes[d] != ANY) && (buffer[c] != bytes[d]))
      {
        c = rc;
        d = rd;
      }
      else
      {
        c++;
        d++;
      }
    }
  }
  if (bytes[d] == END) return 1;
  return 0;
}

void printstats()
{
  SIDID *id = firstid;
  int first = 1;

  if (((identified) && (!onlyunknown)) || 
      ((unidentified) && (unknown | onlyunknown)))
  {
    printf("\n");
  }

  while (id)
  {
    if (id->count)
    {
      if (first)
      {
        printf("Detected players:\n");
        first = 0;
      }
    	printf("%-24s %d\n", id->name, id->count);
    }
    id = (SIDID *)id->next;
  }

  if (!first) printf("\n");

  printf("Statistics:\n");
  printf("Identified               %d\n", identified);
  printf("Unidentified             %d\n", unidentified);
  printf("Total files examined     %d\n", examined);
}

int ishex(char c)
{
  if ((c >= '0') && (c <= '9')) return 1;
  if ((c >= 'a') && (c <= 'f')) return 1;
  if ((c >= 'A') && (c <= 'F')) return 1;
  return 0;
}

int gethex(char c)
{
  if ((c >= '0') && (c <= '9')) return c - '0';
  if ((c >= 'a') && (c <= 'f')) return c - 'a' + 10;
  if ((c >= 'A') && (c <= 'F')) return c - 'A' + 10;
  return -1;
}
