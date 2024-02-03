<?php

namespace Hebbinkpro\WebServer\http\request;

/**
 * All known HTTP Request Methods according to the [Mozilla web docs](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods).
 * TODO: create enum instead
 */
final class HttpRequestMethod
{
    public const ALL = "*";
    public const GET = "GET";
    public const HEAD = "HEAD";
    public const POST = "POST";
    public const PUT = "PUT";
    public const DELETE = "DELETE";
    public const CONNECT = "CONNECT";
    public const OPTIONS = "OPTIONS";
    public const TRACE = "TRACE";
    public const PATCH = "PATCH";

    /**
     * Check if the given method exists
     * @param string $method
     * @return bool
     */
    public static function exists(string $method): bool
    {
        return match ($method) {
            self::ALL, self::GET, self::HEAD, self::POST, self::PUT, self::DELETE, self::CONNECT, self::OPTIONS, self::TRACE, self::PATCH => true,
            default => false
        };
    }
}