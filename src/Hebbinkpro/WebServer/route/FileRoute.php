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

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\exception\FileNotFoundException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;

/**
 * A GET route that sends a file to the client
 */
class FileRoute extends Route
{
    private string $file;

    /**
     * @param string $file the file path
     * @param string|null $default
     * @throws FileNotFoundException
     */
    public function __construct(string $file, ?string $default = null)
    {
        if (!file_exists($file) && $default === null) throw new FileNotFoundException($file);

        $this->file = $file;

        parent::__construct(HttpMethod::GET,
            function (HttpRequest $req, HttpResponse $res, mixed $file = "", mixed $default = null, mixed ...$params) {
                if (!is_string($file) || ($default !== null && !is_string($default))) {
                    $res->setStatus(HttpStatusCodes::NOT_F0UND);
                    $res->sendStatusMessage();
                    $res->end();
                    return;
                }

                $res->sendFile($file, $default);
                $res->end();
            },
            $file, $default
        );
    }

    public function getFile(): string
    {
        return $this->file;
    }

}