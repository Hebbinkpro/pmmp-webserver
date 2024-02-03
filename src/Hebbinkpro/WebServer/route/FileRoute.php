<?php

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\exception\FileNotFoundException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

/**
 * A GET route that sends a file to the client
 */
class FileRoute extends Route
{
    private string $file;

    /**
     * @param string $path the url path
     * @param string $file the file path
     * @param string|null $default
     * @throws FileNotFoundException|PhpVersionNotSupportedException
     */
    public function __construct(string $path, string $file, ?string $default = null)
    {
        if (!file_exists($file) && $default === null) throw new FileNotFoundException($file);

        $this->file = $file;

        parent::__construct(HttpMethod::GET, $path, null);

        $this->setAction(function (HttpRequest $req, HttpResponse $res, mixed ...$params) {
            $res->sendFile($params[0], $params[1]);
            $res->end();
        }, $file, $default);
    }

    public function getFile(): string
    {
        return $this->file;
    }

}