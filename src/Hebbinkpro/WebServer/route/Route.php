<?php

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\WebServer\http\HttpUrl;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\SerializableClosure;
use Hebbinkpro\WebServer\WebClient;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class Route extends ThreadSafe
{
    private string $method;
    private ThreadSafeArray $path;
    private ?string $action;
    private string $params;

    /**
     * @throws PhpVersionNotSupportedException
     */
    public function __construct(string $method, string $path, ?callable $action, mixed ...$params)
    {
        $this->method = $method;
        // construct an urlPath from the given path
        $this->path = ThreadSafeArray::fromArray(HttpUrl::parsePath($path));

        $this->setAction($action, ...$params);
    }

    /**
     * @throws PhpVersionNotSupportedException
     */
    protected function setAction(?callable $action, mixed ...$params) {
        if (!is_null($action)) {
            $serializable = new SerializableClosure($action);
            $this->action = serialize($serializable);
        } else {
            $this->action = null;
        }

        $this->params = serialize($params);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getPath(): array
    {
        $array = [];
        foreach ($this->path as $key=>$value) {
            $array[$key] = $value;
        }
        return $array;
    }

    /**
     * Response to a request
     * @param WebClient $client
     * @param HttpRequest $req
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function handleRequest(WebClient $client, HttpRequest $req): void
    {
        $res = new HttpResponse($client);
        /** @var SerializableClosure $action */
        $action = unserialize($this->action);

        // if action is not null, execute the action
        if (!is_null($this->action)) call_user_func($action->getClosure(), $req, $res, ...(unserialize($this->params)));

        // end the response if it was not already done
        if (!$res->isEnded()) $res->end();

    }

    /**
     * Checks if a given path is equal to the path of this route
     * @param string $method
     * @param array $path
     * @return bool
     */
    public function equals(string $method, array $path): bool
    {
        // the given method is not valid
        if ($this->method != HttpMethod::ALL && $this->method != $method) return false;
        // the given path is shorter than the expected path
        if (count($path) < count($this->path)) return false;

        for ($i = 0; $i < count($path); $i++) {
            $value = $path[$i];
            $expected = $this->path[$i] ?? null;

            // not valid, the expected path has ended but the value expects some more
            if ($expected === null) return false;

            // expected is a *, so every thing after that is allowed
            if ($expected === "*") return true;

            // the expected is a path param, so everything is valid. Continue to the next
            if (str_starts_with($expected, ":")) continue;

            // the given value is not valid with the expected value
            if ($value !== $expected) return false;
        }

        // the given path is valid
        return true;
    }

    public function __toString(): string
    {
        return $this->method . " '/" . implode("/", $this->path) . "' (path count: " . count($this->path) . ")";
    }
}