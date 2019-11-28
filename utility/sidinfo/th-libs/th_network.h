/*
 * Simple TCP network connection handling
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2013-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#ifndef TH_NETWORK_H
#define TH_NETWORK_H

#include <stdio.h>
#include <unistd.h>
#include "th_types.h"
#include "th_datastruct.h"
#include "th_util.h"


#ifdef TH_PLAT_WINDOWS
#    define __OBJC_BOOL // A nasty hack
#    include <windows.h>
#    include <winsock.h>
typedef uint16_t in_port_t;
typedef uint32_t in_addr_t;
#else
#    include <sys/select.h>
#    include <sys/socket.h>
#    ifdef HAVE_NETINET_IN_H
#        include <netinet/in.h>
#    endif
#    include <arpa/inet.h>
#    include <netdb.h>
#endif


#ifdef __cplusplus
extern "C" {
#endif


/* Global defines
 */
#define TH_CONNBUF_SIZE   (64 * 1024)
#define TH_DELAY_USEC     (15 * 1000)
#define TH_DUMP_BYTES	  16


enum
{
    TH_CONN_UNINIT = 0,
    TH_CONN_PROXY_NEG,
    TH_CONN_OPEN,
    TH_CONN_CLOSED,

    TH_CONN_ERROR,
    TH_CONN_DATA_AVAIL,
    TH_CONN_NO_DATA,
};

enum
{
    TH_PROXY_NONE = 0,
    TH_PROXY_SOCKS4,
    TH_PROXY_SOCKS4A,
    TH_PROXY_SOCKS5,

    TH_PROXY_LAST
};

enum
{
    TH_PROXY_ADDR_IPV4 = 0,
    TH_PROXY_ADDR_DOMAIN,
    TH_PROXY_ADDR_IPV6,
};

enum
{
    TH_PROXY_AUTH_NONE,
    TH_PROXY_AUTH_USER,
};

enum
{
    TH_PROXY_CMD_CONNECT = 1,
    TH_PROXY_CMD_BIND = 2,
    TH_PROXY_CMD_ASSOC_UDP = 3,
};


typedef struct _th_base_conn_t
{
    // Target host data
    char *host;
    struct hostent *hst;
    int port;

    // Socket data
    int socket;
    struct in_addr addr;
    fd_set sockfds;

    // Data buffer
    char *buf, *ptr, *in_ptr;
    ssize_t bufsize, got_bytes, total_bytes;

} th_base_conn_t;


typedef struct _th_conn_t
{
    // Connection
    th_base_conn_t base;

    // Proxy settings and data
    struct
    {
        th_base_conn_t conn;
        int type, auth_type;
        int mode, addr_type;
        char *userid, *passwd;
    } proxy;

    // Status
    int err;
    int status;

    // Error handling and status message functors
    void (*errfunc)(struct _th_conn_t *conn, int err, const char *msg);
    void (*msgfunc)(struct _th_conn_t *conn, int loglevel, const char *msg);

    void *node;
} th_conn_t;


int         th_network_init();
void        th_network_close(void);

struct hostent *th_resolve_host(th_conn_t *conn, const char *name);
th_conn_t * th_conn_new(
    void (*errfunc)(th_conn_t *conn, int err, const char *msg),
    void (*msgfunc)(th_conn_t *conn, int loglevel, const char *msg),
    ssize_t bufsize);

void        th_conn_err(th_conn_t *conn, int err, const char *fmt, ...);
void        th_conn_msg(th_conn_t *conn, int loglevel, const char *fmt, ...);

int         th_conn_set_proxy(th_conn_t *conn, int type, int port, const char *host, int auth_type);
int         th_conn_set_proxy_mode(th_conn_t *conn, const int mode);
int         th_conn_set_proxy_addr_type(th_conn_t *conn, const int atype);
int         th_conn_set_proxy_auth_user(th_conn_t *conn, const char *userid, const char *passwd);

int         th_conn_open(th_conn_t *conn, const int port, const char *host);
int         th_conn_close(th_conn_t *);
void        th_conn_free(th_conn_t *);
void        th_conn_reset(th_conn_t *);

int         th_conn_pull(th_conn_t *);
int         th_conn_send_buf(th_conn_t *, const void *buf, const size_t len);
int         th_conn_send_growbuf(th_conn_t *, th_growbuf_t *buf);
BOOL        th_conn_check(th_conn_t *);


BOOL        th_conn_buf_check(th_conn_t *conn, size_t n);
BOOL        th_conn_buf_skip(th_conn_t *conn, size_t n);
int         th_conn_buf_strncmp(th_conn_t *conn, const char *str, const size_t n);
int         th_conn_buf_strcmp(th_conn_t *conn, const char *str);
char *      th_conn_buf_strstr(th_conn_t *conn, const char *str);

void        th_conn_dump_buffer(FILE *f, th_conn_t *conn);


#ifdef __cplusplus
}
#endif
#endif // TH_NETWORK_H
