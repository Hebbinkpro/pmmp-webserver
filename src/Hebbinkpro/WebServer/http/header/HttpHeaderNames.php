<?php

namespace Hebbinkpro\WebServer\http\header;

/**
 * All known HTTP Headers according to the [Mozilla web docs](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers).
 */
final class HttpHeaderNames
{

    // authentication
    public const WWW_AUTHENTICATE = "WWW-Authenticate";
    public const AUTHORIZATION = "Authorization";
    public const PROXY_AUTHENTICATE = "Proxy-Authenticate";
    public const PROXY_AUTHORIZATION = "Proxy-Authorization";

    // caching
    public const AGE = "Age";
    public const CACHE_CONTROL = "Cache-Control";
    public const CLEAR_SITE_DATA = "Clear-Site-Data";
    public const EXPIRES = "Expires";
    public const PRAGMA = "Pragma";
    public const WARNING = "Warning";

    // client hints
    public const ACCEPT_CH = "Accept-CH";
    public const ACCEPT_CH_LIFETIME = "Accept-CH-Lifetime";
    public const CRITICAL_CH = "Critical-CH";

    // - user agent client hints
    public const SEC_CH_PREFERS_REDUCED_MOTION = "Sec-CH-UA-Prefers-Reduced-Motion";
    public const SEC_CH_UA = "Sec-CH-UA";
    public const SEC_CH_UA_ARCH = "Sec-CH-UA-Arch";
    public const SEC_CH_UA_BITNESS = "Sec-CH-UA-Bitness";
    public const SEC_CH_UA_FULL_VERSION = "Sec-CH-UA-Full-Version";
    public const SEC_CH_UA_FULL_VERSION_LIST = "Sec-CH-UA-Full-Version-List";
    public const SEC_CH_UA_MOBILE = "Sec-CH-UA-Mobile";
    public const SEC_CH_UA_MODEL = "Sec-CH-UA-Model";
    public const SEC_CH_UA_PLATFORM = "Sec-CH-UA-Platform";
    public const SEC_CH_UA_PLATFORM_VERSION = "Sec-CH-UA-Platform-Version";

    // - device client hints
    public const CONTENT_DPR = "Content-DPR";
    public const DEVICE_MEMORY = "Device-Memory";
    public const DPR = "DPR";
    public const VIEWPORT_WIDTH = "Viewport-Width";
    public const WIDTH = "Width";

    // - network client hints
    public const DOWNLINK = "Downlink";
    public const ECT = "ECT";
    public const RTT = "RTT";
    public const SAVE_DATA = "Save-Data";

    // conditionals
    public const LAST_MODIFIED = "Last-Modified";
    public const ETAG = "ETag";
    public const IF_MATCH = "If-Match";
    public const IF_NONE_MATCH = "If-None-Match";
    public const IF_MODIFIED_SINCE = "If-Modified-Since";
    public const IF_UNMODIFIED_SINCE = "If-Unmodified-Since";
    public const VARY = "Vary";

    // connection management
    public const CONNECTION = "Connection";
    public const KEEP_ALIVE = "Keep-Alive";

    // content negotiation
    public const ACCEPT = "Accept";
    public const ACCEPT_ENCODING = "Accept-Encoding";
    public const ACCEPT_LANGUAGE = "Accept-Language";

    // controls
    public const EXPECT = "Expect";
    public const MAX_FORWARDS = "Max-Forwards";

    // cookies
    public const COOKIE = "Cookie";
    public const SET_COOKIE = "Set-Cookie";

    // cors
    public const ACCESS_CONTROL_ALLOW_ORIGIN = "Access-Control-Allow-Origin";
    public const ACCESS_CONTROL_ALLOW_CREDENTIALS = "Access-Control-Allow-Credentials";
    public const ACCESS_CONTROL_ALLOW_HEADERS = "Access-Control-Allow-Headers";
    public const ACCESS_CONTROL_ALLOW_METHODS = "Access-Control-Allow-Methods";
    public const ACCESS_CONTROL_EXPOSE_HEADERS = "Access-Control-Expose-Headers";
    public const ACCESS_CONTROL_MAX_AGE = "Access-Control-Max-Age";
    public const ACCESS_CONTROL_REQUEST_HEADERS = "Access-Control-Request-Headers";
    public const ACCESS_CONTROL_REQUEST_METHOD = "Access-Control-Request-Method";
    public const ORIGIN = "Origin";
    public const TIMING_ALLOW_ORIGIN = "Timing-Allow-Origin";

    // downloads
    public const CONTENT_DISPOSITION = "Content-Disposition";

    // message body information
    public const CONTENT_LENGTH = "Content-Length";
    public const CONTENT_TYPE = "Content-Type";
    public const CONTENT_ENCODING = "Content-Encoding";
    public const CONTENT_LANGUAGE = "Content-Language";
    public const CONTENT_LOCATION = "Content-Location";

    // proxies
    public const FORWARDED = "Forwarded";
    public const X_FORWARDED_FOR = "X-Forwarded-For";
    public const X_FORWARDED_HOST = "X-Forwarded-Host";
    public const X_FORWARDED_PROTO = "X-Forwarded-Proto";
    public const VIA = "Via";

    // redirects
    public const LOCATION = "Location";
    public const REFRESH = "Refresh";

    // request context
    public const FROM = "From";
    public const HOST = "Host";
    public const REFERER = "Referer";
    public const REFERRER_POLICY = "Referrer-Policy";
    public const USER_AGENT = "User-Agent";

    // response context
    public const ALLOW = "Allow";
    public const SERVER = "Server";

    // range request
    public const ACCEPT_RANGES = "Accept-Ranges";
    public const RANGE = "Range";
    public const IF_RANGE = "If-Range";
    public const CONTENT_RANGE = "Content-Range";

    // security
    public const CROSS_ORIGIN_EMBEDDER_POLICY = "Cross-Origin-Embedder-Policy";
    public const CROSS_ORIGIN_OPENER_POLICY = "Cross-Origin-Opener-Policy";
    public const CROSS_ORIGIN_RESOURCE_POLICY = "Cross-Origin-Resource-Policy";
    public const CONTENT_SECURITY_POLICY = "Content-Security-Policy";
    public const CONTENT_SECURITY_POLICY_REPORT_ONLY = "Content-Security-Policy-Report-Only";
    public const EXPECT_CT = "Expect-CT";
    public const ORIGIN_ISOLATION = "Origin-Isolation";
    public const PERMISSIONS_POLICY = "Permissions-Policy";
    public const STRICT_TRANSPORT_SECURITY = "Strict-Transport-Security";
    public const UPGRADE_INSECURE_REQUESTS = "Upgrade-Insecure-Requests";
    public const X_CONTENT_TYPE_OPTIONS = "X-Content-Type-Options";
    public const X_FRAME_OPTIONS = "X-Frame-Options";
    public const X_PERMITTED_CROSS_DOMAIN_POLICIES = "X-Permitted-Cross-Domain-Policies";
    public const X_POWERED_BY = "X-Powered-By";
    public const X_XSS_PROTECTION = "X-XSS-Protection";

    // - Fetch metadata request headers
    public const SEC_FETCH_SITE = "Sec-Fetch-Site";
    public const SEC_FETCH_MODE = "Sec-Fetch-Mode";
    public const SEC_FETCH_USER = "Sec-Fetch-User";
    public const SEC_FETCH_DEST = "Sec-Fetch-Dest";
    public const SERVICE_WORKER_NAVIGATION_PRELOAD = "Service-Worker-Navigation-Preload";

    // server-sent events
    public const LAST_EVENT_ID = "Last-Event-ID";
    public const NEL = "NEL";
    public const PING_FROM = "Ping-From";
    public const PING_TO = "Ping-To";
    public const REPORT_TO = "Report-To";

    // transfer coding
    public const TRANSFER_ENCODING = "Transfer-Encoding";
    public const TE = "TE";
    public const TRAILER = "Trailer";

    // websockets
    public const SEC_WEBSOCKET_KEY = "Sec-WebSocket-Key";
    public const SEC_WEBSOCKET_EXTENSIONS = "Sec-WebSocket-Extensions";
    public const SEC_WEBSOCKET_ACCEPT = "Sec-WebSocket-Accept";
    public const SEC_WEBSOCKET_PROTOCOL = "Sec-WebSocket-Protocol";
    public const SEC_WEBSOCKET_VERSION = "Sec-WebSocket-Version";

    // other
    public const ACCEPT_PUSH_POLICY = "Accept-Push-Policy";
    public const ACCEPT_SIGNATURE = "Accept-Signature";
    public const ALT_SVC = "Alt-Svc";
    public const DATE = "Date";
    public const EARLY_DATE = "Early-Date";
    public const LARGE_ALLOCATION = "Large-Allocation";
    public const LINK = "Link";
    public const PUSH_POLICY = "Push-Policy";
    public const RETRY_AFTER = "Retry-After";
    public const SIGNATURE = "Signature";
    public const SIGNED_HEADERS = "Signed-Headers";
    public const SERVER_TIMING = "Server-Timing";
    public const SERVICE_WORKER_ALLOWED = "Service-Worker-Allowed";
    public const SOURCE_MAP = "SourceMap";
    public const UPGRADE = "Upgrade";
    public const X_DNS_PREFETCH_CONTROL = "X-DNS-Prefetch-Control";
    public const X_FIREFOX_SPDY = "X-Firefox-Spdy";
    public const X_PINGBACK = "X-Pingback";
    public const X_REQUESTED_WITH = "X-Requested-With";
    public const X_ROBOTS_TAG = "X-Robots-Tag";
}