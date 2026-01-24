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


class HttpMessageBody
{
    /** @var resource */
    private mixed $stream;

    private int $length;

    /**
     * @param resource $stream the body as a stream
     * @param int $length the length of the body in bytes
     */
    public function __construct(mixed $stream, int $length)
    {
        $this->stream = $stream;
        $this->length = $length;
    }

    /**
     * Get the body stream
     * @return resource
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }

    /**
     * Get the length of the body in bytes
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Write the body to a stream
     * @param resource $to the stream to write to
     * @param int|null $length the maximum number of bytes to write, or null to write the entire body
     * @return void
     */
    public function write(mixed $to, ?int $length = null): void
    {
        $this->writeBody($to, $length);
    }

    /**
     * Write the body to a stream
     * @param mixed $to the stream to write to
     * @param int|null $length the maximum number of bytes to write, or null to write the entire body
     * @return void
     */
    protected function writeBody(mixed $to, ?int $length): void
    {
        // clamp the length to the body length
        if ($length === null) $length = $this->length;
        else $length = min($length, $this->length);

        // copy bytes directly to the stream
        stream_copy_to_stream($this->stream, $to, $length);
    }
}