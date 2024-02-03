<?php

namespace Hebbinkpro\WebServer\http\header;

/**
 * Class for managing HTTP headers inside an HTTP request or response
 */
class HttpHeaders
{
    /** @var string[] */
    private array $headers;

    public function __construct()
    {
        $this->headers = [];
    }

    /**
     * Decode an HTTP header string to HttpHeaders
     * @param string $data
     * @return HttpHeaders
     */
    public static function fromString(string $data): HttpHeaders
    {
        $headers = new HttpHeaders();

        $lines = explode(PHP_EOL, $data);
        foreach ($lines as $line) {
            $parts = explode(": ", $line);
            if (count($parts) < 2) continue;

            $headerName = $parts[0] ?? null;
            // invalid header
            if ($headerName == null) continue;

            $headers->set($headerName, $parts[1]);
        }

        return $headers;
    }

    /**
     * Set a header and its value
     * @param string $header
     * @param string $value
     * @return void
     */
    public function set(string $header, string $value): void
    {
        $this->headers[$header] = $value;
    }

    /**
     * Get the value of a header
     * @param string $header
     * @return string|null
     */
    public function get(string $header): ?string
    {
        return $this->headers[$header] ?? null;
    }

    /**
     * Check if the header exists
     * @param string $header
     * @return string
     */
    public function exists(string $header): string
    {
        return array_key_exists($header, $this->headers);
    }

    public function __toString(): string
    {
        // TODO: why do we do this in an __toString() function and not a custom encode function??
        $res = "";
        foreach ($this->headers as $name => $value) {
            $res .= $name . ": " . $value . PHP_EOL;
        }

        return $res;
    }
}