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

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\exception\FileNotFoundException;
use Hebbinkpro\WebServer\http\HttpContentType;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\response\HttpResponseBuilder;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;

/**
 * A GET route that sends a file to the client
 */
class FileRoute extends BaseRoute
{
    private string $file;

    /**
     * @param string $file the file path
     * @param string|null $contentType the content type of the file, if null it will be determined automatically
     * @param string|null $default default value used when the file does not exist, if null a 404 will be sent instead
     * @param string|null $defaultContentType the content type of the default value, if null text/html will be used
     * @throws FileNotFoundException
     */
    public function __construct(string $file, ?string $contentType = null, ?string $default = null, ?string $defaultContentType = null)
    {
        if (!file_exists($file) && $default === null) throw new FileNotFoundException($file);

        $this->file = $file;

        parent::__construct(HttpMethod::GET,
            function (HttpRequest $req, HttpResponseBuilder $res, mixed $file = "", ?string $contentType = null, ?string $default = null, ?string $defaultContentType = null, mixed ...$params) {
                // for PHPStan, validate that the given file is a proper string, which should always be the case
                if (!is_string($file)) {
                    $res->setStatus(HttpStatusCodes::NOT_FOUND);
                    $res->sendStatusMessage();
                    return;
                }

                if ($default === null) {
                    $res->sendFile($file, $contentType);
                } else {
                    $res->sendFileOrDefault($file, $default, $defaultContentType ?? HttpContentType::TEXT_HTML);
                }

            },
            $file, $contentType, $default, $defaultContentType
        );
    }

    public function getFile(): string
    {
        return $this->file;
    }

}