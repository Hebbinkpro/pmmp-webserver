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

namespace Hebbinkpro\WebServer\http\message\request;

use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\message\header\HttpHeader;
use Hebbinkpro\WebServer\http\server\HttpClientInfo;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\uri\HttpRequestForm;
use Hebbinkpro\WebServer\http\uri\UriPath;
use Hebbinkpro\WebServer\http\uri\url\HttpOriginUrl;
use Hebbinkpro\WebServer\http\uri\url\HttpUrl;
use Hebbinkpro\WebServer\utils\Buffer;
use Logger;
use PHPUnit\Framework\TestCase;

class HttpRequestParserTest extends TestCase
{

	/** @var string[] */
	public const HTTP_REQUEST = [
		"GET /hello-world?q=a#frag HTTP/1.1",
		"Content-Type: text/html",
		"Host: example.com",
		"Connection: close",
		"Content-Length: 12",
		"",
		"Hello World!",
	];

	public function testParseHttpRequest(): void
	{
		$serverInfo = new HttpServerInfo("example.com");
		$logger = $this->createMock(Logger::class);
		$buffer = new Buffer();
		$parser = new HttpRequestParser($buffer, $serverInfo, $logger);

		$rawReq = implode("\r\n", self::HTTP_REQUEST);

		$this->assertFalse($parser->readFromBuffer());

		$buffer->write($rawReq);
		$this->assertTrue($parser->readFromBuffer());

		$this->assertTrue($parser->isComplete());

		$clientInfo = new HttpClientInfo("192.168.0.1", 12345, false, 0, 0);
		$req = $parser->build($clientInfo);

		// assert the request line
		$this->assertEquals(HttpMethod::GET, $req->getMethod());
		$this->assertTarget($req->getTarget());
		$this->assertTrue($req->getVersion()->equals(new HttpVersion(1, 1)));

		// assert the header
		$this->assertHeader($req->getHeader());

		// check the body
		$this->assertEquals("Hello World!", $req->getBody()->toString());

	}

	private function assertTarget(HttpUrl $target): void
	{

		$this->assertEquals(HttpRequestForm::ORIGIN, $target->getRequestForm());
		$this->assertTrue($target instanceof HttpOriginUrl);

		$this->assertEquals(UriPath::parse("/hello-world"), $target->getPath());
		$this->assertEquals("a", $target->getQuery()->getValue("q"));
		$this->assertEquals("frag", $target->getFragment()->getFragment());
	}

	private function assertHeader(HttpHeader $header): void
	{
		$this->assertEquals(["text/html"], $header->getField("Content-Type"));
		$this->assertEquals(["example.com"], $header->getField("Host"));
		$this->assertEquals(["close"], $header->getField("Connection"));
		$this->assertEquals(["12"], $header->getField("Content-Length"));
	}
}
