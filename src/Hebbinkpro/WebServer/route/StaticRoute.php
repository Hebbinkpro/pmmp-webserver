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

use Hebbinkpro\WebServer\exception\FolderNotFoundException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\response\HttpResponseBuilder;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;

/**
 * A GET Route that functions like a FileRoute but for a folder.
 * All files and folders inside the given folder will be accessible using the web server.
 *
 * Used for folders containing multiple static files (e.g. html/css/js files)
 */
class StaticRoute extends Route
{
    private string $folder;

    /**
     * @param string $folder
     * @throws FolderNotFoundException
     */
    public function __construct(string $folder)
    {
        // check if the folder exists
        if (!is_dir($folder)) throw new FolderNotFoundException($folder);

        $this->folder = $folder;

        // construct the parent with a get method, the new path and the action
        parent::__construct(HttpMethod::GET,
            function (HttpRequest $req, HttpResponseBuilder $res, mixed ...$params) {
                // quick check to make PHPStan happy and to ensure the folder still exists
                if (!is_string($params[0]) || ($folderPath = realpath($params[0])) === false || !is_dir($folderPath)) {
                    $res->setStatus(HttpStatusCodes::NOT_FOUND);
                    $res->sendStatusMessage();
                    return;
                }
                // get the path of the requested file, the uriPath is replaced with the folder path
	            $reqSubPath = $req->getRouteInfo()->getFilePath();

                if ($reqSubPath !== null) {
                    $reqFilePath = implode(DIRECTORY_SEPARATOR, $reqSubPath->asArray());
                    $realFilePath = realpath($folderPath . DIRECTORY_SEPARATOR . $reqFilePath);
                } else {
                    $realFilePath = false;
                }

                // validate path
                if ($realFilePath === false
                    || !str_starts_with($realFilePath, $folderPath . DIRECTORY_SEPARATOR)
                    || !is_file($realFilePath)) {

                    // file does not exist, send 404
                    $res->setStatus(HttpStatusCodes::NOT_FOUND);
                    $res->sendStatusMessage();
                    return;
                }

                // file does exist, send the file
                $res->sendFile($realFilePath);
            },
            $folder);
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

}