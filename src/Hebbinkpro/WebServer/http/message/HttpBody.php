<?php
/*
 * MIT License
 *
 * Copyright (c) 2025-2026 Hebbinkpro
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

namespace Hebbinkpro\WebServer\http\message;


use Exception;
use Hebbinkpro\WebServer\utils\StreamUtils;
use InvalidArgumentException;
use ValueError;


readonly class HttpBody
{
    /** @var resource */
    private mixed $stream;

    /**
     * @param resource $stream the body as a file stream
     */
    public function __construct(mixed $stream)
    {
        if (!StreamUtils::isStream($stream)) {
            throw new InvalidArgumentException("Resource is not a file stream");
        }

        if (!StreamUtils::isReadable($stream)) {
            throw new ValueError("Resource is not readable");
        }

        $this->stream = $stream;
    }

    /**
     * Get the length of the body in bytes
     * @return int
     */
    public function getLength(): int
    {
        return fstat($this->stream)['size'];
    }

    /**
     * Read a chunk of the body
     * @param int $length
     * @return string
     */
    public function read(int $length): string
    {
        return fread($this->stream, $length);
    }

    /**
     * If the stream is at the end of the body
     * @return bool
     */
    public function eof(): bool
    {
        return feof($this->stream);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close the stream
     *
     * - This makes the stream unusable for further I/O operations.
     * @return void
     */
    public function close(): void
    {
        try {
            fclose($this->stream);
        } catch (Exception $e) {
            // already closed
        }
    }

    /**
     * Returns the entire body as a string
     * @return string
     */
    public function __toString(): string
    {
        // ensures that the entire stream is read, and the pointer is returned to its original position
        $ptr = $this->tell();
        $this->rewind();
        $body = $this->readAll();
        $this->seek($ptr);
        return $body;
    }

    /**
     * Get the current position of the stream
     * @return int
     */
    public function tell(): int
    {
        return ftell($this->stream);
    }

    /**
     * Rewind the stream to the beginning of the body
     * @return void
     */
    public function rewind(): void
    {
        rewind($this->stream);
    }

    /**
     * Read the remaining body
     * @return string
     */
    public function readAll(): string
    {
        return stream_get_contents($this->stream);
    }

    /**
     * Seek to a position in the body (See `fseek`)
     * @param int $offset The offset relative to $whence
     * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END
     * @return void
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        fseek($this->stream, $offset, $whence);
    }

    /**
     * Decodes the body as JSON using `json_decode`
     * @param int $depth User specified recursion depth
     * @param int $flags Bitmask of JSON decode options
     * @return mixed the decoded body or `null` on failure
     */
    public function asJson(int $depth = 512, int $flags = 0): mixed
    {
        return json_decode($this->readAll(), true, $depth, $flags);
    }
}