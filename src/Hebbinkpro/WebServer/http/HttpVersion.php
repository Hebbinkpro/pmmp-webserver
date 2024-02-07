<?php

namespace Hebbinkpro\WebServer\http;

/**
 * HTTP Version to identify the HTTP request version of the client and to use in the response of the server.
 */
class HttpVersion
{
    public const DEFAULT_MAJOR = 1;
    public const DEFAULT_MINOR = 1;

    /**
     * @param int $major major HTTP version
     * @param int $minor minor HTTP version
     */
    public function __construct(private readonly int $major, private readonly int $minor)
    {
    }

    /**
     * Decode an http version
     * @param string $version
     * @return HttpVersion|null
     */
    public static function fromString(string $version): ?HttpVersion
    {
        if ($version === "undefined") return self::getDefault();

        // invalid http request
        if (!str_starts_with($version, "HTTP/")) return null;

        // get major and minor versions
        [$major, $minor] = explode(".", substr($version, 5));

        // check if the integers are valid
        if (!ctype_digit($major) || !ctype_digit($minor)) return null;


        return new HttpVersion(intval($major), intval($minor));
    }

    /**
     * Get the default HTTP version
     * @return HttpVersion HTTP/1.1
     */
    public static function getDefault(): HttpVersion
    {
        return new HttpVersion(self::DEFAULT_MAJOR, self::DEFAULT_MINOR);
    }

    /**
     * @return int
     */
    public function getMajorVersion(): int
    {
        return $this->major;
    }

    /**
     * @return int
     */
    public function getMinorVersion(): int
    {
        return $this->minor;
    }

    /**
     * Get the encoded HTTP version
     * @return string HTTP/major.minor
     */
    public function toString(): string
    {
        return "HTTP/" . $this->major . "." . $this->minor;
    }
}