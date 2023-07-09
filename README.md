# PMMP WebServer
A Verion for PocketMine-MP plugins to create a simple HTTP/1.1 web server.

## Plugins
- This virion is used in my Dynmap like plugin, `PocketMap`. You can find the plugin [here](https://github.com/Hebbinkpro/PocketMap)

## How to install
- Download the latest phar build from [Poggit CI](https://poggit.pmmp.io/ci/Hebbinkpro/pmmp-webserver)
- or install it directly using composer: `composer require hebbinkpro/pmmp-webserver`

## How to use
### Creating a web server
For creating a web server you have to register the WebServer and create a new instance of `WebServer` to start the server.
```php
<?php

use Hebbinkpro\WebServer\WebServer;

class YourPlugin extends \pocketmine\plugin\PluginBase {
    
    protected function onEnable() : void{
        // ...
        
        // register the WebServer
        WebServer::register($this);
    
        // This is the address the web server will be listening on, localhost (127.0.0.1) will most of the time work.
        $address = "127.0.0.1";
        // The port the server is listening on
        $port = 3000;
        // Create a new server on the address and port
        $webServer = new WebServer($this, $address, $port);
        
        // after starting the server, the site will be available at http://127.0.0.1:3000
        $webServer->start();
    }
}
```
_Note that if you want to access the server outside your localhost or local network, you may need to port-forward, otherwise it will not be available for the outside web!_

### Routing
Routes are used to let your web server be able to do things. By creating a `Route` you can make your webserver listen to different paths and respond to them.
#### Router
The router is used to register all your routes. the router will also handle all incoming requests and make sure they are handled by the correct `Route`.<br>
You can access the router of your `WebServer` by calling:
```php
$router = $webServer->getRouter();
```

#### Route
A route is used to perform an action on an incoming web request.
```php
use Hebbinkpro\WebServer\route\Route;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;

    // the method can be any value in the HttpMethod class.
    // these methods represent HTTP request methods and makes the route listen to a specific type of request.
    // if you want to listen to all requests, you can use HttpMethod::ANY (or "*").
    $method = HttpMethod::GET;
    
    // the specific path the route will listen to,
    // you can find out more about the paths below
    $path = "/";
    
    // the action is the part that will execute once a client makes a request to the given method AND path.
    // the HttpRequest inside the function is the request the client made to the web server
    // the HttpResponse is the response the server will send back to the client after the function returns.
    $action = function (HttpRequest $request, HttpResponse $response) {
        // This will send the string "Hello World" back to the client.
        $response->text("Hello World");
        
        // the text function is one of the many simplified versions of the 'send' function
        // by using the send function, you can input a string and set the HTTP content type
        // the example below will send the string "<h1>Hello World</h1>" to the client and the client will see it as an HTML file.
        $response->send("<h1>Hello World</h1>", "text/html");
        
        // You can also send complete files using Response.
        // this makes it really easy to send any kind of file
        $response->sendFile("/path/to/your/file");
        
        // but remember, you can only use ONE response action at any time
        // each new response action will OVERWRITE the previous.
        // So if you want to send multiple things in a single response,
        // consider splitting it in multiple files, or sending everything in 1 response
    }
    
    // now we construct  the Route with our given method, path and action.
    $route = new Route($method, $path, $action);
```

##### Route Action
The route action is the task that is performed when a new request is sent to the correct path. The syntax for an action is
```php
function (HttpRequest $request, HttpResponse $response, mixed ...$params) {
    // your code
}
```
- `$request` is the incoming request
- `$response` is the response that will be returned to the client
- `...$params` is an array with all given parameters. 
The parameters are given at the end of a new `Route`.
```php
    $route = new \Hebbinkpro\WebServer\route\Route($method, $path, $action, ...$params);
```
You can add as many params as you want, if you only want 1 param, you can use `new Route($method, $path, $action, $param1)`, 
but if you want more than 1 you can just add them behind the first param. `new Route($method, $path, $action, $param1, $param2, $param3)`.
Adding no parameters is also an option, `new Route($method, $path, $action)`.<br>
To use the `...$params` variable in the action, you can use it as an array, so `$params[0]` will return the first parameter, and `$params[1]` will give you the second, ect.<br>
**_Do not put any reference to`$this` or the PocketMine Thread INSIDE the action function. Actions have to be `ThreadSafe`, so only things that DO NOT depend on the PocketMine thread will work._**

#### Router methods to create HTTP request routes
For the most common methods there are functions inside the `Router` instance. 
These functions make it so that you don't have to input an HTTP method for every new `Route` you want to make<br>
The available method function in `Router` are:
- GET, a route that listens only to GET requests - `Router->get($path, $action)`
- POST, a route that listens only to POST requests - `Router->post($path, $action)`
- HEAD, a route that listens only to HEAD requests - `Router->head($path, $action)`
- PUT, a route that listens only to PUT requests - `Router->put($path, $action)`
- DELETE, a route that listens only to DELETE requests - `Router->delete($path, $action)`
- USE (also known as ANY or * in `Hebbinkpro\WebServer\http\HttpMethod`), a route that listens to ALL HTTP methods - `Router->use($path, $action)` 

_You can find more info about HTTP request methods [here](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods)_<br><br>
The path and action arguments inside the router functions are the same as the once in `Router`.

#### Other Route types
There are three types of routes you can use outside the default `Route` implementations in the `Router`:
- `Route` - A basic route that makes you able to create your own responses for a path
- `RouterRoute` - A route that functions as a `Router`, but only for the specified path
- `StaticRoute` - A route that makes you able to share the content of complete folders without making a `Route` for each different path.
- You are not restricted to those routes, but you can also create your own routes. The only requirement is that your custom route has to extend (a child of) `\Hebbinkpro\WebServer\route\Route`.
You can add an instance of a `Route` to the `Router` using
```php
$router->addRoute($route)
```
But there are also functions in `Router` to easily add a `RouterRoute` or `StaticRoute`.<br>
For a `RouterRoute`:
```php
use Hebbinkpro\WebServer\route\Router;

$childRouter = new Router();
// add here the stuff you want to the child router
// ...

// add the router route with the path and the newly created child router
$router->route($path, $childRouter);
```
And for a `StaticRoute`;
```php
// define the folder you want to use for the static route by using its path
$folder = "/path/to/the/folder";

// add the static route with the path of the route and the folder path
$router->static($path, $folder)
```

### Paths
Route paths are the paths in the URL, these paths are very important because they contain the information about the page a client wants to see.
But to make sure the client sees the correct page, the path of this page needs to have a `Route`.
This is why for every `Route` you create, if it is using functions in the `Router` or by creating a new `Route` instance, 
you HAVE to provide a VALID path, otherwise a client cannot find or request your page.
#### Default paths
A path is nothing more than everything after the first `/` up to the end or `?` in the url after the address of a side.

#### prefixes
Sometimes you want a `Route` that listens to all requests that start with `/foo`, so also to `/foo/bar` or `/foo/bar/etc`.<br>
We can accomplish this by adding a `/*` to the end of a path.

#### parameters
Sometimes you want to have a prefix, but also a suffix. To accomplish this, we introduce parameters.
A parameter is a part of the path which can be any kind of string, so a path `/:var/a` will listen to `/foo/a` but also in `/bar/a`.
To make it even better, you can also request all variables inside an `HttpRequest` using `HttpRequest->getPathParams()`, this will return an array with the parameter name as key and the value set to the value in the path.

#### Queries
The query of a path is everything behind the `?` in a path, so `/?foo=bar`. There can be multiple queries after the `?` by using the `&` sign between two values.
A single query is represented as `<name>=<value>`.<br>
To request all queries you can use `HttpUrl->getQuery()`, or to request only a single value you can use `HttpRequest->getQueryParam($name)`.

## Credits
- This virion makes use of [Laravel\SerializableClosure](https://github.com/laravel/serializable-closure) for sharing the action functions given in the `Router` on the main thread with the http server thread.