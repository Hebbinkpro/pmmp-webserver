<?php

namespace Hebbinkpro\WebServer\http;

/**
 * Class for managing HTTP headers inside an HTTP request or response message
 */
class HttpMessageHeaders
{
    /** @var array<string, string> */
    private array $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }

    /**
     * Parse all encoded headers
     * @param string[] $data encoded headers
     * @return HttpMessageHeaders|null
     */
    public static function parse(array $data): ?HttpMessageHeaders
    {
        $headers = new HttpMessageHeaders();

        foreach ($data as $header) {
            $parts = explode(": ", $header, 2);

            if (sizeof($parts) != 2 || str_ends_with($parts[0], " ")) return null;

            $headers->setHeader($parts[0], $parts[1]);
        }

        return $headers;
    }

    /**
     * Set a header and its value
     * @param string $header
     * @param string $value
     * @return void
     */
    public function setHeader(string $header, string $value): void
    {
        $this->headers[strtolower($header)] = $value;
    }

    /**
     * Remove a header from the headers
     * @param string $header
     * @return void
     */
    public function unsetHeader(string $header): void
    {
        unset($this->headers[strtolower($header)]);
    }

    /**
     * Get the value of a header
     * @param string $header the header name
     * @param string|null $default the default value when the header is not available
     * @return string|null
     */
    public function getHeader(string $header, ?string $default = null): ?string
    {
        return $this->headers[strtolower($header)] ?? $default;
    }

    /**
     * Check if the header exists
     * @param string $header
     * @return bool
     */
    public function exists(string $header): bool
    {
        return array_key_exists(strtolower($header), $this->headers);
    }

    /**
     * Encode the request headers
     * @return string
     */
    public function toString(): string
    {
        $res = "";
        foreach ($this->headers as $name => $value) {
            $res .= $name . ": " . $value . "\r\n";
        }

        return $res;
    }
}