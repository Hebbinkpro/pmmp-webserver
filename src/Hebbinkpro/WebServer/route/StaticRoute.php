<?php

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\exception\FolderNotFoundException;
use Hebbinkpro\WebServer\http\HttpUrl;
use Hebbinkpro\WebServer\http\request\HttpRequest;
use Hebbinkpro\WebServer\http\request\HttpRequestMethod;
use Hebbinkpro\WebServer\http\response\HttpResponse;
use Hebbinkpro\WebServer\http\response\HttpResponseStatus;
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
     * @param string $path
     * @param string $folder
     * @throws FolderNotFoundException
     * @throws PhpVersionNotSupportedException
     */
    public function __construct(string $path, string $folder)
    {
        // check if the folder exists
        if (!is_dir($folder)) throw new FolderNotFoundException($folder);

        $this->folder = $folder;

        // add '/*' at the end of the path string to make all paths after the / are valid
        $path = $path . "/*";

        // construct the parent with a get method, the new path and the action
        parent::__construct(HttpRequestMethod::GET, $path, null);
        $this->setAction(function (HttpRequest $req, HttpResponse $res, mixed ...$params) {
            $folder = $params[0];
            $routePath = $params[1];

            $path = HttpUrl::getSubPath($req->getURL()->getPath(), $routePath);

            // get the path of the requested file, the urlPath is replaced with the folder path
            $file = $folder . "/" . implode("/", $path);

            // check if the file exists
            if (!is_file($file)) {
                // file does not exist, send 404
                $res->setStatus(HttpResponseStatus::get(404));
                $res->send("404 File not found.", "text/plain");
                $res->end();
                return;
            }

            // file does exist, send the file
            $res->sendFile($file);
            $res->end();
        }, $folder, $this->getPath());
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

}