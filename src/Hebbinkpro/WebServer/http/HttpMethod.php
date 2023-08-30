<?php

namespace Hebbinkpro\WebServer\http;

final class HttpMethod
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

    public static function exists(string $method): bool
    {
        return match ($method) {
            self::ALL, self::GET, self::HEAD, self::POST, self::PUT, self::DELETE, self::CONNECT, self::OPTIONS, self::TRACE, self::PATCH => true,
            default => false
        };
    }
}