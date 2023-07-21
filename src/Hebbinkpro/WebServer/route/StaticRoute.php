<?php

namespace Hebbinkpro\WebServer\route;

use Exception;
use Hebbinkpro\WebServer\exception\FolderNotFoundException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\WebServer\http\HttpUrl;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

class StaticRoute extends Route
{
    private string $folder;

    /**
     * @throws PhpVersionNotSupportedException
     * @throws FolderNotFoundException
     */
    public function __construct(string $path, string $folder)
    {
        // check if the folder exists
        if (!is_dir($folder)) throw new FolderNotFoundException($folder);

        $this->folder = $folder;

        // add '/*' at the end of the path string to make all paths after the / are valid
        $path = $path . "/*";

        // construct the parent with a get method, the new path and the action
        parent::__construct(HttpMethod::GET, $path, null);
        $this->setAction(function (HttpRequest $req, HttpResponse $res, mixed ...$params) {
            $folder = $params[0];
            $routePath = $params[1];

            $path = HttpUrl::getSubPath($req->getURL()->getPath(), $routePath);

            // get the url path without the /*
            $urlPath = str_replace("/*", "", implode("/", $routePath));

            // get the path of the requested file, the urlPath is replaced with the folder path
            $file = $folder . "/" . implode("/", $path);

            // check if the file exists
            if (!is_file($file)) {
                // file does not exist, send 404
                $res->setStatus(HttpStatus::get(404));
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