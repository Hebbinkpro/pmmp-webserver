<?php

namespace Hebbinkpro\WebServer\http;

/**
 * An HTTP Uri which contains all uri data
 */
class HttpURI
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
     * @param array<string, string> $query
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
     * Parse a request target to an URL
     * @param string $scheme
     * @param string $host
     * @param string $target
     * @return HttpURI|null
     */
    public static function parseRequestTarget(string $scheme, string $host, string $target): ?HttpURI
    {
        $schemeHost = $scheme . "://" . $host;

        // authority form or absolute form
        if (str_starts_with($target, $host) || str_starts_with($target, $schemeHost)) return self::parse($target);

        // origin form

        if ($target === "*") return self::parse($schemeHost);
        return self::parse($schemeHost . $target);
    }

    /**
     * Parse the uri
     * @param string $uri
     * @return HttpURI|null
     */
    public static function parse(string $uri): ?HttpURI
    {
        // parse the uri
        $uriParts = parse_url($uri);
        if ($uriParts === false || !is_array($uriParts)) return null;

        // get all data from the uri
        $scheme = strtolower($uriParts["scheme"] ?? HttpConstants::HTTP_SCHEME);
        $host = $uriParts["host"] ?? "0.0.0.0";
        $port = $uriParts["port"] ?? ($scheme === HttpConstants::HTTP_SCHEME ? HttpConstants::DEFAULT_HTTP_PORT : HttpConstants::DEFAULT_HTTPS_PORT);
        $path = rawurldecode(trim($uriParts["path"] ?? "", "/"));
        $queryString = rawurldecode($uriParts["query"] ?? "");

        $query = [];
        if (strlen($queryString) > 0) {
            foreach (explode("&", $queryString) as $part) {
                [$key, $value] = explode("=", $part, 2);
                $query[$key] = $value;
            }
        }

        return new HttpURI($scheme, $host, $port, $path, $query);
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
     * @return array<string, string>
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