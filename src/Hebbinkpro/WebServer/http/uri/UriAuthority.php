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

namespace Hebbinkpro\WebServer\http\uri;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpConstants;

class UriAuthority implements UriElement
{

    public function __construct(private string $host, private int $port, private ?string $user, private ?string $pass)
    {

    }

    public static function parse(string $value, int $port = HttpConstants::DEFAULT_HTTP_PORT): UriAuthority
    {
        $user = null;
        $pass = null;
        $host = "";

        // got user[:pass]@[host[:port]]
        if (($at = strpos($value, "@")) !== false) {
            $user = substr($value, 0, $at);
            $host = substr($value, $at + 1);

            // got user:pass - pass can contain many colons
            if (($colon = strpos($value, ":")) !== false) {
                $host = substr($value, $colon + 1);
                $user = substr($value, 0, $colon);
            }
        }

        // get the last colon as it could still contain an IPv6 address
        if (($colon = strrpos($host, ":")) !== false) {
            $ipv6End = strrpos($host, "]");
            if ($ipv6End === false || $colon > $ipv6End) {
                // get the port and validate that its a digit
                $portStr = substr($host, $colon + 1);
                if (!ctype_digit($portStr)) throw HttpProblemException::badRequest();

                $port = intval($portStr);
                $host = substr($host, 0, $colon);
            }
        }

        return new self($host, $port, $user, $pass);
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
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @return string|null
     */
    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function toString(): string
    {
        $authority = "//";

        // add the user
        if ($this->user !== null) {
            $authority .= $this->user;
            // only add password if it is given
            if ($this->pass !== null) {
                $authority .= ":" . $this->pass;
            }
            $authority .= "@";
        }


        $authority .= $this->host;

        // omit if port is the default port
        if ($this->port !== HttpConstants::DEFAULT_HTTP_PORT && $this->port !== HttpConstants::DEFAULT_HTTPS_PORT) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }
}