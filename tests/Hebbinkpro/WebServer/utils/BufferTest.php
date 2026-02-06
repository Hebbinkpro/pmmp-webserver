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

namespace Hebbinkpro\WebServer\utils;

use PHPUnit\Framework\TestCase;

class BufferTest extends TestCase
{

    public function testBufferReadAndWrite(): void
    {
        $buffer = new Buffer();
        self::assertEquals(0, $buffer->getSize());
        self::assertEquals(0, $buffer->getReadPosition());

        $data1 = "Hello World!";

        $buffer->write($data1);

        self::assertEquals(strlen($data1), $buffer->getSize());
        self::assertEquals($data1, $buffer->read($buffer->getSize()));
        self::assertEquals(0, $buffer->getSize());
        self::assertEquals(strlen($data1), $buffer->getReadPosition());

        $data2 = "Buffer Read/Write";
        $buffer->write($data2);
        self::assertEquals(strlen($data2), $buffer->getSize());
        self::assertEquals(strlen($data1), $buffer->getReadPosition());

        self::assertEquals($data2, $buffer->read(strlen($data2)));
        self::assertEquals(0, $buffer->getSize());
        self::assertEquals(strlen($data1) + strlen($data2), $buffer->getReadPosition());
    }

    public function testBufferReadLine(): void
    {
        $buffer = new Buffer();
        $data = "Hello World!";
        $buffer->write($data);
        self::assertEquals($data, $buffer->readLine());
        // if ending on EOF, read position should be at the end of the data
        self::assertEquals(strlen($data), $buffer->getReadPosition());
        self::assertEquals(0, $buffer->getSize());
        $buffer->cleanup();

        $buffer->write("$data\r\n");
        self::assertEquals($data, $buffer->readLine());
        // read position should be at the end of the line (after \r\n)
        self::assertEquals(strlen($data) + 2, $buffer->getReadPosition());
        self::assertEquals(0, $buffer->getSize());

        // read data up to max length
        $buffer->write("$data\r\n");
        self::assertEquals("Hello", $buffer->readLine(5));
    }

    public function testBufferCleanup(): void
    {
        $data1 = "Hello World!";

        $buffer = new Buffer();
        $buffer->write($data1);

        // compact without reading, should not change anything
        $buffer->cleanup();
        self::assertEquals(strlen($data1), $buffer->getSize());
        self::assertEquals($data1, $buffer->read($buffer->getSize()));

        // write and compact, new data should be moved to the beginning of the buffer
        $data2 = "Buffer Compact";
        $buffer->write($data2);
        $buffer->cleanup();
        self::assertEquals(strlen($data2), $buffer->getSize());
        self::assertEquals(0, $buffer->getReadPosition());

        self::assertEquals($data2, $buffer->readLine());

    }

    public function testBufferCopyFromStream(): void
    {
        $buffer = new Buffer();

        $data = "Hello World!";
        $stream = fopen("php://temp", "r+");
        fwrite($stream, $data);
        rewind($stream);

        $buffer->copyFromStream($stream, strlen($data));
        self::assertEquals(strlen($data), $buffer->getSize());
        self::assertEquals(0, $buffer->getReadPosition());
        self::assertEquals($data, $buffer->read($buffer->getSize()));
        fclose($stream);
    }

    public function testBufferCopyToStream(): void
    {
        $buffer = new Buffer();

        $data = "Hello World!";
        $buffer->write($data);

        $stream = fopen("php://temp", "r+");
        $buffer->copyToStream($stream, strlen($data));
        self::assertEquals(0, $buffer->getSize());
        self::assertEquals(strlen($data), $buffer->getReadPosition());

        rewind($stream);
        self::assertEquals($data, stream_get_contents($stream));

        fclose($stream);
    }

    public function testBufferWithSimpleHttpRequestStream(): void
    {

        $start = "POST / HTTP/1.1";
        $body = "Hello World!";
        $header = "Host: localhost\r\nContent-Length: " . strlen($body);
        $req = "$start\r\n$header\r\n\r\n$body\r\n";

        $stream = fopen("php://temp", "r+");
        fwrite($stream, $req);
        rewind($stream);

        $buffer = new Buffer();
        $buffer->copyFromStream($stream, strlen($req));
        self::assertEquals(strlen($req), $buffer->getSize());

        self::assertEquals($start, $buffer->readLine());
        self::assertEquals(strlen($start) + 2, $buffer->getReadPosition());

        $unread = strlen($req) - strlen($start) - 2;
        self::assertEquals($unread, $buffer->getSize());

        $buffer->cleanup();
        self::assertEquals($unread, $buffer->getSize());
        self::assertEquals(0, $buffer->getReadPosition());
        self::assertEquals("$header\r\n\r\n$body\r\n", $buffer->read($unread));

        fclose($stream);
    }


}