# Routing

Routes are used to let your web server be able to do things. By creating a `BaseRoute` you can make your webserver
listen to
different paths and respond to them.

## Router

The router is used to register all your routes. the router will also handle all incoming requests and make sure they are
handled by the correct `BaseRoute`.<br>
You can access the router of your `WebServer` by calling:

```php
$router = $webServer->getServerInfo()->getRouter();
```

## Route

A route is used to perform an action on an incoming web request.

```php
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;
use Hebbinkpro\WebServer\route\BaseRoute;
{
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
    
    // now we construct the Route with our given method, path and action.
    $route = new BaseRoute($method, $path, $action);
}
```

### Route Action

The route action is the task performed when a new request is sent to the correct path. The syntax for an action is

```php
function (HttpRequest $request, HttpResponse $response, mixed ...$params) {
    // your code
}
```

- `$request` is the incoming request
- `$response` is the response that will be returned to the client
- `...$params` is an array with all given parameters. The parameters are given at the end of a new `BaseRoute`.

```php
    $route = new \Hebbinkpro\WebServer\route\BaseRoute($method, $action, ...$params);
```

You can add as many params as you want, if you only want 1 param, you can use
`new Route($method, $path, $action, $param1)`,
but if you want more than 1, you can add them behind the first
param. `new Route($method, $path, $action, $param1, $param2, $param3)`.
Adding no parameters is also an option, `new Route($method, $path, $action)`.<br>
To use the `...$params` variable in the action, you can use it as an array, so `$params[0]` will return the first
parameter, and `$params[1]` will give you the second, ect.<br>
**_Do not put any code that makes use of the main thread INSIDE the action function. Actions have to be `ThreadSafe`, so
only things that DO NOT depend on the PocketMine thread will work. If you want to use classes that do not
extend `pmmp\thread\ThreadSafe`, you can use `serialize(...)` for the parameter and `unserialize(...)` inside the action
function _**

## Router methods to create HTTP request routes

For the most common methods there are functions inside the `Router` instance. These functions make it so that you don't
have to input an HTTP method for every new `BaseRoute` you want to make<br>
The available method function in `Router` are:

- GET, a route that only listens to GET requests - `Router->get($path, $action, ...$params)`
- POST, a route that only listens to POST requests - `Router->post($path, $action, ...$params)`
- HEAD, a route that only listens to HEAD requests - `Router->head($path, $action, ...$params)`
- PUT, a route that only listens to PUT requests - `Router->put($path, $action, ...$params)`
- DELETE, a route that only listens to DELETE requests - `Router->delete($path, $action, ...$params)`
- USE (also known as ANY or * in `Hebbinkpro\WebServer\http\HttpMethod`), a route that listens to ALL HTTP
  methods - `Router->all($path, $action, ...$params)`

_You can find more info about HTTP request
methods [here](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods)_<br><br>
The path and action arguments inside the router functions are the same as the once in `Router`.

## Other Route types

There are three types of routes you can use outside the default `BaseRoute` implementations in the `Router`:

- `BaseRoute` - A basic route that makes you able to create your own responses for a path
- `FileRoute` - A route that sends a file as response
- `RouterRoute` - A route that functions as a `Router`, but only for the specified path
- `StaticRoute` - A route that makes you able to share the content of complete folders without making a `BaseRoute` for
  each
  different path.
- You are not restricted to those routes, but you can also create your own routes. The only requirement is that your
  custom route has to extend (a child of) `\Hebbinkpro\WebServer\route\Route`. You can add an instance of a `BaseRoute`
  to the `Router` using

```php
$router->addRoute($route)
```

But there are also functions in `Router` to easily add a `FileRoute`, `RouterRoute` or `StaticRoute`.

### FileRoute

```php
$file = "path/to/your/file";
$default = "File not found";

// add the file route, $default is optional
$router->getFile($path, $file, $default);
```

### RouterRoute

```php
use Hebbinkpro\WebServer\router\Router;

$childRouter = new Router();
// add here the stuff you want to the child router
// this is the same as for a default router
// ...

// add the router route with the path and the newly created child router
$router->route($path, $childRouter);
```

### Static Route

```php
// define the folder you want to use for the static route by using its path
$folder = "/path/to/the/folder";

// add the static route with the path of the route and the folder path
$router->getStatic($path, $folder)
```