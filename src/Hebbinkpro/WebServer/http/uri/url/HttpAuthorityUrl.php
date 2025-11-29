<?php
/*
 * MIT License
 *
 * Copyright (c) 2025 Hebbinkpro
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

namespace Hebbinkpro\WebServer\http\uri\url;

use Hebbinkpro\WebServer\http\uri\AuthorityUri;
use Hebbinkpro\WebServer\http\uri\HttpRequestForm;
use Hebbinkpro\WebServer\http\uri\UriAuthority;
use Hebbinkpro\WebServer\utils\UrlUtils;

class HttpAuthorityUrl implements HttpUrl, AuthorityUri
{

    public function __construct(private string $host, private int $port)
    {
    }

    /**
     * @inheritDoc
     */
    public static function parse(string $value): HttpAuthorityUrl
    {
        /** @var array{host: string, port: int} $urlParts */
        $urlParts = UrlUtils::parseUrlMatches($value, ["host", "port"], [], true);

        $host = $urlParts["host"];
        $port = $urlParts["port"];

        return new self($host, intval($port));
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @inheritDoc
     */
    public function getRequestForm(): HttpRequestForm
    {
        return HttpRequestForm::AUTHORITY;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->host . ":" . $this->port;
    }

    public function getAuthority(): UriAuthority
    {
        return new UriAuthority($this->host, $this->port, null, null);
    }
}