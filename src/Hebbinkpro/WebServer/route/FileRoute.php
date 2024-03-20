<?php

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