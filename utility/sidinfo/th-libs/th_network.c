/*
 * Simple TCP network connection handling
 * Programmed and designed by Matti 'ccr' Hamalainen
 * (C) Copyright 2013-2018 Tecnic Software productions (TNSP)
 *
 * Please read file 'COPYING' for information on license and distribution.
 */
#include "th_network.h"
#include "th_string.h"
#include <errno.h>


static BOOL th_network_inited = FALSE;
static th_llist_t *th_conn_list = NULL;


enum
{
    SOCKS5_AUTH_NONE     = 0,
    SOCKS5_AUTH_USER     = 2,
};


enum
{
    SOCKS5_ADDR_IPV4     = 0x01,
    SOCKS5_ADDR_DOMAIN   = 0x03,
    SOCKS5_ADDR_IPV6     = 0x04,
};

static const char *th_proxy_types[] =
{
    "none",
    "SOCKS 4",
    "SOCKS 4a",
    "SOCKS 5",
    NULL
};


static const char *th_socks5_results_msgs[] =
{
    "Succeeded",
    "General SOCKS server failure",
    "Connection not allowed by ruleset",
    "Network unreachable",
    "Host unreachable",
    "Connection refused",
    "TTL expired",
    "Command not supported",
    "Address type not supported",
};

static const int th_socks5_results_msgs_num = sizeof(th_socks5_results_msgs) / sizeof(th_socks5_results_msgs[0]);



static int th_get_socket_errno(void)
{
    return errno;
}


void th_conn_err(th_conn_t *conn, int err, const char *fmt, ...)
{
    if (conn->errfunc != NULL)
    {
        char *msg;
        va_list ap;
        va_start(ap, fmt);
        msg = th_strdup_vprintf(fmt, ap);
        va_end(ap);

        conn->errfunc(conn, err, msg);
        th_free(msg);
    }
}


void th_conn_msg(th_conn_t *conn, int loglevel, const char *fmt, ...)
{
    if (conn->msgfunc != NULL)
    {
        char *msg;
        va_list ap;
        va_start(ap, fmt);
        msg = th_strdup_vprintf(fmt, ap);
        va_end(ap);

        conn->msgfunc(conn, loglevel, msg);
        th_free(msg);
    }
}


struct hostent *th_resolve_host(th_conn_t *conn, const char *name)
{
    struct hostent *res = gethostbyname(name);

    if (res == NULL)
    {
        int err = th_errno_to_error(h_errno);
        th_conn_err(conn, err,
            "Could not resolve hostname '%s': %s\n",
            name, th_error_str(err));
    }
    else
    {
        th_conn_msg(conn, THLOG_INFO, "True hostname for %s is %s\n",
            name, res->h_name);
    }

    return res;
}


BOOL th_base_conn_init(th_base_conn_t *base, ssize_t bufsize)
{
    // Allocate connection data buffer
    base->bufsize = (bufsize <= 0) ? TH_CONNBUF_SIZE : bufsize;
    if ((base->buf = th_malloc(base->bufsize)) == NULL)
        return FALSE;

    return TRUE;
}


th_conn_t * th_conn_new(
    void (*errfunc)(th_conn_t *conn, int err, const char *msg),
    void (*msgfunc)(th_conn_t *conn, int loglevel, const char *msg),
    ssize_t bufsize)
{
    th_conn_t *conn = th_malloc0(sizeof(th_conn_t));

    if (conn == NULL)
        return NULL;

    // Set function pointers
    conn->errfunc = errfunc;
    conn->msgfunc = msgfunc;

    // Do base initialization
    if (!th_base_conn_init(&conn->base, bufsize))
    {
        th_free(conn);
        return NULL;
    }

    return conn;
}


static BOOL th_get_addr(struct in_addr *addr, struct hostent *hst)
{
    if (hst != NULL)
    {
        *addr = *(struct in_addr *) (hst->h_addr_list[0]);
        return TRUE;
    }
    else
    {
        addr->s_addr = 0;
        return FALSE;
    }
}


int th_conn_set_proxy(th_conn_t *conn, int type, int port, const char *host, int auth_type)
{
    if (conn == NULL)
        return THERR_NULLPTR;

    conn->proxy.type = type;
    conn->proxy.auth_type = auth_type;

    conn->proxy.conn.port = port;
    th_free(conn->proxy.conn.host);
    conn->proxy.conn.host = th_strdup(host);

    if (host != NULL)
    {
        conn->proxy.conn.hst = th_resolve_host(conn, host);
        th_get_addr(&(conn->proxy.conn.addr), conn->proxy.conn.hst);
    }
    else
        return THERR_INVALID_DATA;

    return THERR_OK;
}


int th_conn_set_proxy_mode(th_conn_t *conn, const int mode)
{
    if (conn == NULL)
        return THERR_NULLPTR;

    conn->proxy.mode = mode;

    return THERR_OK;
}


int th_conn_set_proxy_addr_type(th_conn_t *conn, const int atype)
{
    if (conn == NULL)
        return THERR_NULLPTR;

    conn->proxy.addr_type = atype;

    return THERR_OK;
}


int th_conn_set_proxy_auth_user(th_conn_t *conn, const char *userid, const char *passwd)
{
    if (conn == NULL)
        return THERR_NULLPTR;

    th_free(conn->proxy.userid);
    conn->proxy.userid = th_strdup(userid);

    th_free(conn->proxy.passwd);
    conn->proxy.passwd = th_strdup(passwd);

    return THERR_OK;
}


static int th_conn_proxy_wait(th_conn_t *conn, const ssize_t want)
{
    int status, tries;

    for (status = TH_CONN_NO_DATA, tries = 1; tries <= 20 && status != TH_CONN_DATA_AVAIL; tries++)
    {
#ifdef TH_PLAT_WINDOWS
        Sleep(100);
#else
        usleep(100000);
#endif
        th_conn_reset(conn);
        switch (status = th_conn_pull(conn))
        {
            case TH_CONN_ERROR:
                th_conn_err(conn, status, "Proxy negotiation failed at try %d with network error: %s.\n",
                    tries, th_error_str(conn->err));
                break;

            case TH_CONN_DATA_AVAIL:
            case TH_CONN_NO_DATA:
                if (conn->base.total_bytes < want)
                    status = TH_CONN_NO_DATA;
                break;

            default:
                return status;
        }
    }

    if (status != TH_CONN_DATA_AVAIL)
        th_conn_err(conn, THERR_TIMED_OUT, "Proxy negotiation timed out.\n");

    return status;
}


static int th_conn_proxy_send(th_conn_t *conn, th_growbuf_t *buf)
{
    th_conn_reset(conn);
    return th_conn_send_growbuf(conn, buf);
}


static int th_conn_socks4_negotiate(th_conn_t *conn, const int port, const char *host)
{
    th_growbuf_t buf;
    uint8_t *ptr;
    int cmd, err = THERR_INIT_FAIL;

    (void) host;
    th_growbuf_init(&buf, 128);
    th_conn_msg(conn, THLOG_INFO, "Initializing SOCKS 4/a proxy negotiation.\n");

    switch (conn->proxy.mode)
    {
        case TH_PROXY_CMD_CONNECT: cmd = 1; break;
        case TH_PROXY_CMD_BIND: cmd = 2; break;
        default:
            err = THERR_NOT_SUPPORTED;
            th_conn_err(conn, err, "Invalid SOCKS 4 command/mode, unsupported.\n");
            goto out;
    }

    // Create SOCKS 4 handshake
    th_growbuf_put_u8(&buf, 4);  // Protocol version
    th_growbuf_put_u8(&buf, cmd); // Command
    th_growbuf_put_u16_be(&buf, port);

    switch (conn->proxy.addr_type)
    {
        case TH_PROXY_ADDR_IPV4:
            th_growbuf_put_str(&buf, (uint8_t *) &(conn->base.addr.s_addr), sizeof(conn->base.addr.s_addr));
            break;

        case TH_PROXY_ADDR_DOMAIN:
            if (conn->proxy.type == TH_PROXY_SOCKS4A)
                th_growbuf_put_u32_be(&buf, 0x00000032);
            else
            {
                err = THERR_INIT_FAIL;
                th_conn_err(conn, err,
                    "Invalid proxy settings, SOCKS 4 in use, but domain address type requested. SOCKS 4a or 5 required for host atype.\n");
                goto out;
            }
            break;

        default:
            err = THERR_INIT_FAIL;
            th_conn_err(conn, err,
                "Invalid proxy settings for SOCKS 4, unsupported address type requested.\n");
            goto out;
    }

    th_growbuf_puts(&buf, conn->proxy.userid, TRUE);
    if (conn->proxy.addr_type == TH_PROXY_ADDR_DOMAIN)
        th_growbuf_puts(&buf, host, TRUE);

    // Send request
    if ((err = th_conn_proxy_send(conn, &buf)) != THERR_OK)
        goto out;

    // Wait for SOCKS server to reply
    if (th_conn_proxy_wait(conn, 2) != TH_CONN_DATA_AVAIL)
        goto out;

    ptr = (uint8_t*) conn->base.buf;
    if (*ptr != 0)
    {
        err = THERR_INIT_FAIL;
        th_conn_err(conn, err,
            "Invalid SOCKS 4 server reply, does not begin with NUL byte (%d).\n",
            *ptr);
        goto out;
    }

    ptr++;
    if (*ptr != 0x5a)
    {
        const char *s = NULL;
        switch (*ptr)
        {
            case 0x5b: s = "Request rejected or failed"; break;
            case 0x5c: s = "Request failed because client is not running identd (or not reachable from the server)"; break;
            case 0x5d: s = "Request failed because client's identd could not confirm the user ID string in the request"; break;
            default: s = "Unknown SOCKS 4 error response"; break;
        }

        err = THERR_INIT_FAIL;
        th_conn_err(conn, err, "SOCKS 4 setup failed, 0x%02x: %s.\n", *ptr, s);
        goto out;
    }

    err = THERR_OK;

out:
    th_growbuf_free(&buf);
    return err;
}


static int th_conn_socks5_negotiate(th_conn_t *conn, const int port, const char *host)
{
    th_growbuf_t buf;
    uint8_t *ptr, authlist[16];
    int i, cmd, avail, auth, err = THERR_INIT_FAIL;

    th_growbuf_init(&buf, 256);
    th_conn_msg(conn, THLOG_INFO, "Initializing SOCKS 5 proxy negotiation.\n");

    switch (conn->proxy.mode)
    {
        case TH_PROXY_CMD_CONNECT: cmd = 1; break;
        case TH_PROXY_CMD_BIND: cmd = 2; break;
        case TH_PROXY_CMD_ASSOC_UDP: cmd = 3; break;
        default:
            err = THERR_NOT_SUPPORTED;
            th_conn_err(conn, err, "Invalid SOCKS 5 command/mode, unsupported.\n");
            goto out;
    }

    avail = 0;
    switch (conn->proxy.auth_type)
    {
        case TH_PROXY_AUTH_USER:
            authlist[avail++] = SOCKS5_AUTH_USER;
            if (conn->proxy.userid == NULL || conn->proxy.passwd == NULL)
            {
                err = THERR_INVALID_DATA;
                th_conn_err(conn, err,
                    "SOCKS 5 user authentication chosen, but no user/pass set.\n");
                goto out;
            }

            if (strlen(conn->proxy.userid) > 255 ||
                strlen(conn->proxy.passwd) > 255)
            {
                err = THERR_INVALID_DATA;
                th_conn_err(conn, err,
                    "SOCKS 5 proxy userid or password is too long.\n");
                goto out;
            }
            // Intentionally fallthrough

        case TH_PROXY_AUTH_NONE:
            authlist[avail++] = SOCKS5_AUTH_NONE;
            break;

        default:
            err = THERR_NOT_SUPPORTED;
            th_conn_err(conn, err,
                "Unsupported proxy authentication method %d.\n",
                conn->proxy.auth_type);
            goto out;
    }

    // Form handshake packet
    th_growbuf_clear(&buf);
    th_growbuf_put_u8(&buf, 0x05);   // Protocol version
    th_growbuf_put_u8(&buf, avail);  // # of available auth methods
    for (i = 0; i < avail; i++)
        th_growbuf_put_u8(&buf, authlist[i]);

    // Send request
    if ((err = th_conn_proxy_send(conn, &buf)) != THERR_OK)
        goto out;

    // Wait for SOCKS server to reply
    if (th_conn_proxy_wait(conn, 2) != TH_CONN_DATA_AVAIL)
        goto out;

    ptr = (uint8_t *) conn->base.buf;
    if (*ptr != 0x05)
    {
        err = THERR_INVALID_DATA;
        th_conn_err(conn, err,
            "Invalid SOCKS 5 server reply, does not begin with protocol version byte (%d).\n", *ptr);
        goto out;
    }
    ptr++;
    auth = *ptr;

    if (auth == 0xff)
    {
        err = THERR_NOT_SUPPORTED;
        th_conn_err(conn, err,
            "No authentication method could be negotiated with the server.\n");
        goto out;
    }
    else
    if (auth == SOCKS5_AUTH_USER)
    {
        // Attempt user/pass authentication (RFC 1929)
        th_conn_msg(conn, THLOG_INFO, "Attempting SOCKS 5 user/pass authentication.\n");
        th_growbuf_clear(&buf);

        th_growbuf_put_u8(&buf, 0x01);
        th_growbuf_put_u8(&buf, strlen(conn->proxy.userid));
        th_growbuf_puts(&buf, conn->proxy.userid, FALSE);
        th_growbuf_put_u8(&buf, strlen(conn->proxy.passwd));
        th_growbuf_puts(&buf, conn->proxy.passwd, FALSE);

        // Send request
        if ((err = th_conn_proxy_send(conn, &buf)) != THERR_OK)
            goto out;

        // Wait for SOCKS server to reply
        if (th_conn_proxy_wait(conn, 2) != TH_CONN_DATA_AVAIL)
            goto out;

        ptr = (uint8_t *) conn->base.buf;
        if (*ptr != 0x01)
        {
            err = THERR_INVALID_DATA;
            th_conn_err(conn, err,
                "Invalid SOCKS 5 server reply, does not begin with protocol version byte (%d).\n", *ptr);
            goto out;
        }
        ptr++;
        if (*ptr != 0)
        {
            err = THERR_AUTH_FAILED;
            th_conn_err(conn, err,
                "SOCKS 5 proxy user/pass authentication failed! Code 0x%02x.\n", *ptr);
            goto out;
        }
    }
    else
    if (auth == SOCKS5_AUTH_NONE)
    {
        th_conn_msg(conn, THLOG_INFO, "Using no authentication for SOCKS 5.\n");
    }
    else
    {
        err = THERR_NOT_SUPPORTED;
        th_conn_err(conn, err,
            "Proxy server chose an unsupported SOCKS 5 authentication method 0x%02x.\n",
            auth);
        goto out;
    }


    // Form client connection request packet
    th_growbuf_clear(&buf);
    th_growbuf_put_u8(&buf, 0x05); // Protocol version
    th_growbuf_put_u8(&buf, cmd);  // Command
    th_growbuf_put_u8(&buf, 0x00); // Reserved

    switch (conn->proxy.addr_type)
    {
        case TH_PROXY_ADDR_IPV4:
            th_growbuf_put_u8(&buf, SOCKS5_ADDR_IPV4);
            th_growbuf_put_str(&buf, (uint8_t *) &(conn->base.addr.s_addr), sizeof(conn->base.addr.s_addr));
            break;

        case TH_PROXY_ADDR_IPV6:
            th_growbuf_put_u8(&buf, SOCKS5_ADDR_IPV6);
            //th_growbuf_put_str(&buf, (uint8_t *) &(conn->base.addr.s_addr), sizeof(conn->base.addr.s_addr));
            break;

        case TH_PROXY_ADDR_DOMAIN:
            cmd = strlen(host);
            if (cmd < 1 || cmd > 255)
            {
                err = THERR_NOT_SUPPORTED;
                th_conn_err(conn, err,
                    "Domain address type requested, but domain name longer than 255 characters (%d).\n", cmd);
                goto out;
            }

            th_growbuf_put_u8(&buf, SOCKS5_ADDR_DOMAIN);
            th_growbuf_put_u8(&buf, cmd);
            th_growbuf_put_str(&buf, host, cmd);
            break;
    }

    th_growbuf_put_u16_be(&buf, port);

    // Send request
    if ((err = th_conn_proxy_send(conn, &buf)) != THERR_OK)
        goto out;

    // Wait for SOCKS server to reply
    if (th_conn_proxy_wait(conn, 3) != TH_CONN_DATA_AVAIL)
        goto out;

    ptr = (uint8_t *) conn->base.buf;
    if (*ptr != 0x05)
    {
        err = THERR_INVALID_DATA;
        th_conn_err(conn, err,
            "Invalid SOCKS 5 server reply, does not begin with protocol version byte (%d).\n", *ptr);
        goto out;
    }
    ptr++;
    if (*ptr != 0)
    {
        err = THERR_INIT_FAIL;
        if (*ptr < th_socks5_results_msgs_num)
            th_conn_err(conn, err, "SOCKS 5 error: %s.\n", th_socks5_results_msgs[*ptr]);
        else
            th_conn_err(conn, err, "Unknown SOCKS 5 result code 0x02x.\n", *ptr);
        goto out;
    }
    ptr++;
    if (*ptr != 0)
    {
        err = THERR_INVALID_DATA;
        th_conn_err(conn, err, "Invalid reply from SOCKS 5 server: expected 0x00, got 0x%02x.\n", *ptr);
        goto out;
    }

    err = THERR_OK;

out:
    th_growbuf_free(&buf);
    return err;
}


int th_conn_open(th_conn_t *conn, const int port, const char *host)
{
    struct sockaddr_in dest;
    int err = THERR_INIT_FAIL;

    if (conn == NULL)
        return THERR_NULLPTR;

    conn->base.port = port;
    conn->base.host = th_strdup(host);
    conn->base.hst = th_resolve_host(conn, host);

    // If name resolving locally fails, force to domain addr type
    if (conn->base.hst == NULL)
        conn->proxy.addr_type = TH_PROXY_ADDR_DOMAIN;

    th_get_addr(&(conn->base.addr), conn->base.hst);

    // Prepare for connection
    dest.sin_family = AF_INET;

    if (conn->proxy.type > TH_PROXY_NONE && conn->proxy.type < TH_PROXY_LAST)
    {
        // If using a proxy, we connect to the proxy server
        dest.sin_port = htons(conn->proxy.conn.port);
        dest.sin_addr = conn->proxy.conn.addr;

        th_conn_msg(conn, THLOG_INFO, "Connecting to %s proxy %s:%d ...\n",
            th_proxy_types[conn->proxy.type],
            inet_ntoa(conn->proxy.conn.addr), conn->proxy.conn.port);
    }
    else
    {
        dest.sin_port = htons(conn->base.port);
        dest.sin_addr = conn->base.addr;

        th_conn_msg(conn, THLOG_INFO, "Connecting to %s:%d ...\n",
            inet_ntoa(conn->base.addr), conn->base.port);
    }

    if ((conn->base.socket = socket(PF_INET, SOCK_STREAM, 0)) == -1)
    {
        err = th_errno_to_error(th_get_socket_errno());
        th_conn_err(conn, err, "Could not open socket: %s\n", th_error_str(err));
        goto error;
    }

    if (connect(conn->base.socket, (struct sockaddr *) &dest, sizeof(dest)) == -1)
    {
        err = th_errno_to_error(th_get_socket_errno());
        th_conn_err(conn, err, "Could not connect: %s\n", th_error_str(err));
        goto error;
    }

    FD_ZERO(&(conn->base.sockfds));
    FD_SET(conn->base.socket, &(conn->base.sockfds));

    // Proxy-specific setup
    switch (conn->proxy.type)
    {
        case TH_PROXY_SOCKS4:
        case TH_PROXY_SOCKS4A:
            if ((err = th_conn_socks4_negotiate(conn, port, host)) != THERR_OK)
                goto error;
            th_conn_msg(conn, THLOG_INFO, "SOCKS 4 connection established!\n");
            break;

        case TH_PROXY_SOCKS5:
            if ((err = th_conn_socks5_negotiate(conn, port, host)) != THERR_OK)
                goto error;
            th_conn_msg(conn, THLOG_INFO, "SOCKS 5 connection established!\n");
            break;
    }

    th_conn_reset(conn);
    conn->status = TH_CONN_OPEN;

    // Insert to connection list
    conn->node = th_llist_append(&th_conn_list, conn);

    return THERR_OK;

error:
    th_conn_close(conn);
    return err;
}


int th_conn_close(th_conn_t *conn)
{
    if (conn == NULL)
        return -1;

    if (conn->base.socket >= 0)
    {
#ifdef TH_PLAT_WINDOWS
        closesocket(conn->base.socket);
#else
        close(conn->base.socket);
#endif
        conn->base.socket = -1;
    }

    conn->status = TH_CONN_CLOSED;
    return 0;
}


static void th_conn_free_nodelete(th_conn_t *conn)
{
    th_free(conn->base.buf);
    th_conn_close(conn);
    th_free(conn->base.host);
    th_free(conn->proxy.conn.host);
    th_free(conn);
}


void th_conn_free(th_conn_t *conn)
{
    if (conn != NULL)
    {
        // Remove from linked list
        if (conn->node != NULL)
            th_llist_delete_node_fast(&th_conn_list, conn->node);

        // Free connection data
        th_conn_free_nodelete(conn);
    }
}


int th_conn_send_buf(th_conn_t *conn, const void *buf, const size_t len)
{
    size_t bufLeft = len;
    const char *bufPtr = (char *) buf;

    while (bufLeft > 0)
    {
        ssize_t bufSent = send(conn->base.socket, bufPtr, bufLeft, 0);
        if (bufSent < 0)
        {
            int err = th_errno_to_error(th_get_socket_errno());
            th_conn_err(conn, err, "th_conn_send_buf() failed: %s", th_error_str(err));
            return err;
        }
        bufLeft -= bufSent;
        bufPtr += bufSent;
    }

    return THERR_OK;
}


int th_conn_send_growbuf(th_conn_t *conn, th_growbuf_t *buf)
{
    int ret = th_conn_send_buf(conn, buf->data, buf->len);
    th_growbuf_clear(buf);
    return ret;
}


void th_conn_reset(th_conn_t *conn)
{
    if (conn != NULL)
    {
        conn->base.ptr = conn->base.in_ptr = conn->base.buf;
        conn->base.got_bytes = conn->base.total_bytes = 0;
    }
}


int th_conn_pull(th_conn_t *conn)
{
    int result;
    struct timeval socktv;
    fd_set tmpfds;

    if (conn == NULL)
        return TH_CONN_ERROR;

    // Shift the input buffer
    if (conn->base.ptr > conn->base.buf)
    {
        size_t left = conn->base.in_ptr - conn->base.ptr;
        if (left > 0)
        {
            size_t moved = conn->base.ptr - conn->base.buf;
            memmove(conn->base.buf, conn->base.ptr, left);
            conn->base.ptr = conn->base.buf;
            conn->base.in_ptr -= moved;
            conn->base.total_bytes -= moved;
        }
        else
            th_conn_reset(conn);
    }

    // Check for incoming data
    socktv.tv_sec = 0;
    socktv.tv_usec = TH_DELAY_USEC;
    tmpfds = conn->base.sockfds;

    if ((result = select(conn->base.socket + 1, &tmpfds, NULL, NULL, &socktv)) == -1)
    {
        int err = th_get_socket_errno();
        if (err != EINTR)
        {
            err = th_errno_to_error(err);
            th_conn_err(conn, err, "Error occured in select(%d, sockfds): %s\n",
                socket, th_error_str(err));
            return TH_CONN_ERROR;
        }
    }
    else if (FD_ISSET(conn->base.socket, &tmpfds))
    {
        conn->base.got_bytes = recv(conn->base.socket,
            conn->base.in_ptr, conn->base.bufsize - conn->base.total_bytes, 0);

        if (conn->base.got_bytes < 0)
        {
            int err = th_errno_to_error(th_get_socket_errno());
            th_conn_err(conn, err, "Error in recv: %s\n", th_error_str(err));
            return TH_CONN_ERROR;
        }
        else if (conn->base.got_bytes == 0)
        {
            th_conn_err(conn, ECONNABORTED, "Server closed connection.\n");
            conn->status = TH_CONN_CLOSED;
            return TH_CONN_CLOSED;
        }
        else
        {
            conn->base.total_bytes += conn->base.got_bytes;
            conn->base.in_ptr += conn->base.got_bytes;
            return TH_CONN_DATA_AVAIL;
        }
    }

    return TH_CONN_NO_DATA;
}


BOOL th_conn_check(th_conn_t *conn)
{
    if (conn == NULL)
        return FALSE;

    return conn->err == 0 && conn->status == TH_CONN_OPEN;
}


int th_network_init(void)
{
#ifdef TH_PLAT_WINDOWS
    // Initialize WinSock, if needed
    WSADATA wsaData;
    int err = WSAStartup(0x0101, &wsaData);
    if (err != 0)
    {
        THERR("Could not initialize WinSock library (err=%d).\n", err);
        return THERR_INIT_FAIL;
    }
#endif

    th_network_inited = TRUE;

    th_conn_list = NULL;

    return THERR_OK;
}


void th_network_close(void)
{
    if (th_network_inited)
    {
        // Close connections
        th_llist_t *curr = th_conn_list;
        while (curr != NULL)
        {
            th_llist_t *next = curr->next;
            th_conn_free_nodelete(curr->data);
            curr = next;
        }

#ifdef TH_PLAT_WINDOWS
        WSACleanup();
#endif
    }

    th_network_inited = FALSE;
}


BOOL th_conn_buf_check(th_conn_t *conn, size_t n)
{
    return conn && (conn->base.ptr + n <= conn->base.in_ptr);
}


BOOL th_conn_buf_skip(th_conn_t *conn, size_t n)
{
    if (th_conn_buf_check(conn, n))
    {
        conn->base.ptr += n;
        return TRUE;
    }
    else
        return FALSE;
}


int th_conn_buf_strncmp(th_conn_t *conn, const char *str, const size_t n)
{
    int ret;
    if (!th_conn_buf_check(conn, n))
        return -1;

    if ((ret = strncmp(conn->base.ptr, str, n)) == 0)
    {
        conn->base.ptr += n;
        return 0;
    }
    else
        return ret;
}


int th_conn_buf_strcmp(th_conn_t *conn, const char *str)
{
    return th_conn_buf_strncmp(conn, str, strlen(str));
}


char *th_conn_buf_strstr(th_conn_t *conn, const char *str)
{
    char *pos;
    size_t n = strlen(str);

    if (th_conn_buf_check(conn, n) && ((pos = strstr(conn->base.ptr, str)) != NULL))
    {
        conn->base.ptr = pos + n;
        return pos;
    }
    else
        return NULL;
}


void th_conn_dump_buffer(FILE *f, th_conn_t *conn)
{
    char *p;
    size_t offs, left;

    fprintf(f,
    "\n--------------------------------------------------------------\n"
    "err=%d, status=%d, got_bytes=%" PRIu_SIZE_T ", total_bytes=%" PRIu_SIZE_T "\n"
    "buf=0x%p, in_ptr=0x%04" PRIx_SIZE_T ", ptr=0x%04" PRIx_SIZE_T "\n",
    conn->err, conn->status, conn->base.got_bytes, conn->base.total_bytes,
    conn->base.buf, conn->base.in_ptr - conn->base.buf, conn->base.ptr - conn->base.buf);

    // Dump buffer contents as a hexdump
    for (offs = 0, left = conn->base.total_bytes, p = conn->base.buf; p < conn->base.in_ptr;)
    {
        char buf[TH_DUMP_BYTES + 1];
        size_t bufoffs, amount = left < TH_DUMP_BYTES ? left : TH_DUMP_BYTES;
        left -= amount;

        // Dump offs | xx xx xx xx | and fill string
        fprintf(f, "%04" PRIx_SIZE_T " | ", offs);
        for (bufoffs = 0; bufoffs < amount; offs++, bufoffs++, p++)
        {
            fprintf(f, "%02x ", *p);
            buf[bufoffs] = th_isprint(*p) ? *p : '.';
        }
        buf[bufoffs] = 0;

        // Add padding
        for (; bufoffs < TH_DUMP_BYTES; bufoffs++)
            fprintf(f, "   ");

        // Print string
        fprintf(f, "| %s\n", buf);
    }
}
