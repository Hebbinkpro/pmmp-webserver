<?php

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\exception\FileNotFoundException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;

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
            function (HttpRequest $req, HttpResponse $res, mixed ...$params) {
                $res->sendFile($params[0], $params[1]);
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