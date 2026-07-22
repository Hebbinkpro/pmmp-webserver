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

declare(strict_types=1);

namespace Hebbinkpro\WebServer\utils;

use Hebbinkpro\WebServer\exception\StreamException;

final class StreamUtils
{
    /**
     * Get if the resource is a readable stream
     * @param resource $stream
     * @return bool
     */
    public static function isReadable(mixed $stream): bool
    {
        return self::hasMode($stream, "r") || self::isReadableAndWritable($stream);
    }

    /**
     * Get if the resource is a stream and has the specified mode
     * @param resource $stream the resource to check
     * @param string $mode the mode to check for
     * @return bool if the resource is a stream and has the specified mode
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
     * @param resource $stream
     * @return bool
     */
    public static function isWritable(mixed $stream): bool
    {
        return self::hasMode($stream, "w") || self::isReadableAndWritable($stream);
    }

    /**
     * Get if the resource is readable and writable stream
     * @param resource $stream
     * @return bool
     */
    public static function isReadableAndWritable(mixed $stream): bool
    {
        return self::hasMode($stream, "r+") || self::hasMode($stream, "w+");
    }

    /**
     * Open a file stream
     *
     * This is a wrapper for <code>fopen</code> and used to ensure that the return type is a <code>resource</code>
     * instead of <code>resource|false</code> as for the default method.
     * This is an issue as in PMMP, E_WARNING throws an exception instead of returning false,
     * which PHPStan does not agree with. So to fix PHPStan worarounds,
     * we can use this wrapper which will either return a resource or throw an exception.
     * @param string $filename
     * @param string $mode
     * @param bool $use_include_path
     * @param resource $context
     * @return resource the created file stream
     * @throws StreamException on failure
     * @see fopen for parameter descriptions
     */
    public static function openStream(string $filename, string $mode, bool $use_include_path = false, mixed $context = null): mixed
    {
        $stream = @fopen($filename, $mode, $use_include_path, $context);
        if ($stream === false) {
            throw new StreamException("Unable to open temp stream");
        }
        return $stream;
    }

    /**
     * Open a read and writeable stream in-memory.
     *
     * @return resource <code>fopen("php://temp", "r+")</code>
     */
    public static function openTempStream(): mixed
    {
        return self::openStream("php://temp", "r+");
    }

    /**
     * Copies data from one stream to another
     *
     * Wrapper around <code>stream_copy_to_stream</code>
     * @param resource $from
     * @param resource $to
     * @param int<1,max>|null $length
     * @param int $offset
     * @return int<0,max>
     * @throws StreamException on failure
     * @see stream_copy_to_stream for parameter descriptions
     */
    public static function streamCopyToStream(mixed $from, mixed $to, ?int $length = null, int $offset = 0): int
    {
        $bytes = @stream_copy_to_stream($from, $to, $length, $offset);
        if ($bytes === false) throw new StreamException("Unable to copy stream to stream");

        return $bytes;
    }

    /**
     * Binary-safe file read
     *
     * Wrapper around <code>fread</code>
     * @param resource $stream
     * @param int<1,max> $length
     * @return string
     * @throws StreamException on failure
     * @see fread for parameter descriptions
     */
    public static function readStream(mixed $stream, int $length): string
    {
        $data = @fread($stream, $length);
        if ($data === false) throw new StreamException("Unable to read stream");
        return $data;
    }

    /**
     * Binary-safe file write
     *
     * Wrapper around <code>fwrite</code>
     * @param resource $stream
     * @param string $data
     * @param int<1,max>|null $length
     * @return int<0,max>
     * @throws StreamException on failure
     * @see fwrite for parameter descriptions
     */
    public static function writeStream(mixed $stream, string $data, ?int $length = null): int
    {
        $bytes = @fwrite($stream, $data, $length);
        if ($bytes === false) throw new StreamException("Unable to write stream");
        return $bytes;
    }

    /**
     * Binary-safe file write
     *
     * Wrapper around <code>stream_get_line</code>
     * @param resource $stream
     * @param int<1,max> $length
     * @param string $ending
     * @return mixed
     * @throws StreamException on failure
     * @see stream_get_line for parameter descriptions
     */
    public static function streamGetLine(mixed $stream, int $length, string $ending = ''): string
    {
        $line = stream_get_line($stream, $length, $ending);
        if ($line === false) throw new StreamException("Unable to get line of stream");
        return $line;
    }

    /**
     * Seeks on a file pointer
     *
     * Wrapper around <code>fseek</code> but does not return any value
     * @param resource $stream
     * @param int $offset
     * @param int $whence
     * @return void
     * @throws StreamException on failure
     * @see fseek for parameter descriptions
     */
    public static function seekStream(mixed $stream, int $offset, int $whence = SEEK_SET): void
    {
        $success = @fseek($stream, $offset, $whence);
        if ($success === -1) throw new StreamException("Unable to seek stream");
    }

    /**
     * Gets information about a file using an open file pointer
     *
     * Wrapper around <code>fstat</code>
     * @param resource $stream
     * @return array<string,mixed>
     * @throws StreamException on failure
     * @see fstat for parameter descriptions
     */
    public static function streamStat(mixed $stream): array
    {
        $stat = @fstat($stream);
        if ($stat === false) throw new StreamException("Unable to get information about the stream");

        return $stat;
    }

    /**
     * Closes an open file pointer
     *
     * Wrapper around <code>fclose</code>
     * @param resource $stream
     * @return void
     * @throws StreamException on failure
     * @see fclose for parameter descriptions
     */
    public static function closeStream(mixed $stream): void
    {
        $success = @fclose($stream);
        if ($success === false) throw new StreamException("Unable to close stream");
    }
}