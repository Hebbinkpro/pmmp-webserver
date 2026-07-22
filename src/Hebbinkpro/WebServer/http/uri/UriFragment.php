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

namespace Hebbinkpro\WebServer\http\uri;

class UriFragment implements UriElement
{
    public function __construct(private ?string $fragment)
    {
    }

    /**
     * @inheritDoc
     */
    public static function parse(string $value): self
    {
        if (strlen($value) == 0) return new self(null);

        $value = trim($value, "#");
        $fragment = urldecode($value);
        return new self($fragment);
    }

    /**
     * @return string|null
     */
    public function getFragment(): string|null
    {
        return $this->fragment;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        if ($this->fragment === null) return "";

        return "#" . urlencode($this->fragment);
    }
}