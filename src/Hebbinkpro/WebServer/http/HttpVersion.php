<?php

namespace Hebbinkpro\WebServer\http;

class HttpVersion
{
    private readonly int $major;
    private readonly int $minor;

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

    public static function fromString(string $version): HttpVersion
    {
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

    public function __toString(): string
    {
        return "HTTP/" . $this->getVersion();
    }

    public function getVersion(): string
    {
        return $this->major . "." . $this->minor;
    }
}