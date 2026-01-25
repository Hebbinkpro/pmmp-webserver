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

namespace Hebbinkpro\WebServer\http;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use PHPUnit\Framework\TestCase;

class HttpBaseParserTest extends TestCase
{
    public function testHttpVersionParser()
    {
        $this->assertEquals(new HttpVersion(1, 1), HttpVersion::parse("HTTP/1.1"));
        $this->assertEquals(new HttpVersion(9, 9), HttpVersion::parse("HTTP/9.9"));

        $this->expectException(HttpProblemException::class);
        HttpVersion::parse("HTT/1.1");
        HttpVersion::parse("HTTP/1.10");
    }

    public function testRequestLineParser()
    {
        $this->assertEquals(
            new HttpRequestLine(HttpMethod::GET, "/", new HttpVersion(1, 1)),
            HttpRequestLine::parse("GET / HTTP/1.1")
        );

        $this->assertEquals(
            new HttpRequestLine(HttpMethod::GET, "/", new HttpVersion(1, 1)),
            HttpRequestLine::parse("get / HTTP/1.1")
        );

        $this->assertEquals(
            new HttpRequestLine(HttpMethod::GET, "/", new HttpVersion(1, 1)),
            HttpRequestLine::parse("gEt / HTTP/1.1")
        );

        $this->assertEquals(
            new HttpRequestLine(HttpMethod::POST, "https://example.com/path", new HttpVersion(1, 1)),
            HttpRequestLine::parse("POST https://example.com/path HTTP/1.1")
        );

        foreach (array_diff(HttpMethod::cases()) as $method) {
            // this is an invalid method
            if ($method === HttpMethod::ALL) continue;

            $this->assertEquals(
                new HttpRequestLine($method, "/", new HttpVersion(1, 1)),
                HttpRequestLine::parse("$method->value / HTTP/1.1")
            );
        }

        $this->assertEquals(
            new HttpRequestLine(HttpMethod::GET, "-=!@#$%^&*()_+", new HttpVersion(1, 1)),
            HttpRequestLine::parse("GET -=!@#$%^&*()_+ HTTP/1.1")
        );

        $this->expectException(HttpProblemException::class);
        HttpRequestLine::parse("GET / HTTP/1.1 Some random text");
        HttpRequestLine::parse("GET / HTT/1.1");
        HttpRequestLine::parse("EXAMPLE / HTTP/1.1");

        // this would match HttpMethod::ALL
        HttpRequestLine::parse("* / HTTP/1.1");
    }
}