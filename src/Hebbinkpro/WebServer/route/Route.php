<?php

namespace Hebbinkpro\WebServer\route;

use Closure;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\SerializableClosure;
use pmmp\thread\ThreadSafe;

/**
 * A route that handles a client request for a specific path
 */
class Route extends ThreadSafe
{
    private HttpMethod $method;
    private ?string $action;
    private string $params;

    /**
     * @param HttpMethod $method the request method
     * @param Closure(HttpRequest $req, HttpResponse $res, mixed ...$params): void|null $action the action to execute
     * @param mixed ...$params additional (thread safe) parameters to use in the action
     */
    public function __construct(HttpMethod $method, ?Closure $action, mixed ...$params)
    {
        $this->method = $method;
        $this->action = null;

        if ($action !== null) {
            $serializable = new SerializableClosure($action);
            $this->action = serialize($serializable);
        }

        $this->params = serialize($params);
    }

    /**
     * Get the HTTP method
     * @return HttpMethod
     */
    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    /**
     * Handle the client request by executing the action
     * @param HttpClient $client the client
     * @param HttpRequest $req the request of the client
     * @return void
     */
    public function handleRequest(HttpClient $client, HttpRequest $req): void
    {
        if ($this->action === null) {
            HttpResponse::notImplemented($client)->end();
            return;
        }

        /** @var SerializableClosure|null $action */
        $action = unserialize($this->action);

        // no action to handle the request
        if ($action === false || $action === null) {
            HttpResponse::notImplemented($client)->end();
            return;
        }

        /** @var mixed[] $params */
        $params = unserialize($this->params);

        // response to be sent back to the client, and make sure HEAD requests send a response without content
        if ($req->getMethod() === HttpMethod::HEAD) $res = HttpResponse::noContent($client);
        else $res = HttpResponse::ok($client);

        // execute the closure with the request, response and parameters
        call_user_func($action->getClosure(), $req, $res, ...$params);

        // end the response if it was not already done
        if (!$res->isEnded()) $res->end();


    }


}