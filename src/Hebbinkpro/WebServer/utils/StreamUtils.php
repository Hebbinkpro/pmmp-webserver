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

namespace Hebbinkpro\WebServer\utils;

final class StreamUtils
{
    /**
     * Get if the resource is a readable stream
     * @param mixed $stream
     * @return bool
     * @phpstan-assert-if-true resource $stream
     */
    public static function isReadable(mixed $stream): bool
    {
        return self::hasMode($stream, "r");
    }

    /**
     * Get if the resource is a stream and has the specified mode
     * @param mixed $stream the resource to check
     * @param string $mode the mode to check for
     * @return bool if the resource is a stream and has the specified mode
     * @phpstan-assert-if-true resource $stream
     */
    public static function hasMode(mixed $stream, string $mode): bool
    {
        return self::isStream($stream) && stream_get_meta_data($stream)["mode"] === $mode;
    }

    /**
     * Get if a resource is a stream
     * @param resource $stream the resource to check
     * @return bool if the provided resource is a stream
     * @phpstan-assert-if-true resource $stream
     */
    public static function isStream(mixed $stream): bool
    {
        return is_resource($stream) && get_resource_type($stream) === "stream";
    }

    /**
     * Get if the resource is a writable stream
     * @param mixed $stream
     * @return bool
     * @phpstan-assert-if-true resource $stream
     */
    public static function isWritable(mixed $stream): bool
    {
        return self::hasMode($stream, "w");
    }

    /**
     * Get if the resource is a seekable stream
     * @param mixed $stream
     * @return bool
     * @phpstan-assert-if-true resource $stream
     */
    public static function isSeekable(mixed $stream): bool
    {
        return self::hasMode($stream, "r+");
    }
}