<?php

namespace Hebbinkpro\WebServer\http\header;

class HttpHeaders
{
    /** @var string[] */
    private array $headers;

    public function __construct()
    {
        $this->headers = [];
    }

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

    public function set(string $header, string $value): void
    {
        $this->headers[$header] = $value;
    }

    public function get(string $header): string
    {
        return $this->headers[$header];
    }

    public function exists(string $header): string
    {
        return array_key_exists($header, $this->headers);
    }

    public function __toString(): string
    {
        $res = "";
        foreach ($this->headers as $name => $value) {
            $res .= $name . ": " . $value . PHP_EOL;
        }

        return $res;
    }
}