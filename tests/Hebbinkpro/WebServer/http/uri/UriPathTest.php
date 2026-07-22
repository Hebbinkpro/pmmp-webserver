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

namespace Hebbinkpro\WebServer\http\uri;

use PHPUnit\Framework\TestCase;

class UriPathTest extends TestCase
{
    public function testUriStartsWith()
    {

        $pathParts = ["foo", "bar", "baz"];
        $path = new UriPath($pathParts);


        for ($i = 0; $i <= count($pathParts); $i++) {
            $slice = array_slice($pathParts, 0, $i);
            $this->assertTrue($path->startsWith(new UriPath($slice)));
        }

        $this->assertFalse($path->startsWith(new UriPath(["bar"])));
        $this->assertFalse($path->startsWith(new UriPath(["bar", "baz"])));

        $this->assertTrue($path->startsWith(new UriPath(["foo", ":a", "baz"])));
        $this->assertTrue($path->startsWith(new UriPath(["foo", "bar", "*"])));
        $this->assertTrue($path->startsWith(new UriPath(["**"])));
        $this->assertTrue($path->startsWith(new UriPath(["**", "baz"])));
        $this->assertTrue($path->startsWith(new UriPath(["foo", "**", "baz"])));

        $this->assertFalse($path->startsWith(new UriPath(["**", "abc"])));
        $this->assertFalse($path->startsWith(new UriPath(["**", "bar", "abc"])));
        $this->assertFalse($path->startsWith(new UriPath(["**", "bar", "baz", "abc"])));
        $this->assertFalse($path->startsWith(new UriPath(["**", "foo", "bar", "baz", "abc"])));
    }
}