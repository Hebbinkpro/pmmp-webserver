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

namespace Hebbinkpro\WebServer\http\message\header;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use PHPUnit\Framework\TestCase;

class HttpHeaderTest extends TestCase
{
    public function testNormalizeFieldName()
    {
        $this->assertEquals("content-type", HttpHeader::normalizeFieldName("Content-Type"));
    }

    public function testBuilder()
    {
        $builder = new HttpHeaderBuilder();
        $this->assertEquals([], $builder->getHeaderFields());
        $this->assertEquals([], $builder->build()->getHeaderFields());

        $builder->setField("Content-Type", "text/html");
        $builder->setField("Content-Length", "123");
        $builder->setField("Server", "Hebbinkpro/WebServer");
        $this->assertEquals(["content-type" => ["text/html"], "content-length" => ["123"], "server" => ["Hebbinkpro/WebServer"]], $builder->getHeaderFields());

        $this->assertEquals($builder->getHeaderFields(), $builder->build()->getHeaderFields());
        self::assertEquals("text/html", $builder->build()->getFieldValue("Content-Type"));

        $builder->setFromFieldLine("Content-Type: text/plain");
        self::assertEquals(["text/html", "text/plain"], $builder->build()->getFieldValues("Content-Type"));

        $header = "content-type: text/html\r\ncontent-type: text/plain\r\ncontent-length: 123\r\nserver: Hebbinkpro/WebServer\r\n";
        self::assertEquals($header, $builder->build()->toString());

        $this->expectException(HttpProblemException::class);
        $builder->setFromFieldLine("Content-Type : text/plain");
    }
}