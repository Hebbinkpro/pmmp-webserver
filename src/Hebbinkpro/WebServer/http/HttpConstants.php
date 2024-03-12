<?php

namespace Hebbinkpro\WebServer\http;

/**
 * All constants used for this implementation of HTTP/1.1
 */
final class HttpConstants
{
    /** @var int Maximum amount of bytes to read from a socket stream at once */
    public const MAX_STREAM_READ_LENGTH = 8192;

    /** @var int Recommended byte length for the request line */
    public const MAX_REQUEST_LINE_LENGTH = 8000;

    public const DEFAULT_HTTP_PORT = 80;

    public const DEFAULT_HTTPS_PORT = 443;

    public const HTTP_SCHEME = "http";
    public const HTTPS_SCHEME = "https";
}