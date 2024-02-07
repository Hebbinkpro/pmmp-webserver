<?php

namespace Hebbinkpro\WebServer\http;

/**
 * An HTTP Url which contains all url data
 */
class HttpUrl
{
    private string $scheme;
    private string $host;
    private int $port;
    private string $path;
    /** @var array<string, string> */
    private array $query;

    /**
     * @param string $scheme
     * @param string $address
     * @param int $port
     * @param string $path
     * @param array $query
     */
    public function __construct(string $scheme, string $address, int $port, string $path, array $query)
    {
        $this->scheme = $scheme;
        $this->host = $address;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
    }

    /**
     * Parse the url
     * @param string $url
     * @return HttpUrl|null
     */
    public static function parse(string $url): ?HttpUrl
    {
        // parse the url
        $urlParts = parse_url($url);
        if ($urlParts === false || !is_array($urlParts)) return null;

        // get all data from the url
        $scheme = $urlParts["scheme"] ?? "http";
        $host = $urlParts["host"] ?? "0.0.0.0";
        $port = $urlParts["port"] ?? 80;
        $path = trim(urldecode($urlParts["path"] ?? ""), "/");
        $queryString = $urlParts["query"] ?? null;


        $query = [];
        if ($queryString !== null) {
            foreach (explode("&", $queryString) as $part) {
                $part = urldecode($part);
                [$key, $value] = explode("=", $part, 2);
                $query[$key] = $value;
            }
        }

        return new HttpUrl($scheme, $host, $port, $path, $query);
    }

    /**
     * Get a string representation of this url
     * @return string
     */
    public function toString(): string
    {
        $queryParts = array_map(fn($v, $k): string => $k . "=" . $v, $this->query, array_keys($this->query));
        return $this->scheme . "://" . $this->host . ":" . $this->port . "/" . $this->path
            . (sizeof($queryParts) == 0 ? "" : "?" . implode("&", $queryParts));
    }

    /**
     * Get the HTTP scheme (http or https)
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Get the hostname
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get the path
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the query
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Get a query by its name
     * @param string $name
     * @return string|null
     */
    public function getQueryParam(string $name): ?string
    {
        return $this->query[$name] ?? null;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }
}