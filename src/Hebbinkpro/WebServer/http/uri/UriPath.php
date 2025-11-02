<?php
/*
 * MIT License
 *
 * Copyright (c) 2025 Hebbinkpro
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Hebbinkpro\WebServer\http\uri;

/**
 * A simple object representing a route path
 */
class UriPath implements UriElement
{
    /**
     * @param string[] $path
     */
    public function __construct(private array $path = [])
    {
    }

    /**
     * Parse a route path from the given string
     * @param string $path The route path
     * @return UriPath
     */
    public static function fromString(string $path): UriPath
    {
        // remove the first / to remove a starting empty value
        if (str_starts_with($path, "/")) $path = substr($path, 1);
        return new self(explode("/", $path));
    }

    public static function parse(string $value): self
    {
        $value = trim($value, "/");
        if (strlen($value) == 0) return new self([]);

        // split at / and decode each value
        $path = array_map("rawurldecode", explode("/", $value));
        return new self($path);
    }

    /**
     * Append a new path to the existing path
     * @param UriPath $path the path to add
     * @return void
     */
    public function appendPath(UriPath $path): void
    {
        $this->path += $path->getPath();
    }

    /**
     * @return string[]
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * Append a new value to the path
     * @param string $value
     * @return void
     */
    public function append(string $value): void
    {
        $this->path[] = $value;
    }

    public function toString(): string
    {
        // encode each value and create a path
        return "/" . implode("/", array_map("rawurlencode", $this->path));
    }
}