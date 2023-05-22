<?php

namespace Hebbinkpro\WebServer\route;

use Exception;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\WebServer\http\HttpUrl;
use Hebbinkpro\WebServer\http\status\HttpStatus;

class StaticRoute extends Route
{
    private string $folder;

    /**
     * @throws Exception
     */
    public function __construct(string $path, string $folder)
    {
        // check if the folder exists
        if (!is_dir($folder)) throw new Exception("Given folder does not exist");

        $this->folder = $folder;

        // add '/*' at the end of the path string to make all paths after the / are valid
        // construct the parent with a get method, the new path and the action
        parent::__construct(HttpMethod::GET, $path . "/*", function (HttpRequest $req, HttpResponse $res) {
            $path = HttpUrl::getSubPath($req->getURL()->getPath(), $this->getPath());

            // get the url path without the /*
            $urlPath = str_replace("/*", "", implode("/", $this->getPath()));

            // get the path of the requested file, the urlPath is replaced with the folder path
            $file = $this->folder . "/" . implode("/", $path);

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
        });
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

}