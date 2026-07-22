<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Hebbinkpro
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
use Hebbinkpro\WebServer\http\HttpConstants;
use OverflowException;

/**
 * A buffer to store data temporary which can only be read once
 */
class Buffer
{
    /** @var resource the temporary file stream where the buffer is stored */
    private mixed $stream;
    /** @var int<-1, max> */
    private int $readPosition;
    /** @var int<1, max> */
    private int $maxBufferSize;

    public function __construct(int $maxBufferSize = HttpConstants::MAX_CLIENT_BUFFER_SIZE)
    {
        $this->maxBufferSize = $maxBufferSize;
        $this->stream = StreamUtils::openTempStream();
        $this->readPosition = 0;
    }

    /**
     * Close the buffer freeing all resources
     * @return void
     */
    public function close(): void
    {
        $this->readPosition = -1;
        try {
            StreamUtils::closeStream($this->stream);
        } catch (StreamException) {
            // already closed
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Copy data from the `$from` stream to the buffer
     * @param resource $from the stream to copy from
     * @param int<1, max> $length the maximum number of bytes to copy
     * @return int the number of bytes copied
     * @throws OverflowException if the buffer cannot hold at most `$length` bytes
     */
    public function copyFromStream(mixed $from, int $length): int
    {
        // move position to the end of the buffer
        $this->prepareForWrite($length);
        return StreamUtils::streamCopyToStream($from, $this->stream, $length);
    }


    /**
     * Copy data from this buffer to `$to` stream
     * @param resource $to the stream to copy to
     * @param int<1, max> $length the maximum number of bytes to copy
     * @return int<0,max> the number of bytes copied
     */
    public function copyToStream(mixed $to, int $length): int
    {
        // move position to the beginning of the unread data
        $this->moveToRead();
        $copied = StreamUtils::streamCopyToStream($this->stream, $to, $length);

        // Advance source read position
        $this->readPosition += $copied;
        return $copied;
    }

    /**
     * Write data to the buffer
     * @param string $data the data to be written
     * @return int the number of bytes written
     * @throws OverflowException if the data is too large for the buffer
     */
    public function write(string $data): int
    {
        $this->prepareForWrite(strlen($data));
        return StreamUtils::writeStream($this->stream, $data);
    }

    /**
     * Read data from the buffer
     * @param int<1, max> $length the maximum number of bytes to read
     * @return string the read data
     */
    public function read(int $length): string
    {
        $this->moveToRead();
        $data = StreamUtils::readStream($this->stream, $length);
        $this->readPosition += strlen($data);
        return $data;
    }

    /**
     * Cleanup the buffer by moving all unread data to the beginning of the buffer
     * - This copies all unread data to a new temp file and closes the old one
     * @return void
     */
    public function cleanup(): void
    {
        // go to the read position and copy all remaining data to the new buffer
        $this->moveToRead();

        // create a new temp file and copy all unread data
        $newBuffer = StreamUtils::openTempStream();
        StreamUtils::streamCopyToStream($this->stream, $newBuffer);

        // close the old file and set the new one
        StreamUtils::closeStream($this->stream);
        $this->stream = $newBuffer;

        // reset the read position
        $this->readPosition = 0;
    }

    /**
     * Read a line from the buffer
     *
     * On failure the read position will not be changed.
     * @param int $maxLength the maximum number of bytes to read
     * @return string|null the read line, or null on failure
     */
    public function readLine(int $maxLength = HttpConstants::MAX_STREAM_READ_LENGTH): ?string
    {
        $this->moveToRead();
        $line = StreamUtils::streamGetLine($this->stream, $maxLength, "\r\n");
        if ($line === false) return null;

        $bufferSize = $this->getSize();
        $lineLength = strlen($line);

        if ($bufferSize <= $lineLength) {
            // got EOF or max length, do not include \r\n in next read position
            $this->readPosition = $bufferSize;
        } else {
            // got linebreak, exclude it
            $this->readPosition += $lineLength + 2;
        }

        return $line;
    }

    /**
     * Get the number of unread bytes in the buffer
     * @return int
     */
    public function getSize(): int
    {
        return StreamUtils::streamStat($this->stream)["size"] - $this->readPosition;
    }

    /**
     * Get the current read position in the buffer
     * @return int
     */
    public function getReadPosition(): int
    {
        return $this->readPosition;
    }

    /**
     * Validate that the buffer can hold at least `$length` bytes before writing
     * @param int $length
     * @return void
     * @throws OverflowException if the buffer cannot hold at most `$length` bytes
     */
    protected function prepareForWrite(int $length): void
    {
        // check buffer size
        if ($this->getSize() + $length > $this->maxBufferSize) {
            throw new OverflowException("Buffer cannot exceed " . $this->maxBufferSize . " bytes.");
        }

        // move position to the end of the buffer
        $this->moveToEnd();
    }

    /**
     * Move the read position to the end of the buffer
     * @return void
     */
    protected function moveToEnd(): void
    {
        StreamUtils::seekStream($this->stream, 0, SEEK_END);
    }

    /**
     * Move the read position to the beginning of the unread data
     * @return void
     */
    protected function moveToRead(): void
    {
        StreamUtils::seekStream($this->stream, $this->readPosition);
    }

    /**
     * Get if the buffer is closed
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->readPosition < 0;
    }
}