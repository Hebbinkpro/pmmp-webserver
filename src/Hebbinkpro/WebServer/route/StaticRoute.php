<?php

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\exception\FolderNotFoundException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

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
     * @throws PhpVersionNotSupportedException
     */
    public function __construct(string $folder)
    {
        // check if the folder exists
        if (!is_dir($folder)) throw new FolderNotFoundException($folder);

        $this->folder = $folder;

        // construct the parent with a get method, the new path and the action
        parent::__construct(HttpMethod::GET,
            function (HttpRequest $req, HttpResponse $res, mixed ...$params) {
                $folder = $params[0];
                $filePath = $req->getSubPath();

                // get the path of the requested file, the uriPath is replaced with the folder path
                $file = $folder . "/" . $filePath;

                // check if the file exists
                if (!is_file($file)) {
                    // file does not exist, send 404
                    $res->setStatus(HttpStatusCodes::NOT_F0UND);
                    $res->send("404 File not found.", "text/plain");
                    $res->end();
                    return;
                }

                // file does exist, send the file
                $res->sendFile($file);
                $res->end();
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