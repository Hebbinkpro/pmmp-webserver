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

use Hebbinkpro\WebServer\exception\HttpProblemException;
use PHPUnit\Framework\TestCase;

class UrlUtilsTest extends TestCase
{
    public const SAMPLE_URLS = [
        "https://www.example.com/" => [
            "scheme" => "https",
            "host" => "www.example.com",
            "path" => "/"
        ],
        "/index.html?s=query&abc=def#fragment" => [
            "path" => "/index.html",
            "query" => "s=query&abc=def",
            "fragment" => "fragment"
        ],
        "localhost:3000" => [
            "host" => "localhost",
            "port" => 3000
        ],
    ];

    public function testParseUrl()
    {
        foreach (self::SAMPLE_URLS as $url => $expected) {
            $this->assertEquals($expected, UrlUtils::parseUrl($url));
        }
    }

    public function testParseUrlInvalid()
    {
        // well, its quite hard to find a breakable url, but this seems to work
        $url = ":3000";
        $this->assertFalse(@parse_url($url));

        $this->expectException(HttpProblemException::class);
        UrlUtils::parseUrl($url);
    }

    public function testParseUrlMatchesRequired()
    {
        $url = "https://www.example.com/";
        $this->assertEquals(["host" => "www.example.com"], UrlUtils::parseUrlMatches($url, ["host"]));
        $this->assertEquals(self::SAMPLE_URLS[$url], UrlUtils::parseUrlMatches($url, ["scheme", "host", "path"]));

        $this->expectException(HttpProblemException::class);
        UrlUtils::parseUrlMatches($url, ["port"]);
        UrlUtils::parseUrlMatches($url, ["host", "port"]);
    }

    public function testParseUrlMatchesOptional()
    {
        $url = "https://www.example.com/";
        $this->assertEquals(["host" => "www.example.com"], UrlUtils::parseUrlMatches($url, [], ["host"]));
        $this->assertEquals(self::SAMPLE_URLS[$url], UrlUtils::parseUrlMatches($url, [], ["scheme", "host", "path"]));

        $this->assertEquals([], UrlUtils::parseUrlMatches($url, [], ["port"]));
        $this->assertEquals(["host" => "www.example.com"], UrlUtils::parseUrlMatches($url, [], ["host", "port"]));

        $this->assertEquals(self::SAMPLE_URLS[$url], UrlUtils::parseUrlMatches($url, ["scheme"], ["host", "path"]));

        $this->expectException(HttpProblemException::class);
        UrlUtils::parseUrlMatches($url, ["port"], ["host"]);
    }

    public function testParseUrlMatchesStrict()
    {
        $url = "https://www.example.com/";

        $this->assertEquals(self::SAMPLE_URLS[$url], UrlUtils::parseUrlMatches($url, ["scheme", "host", "path"], [], true));
        $this->assertEquals(self::SAMPLE_URLS[$url], UrlUtils::parseUrlMatches($url, [], ["scheme", "host", "path"], true));
        $this->assertEquals(self::SAMPLE_URLS[$url], UrlUtils::parseUrlMatches($url, ["scheme"], ["host", "path"], true));
        $this->assertEquals(self::SAMPLE_URLS[$url], UrlUtils::parseUrlMatches($url, ["scheme"], ["host", "port", "path"], true));

        $this->expectException(HttpProblemException::class);
        UrlUtils::parseUrlMatches($url, ["scheme"], [], true);
        UrlUtils::parseUrlMatches($url, ["scheme", "host"], ["port"], true);
    }
}