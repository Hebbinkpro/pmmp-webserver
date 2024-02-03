<?php

namespace Hebbinkpro\WebServer\http;

/**
 * HTTP Version to identify the HTTP request version of the client and to use in the response of the server.
 */
class HttpVersion
{
    private int $major;
    private int $minor;

    public function __construct(int $major, int $minor)
    {
        $this->major = $major;
        $this->minor = $minor;
    }

    /**
     * Get the default HTTP version
     * @return HttpVersion HTTP version 1.1
     */
    public static function get(): HttpVersion
    {
        return HttpVersion::fromString("HTTP/1.1");
    }

    /**
     * Decode an http version
     * @param string $version
     * @return HttpVersion|null
     */
    public static function fromString(string $version): ?HttpVersion
    {
        // invalid http request
        if (!str_contains($version, "HTTP/")) return null;

        $version = str_replace("HTTP/", "", $version);
        $parts = explode(".", $version);
        $major = intval($parts[0]);
        $minor = intval($parts[1]);

        return new HttpVersion($major, $minor);
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
     * TODO: i dont like the __toString, that can cause a lot of random nasty bugs
     * @return string
     */
    public function __toString(): string
    {
        return "HTTP/" . $this->getVersion();
    }

    /**
     * Get the http version string major.minor
     * @return string
     */
    public function getVersion(): string
    {
        return $this->major . "." . $this->minor;
    }
}