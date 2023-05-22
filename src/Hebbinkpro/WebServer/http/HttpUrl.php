<?php

namespace Hebbinkpro\WebServer\http;

class HttpUrl
{
    private string $url;

    private string $protocol;
    private string $address;
    private int $port;
    private array $path;
    private array $query;

    public function __construct(string $url, string $protocol, string $address, int $port, array $path, array $query)
    {

        $this->url = $url;
        $this->protocol = $protocol;
        $this->address = $address;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
    }

    public static function parse(string $url): HttpUrl
    {
        // extract the protocol
        [$protocol, $urlPartsString] = explode("://", $url);

        // extract the address
        $urlParts = explode("/", $urlPartsString, 2);
        $addressString = $urlParts[0];
        $pathPartsString = $urlParts[1] ?? "";

        $addressParts = explode(":", $addressString);
        $address = $addressParts[0];
        $port = intval($addressParts[1] ?? "80");

        // separate the path and query
        $pathParts = explode("?", $pathPartsString, 2);

        $pathString = $pathParts[0];
        $path = self::parsePath($pathString);

        $queryString = $pathParts[1] ?? null;
        $queryParts = $queryString === null ? [] : explode("&", $queryString);

        $query = [];
        foreach ($queryParts as $q) {
            [$key, $value] = explode("=", $q, 2);
            $query[$key] = $value;
        }

        return new HttpUrl($url, $protocol, $address, $port, $path, $query);
    }

    public static function parsePath(string $pathString): array
    {
        $pathString = trim($pathString, "/");
        return explode("/", $pathString);
    }

    public static function getSubPath(array $path, array $parentPath): array
    {
        $realPath = str_replace("/*", "", implode("/", $path));
        $parentPath = str_replace("/*", "", implode("/", $parentPath));

        return HttpUrl::parsePath(explode($parentPath, $realPath, 2)[1]);
    }

    public function get(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return array
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }
}