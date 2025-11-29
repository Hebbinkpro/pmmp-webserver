# Paths

Route paths are the paths in the URL, these paths are very important because they contain the information about the page
a client wants to see.
But to make sure the client sees the correct page, the path of this page needs to have a `Route`.
This is why for every `Route` you create, if it is using functions in the `Router` or by creating a new `Route`
instance,
you HAVE to provide a VALID path, otherwise a client cannot find or request your page.

## Default paths

A path is nothing more than everything after the first `/` up to the end or `?` in the uri after the address of a side.

## prefixes

Sometimes you want a `Route` that listens to all requests that start with `/foo`, so also to `/foo/bar` or
`/foo/bar/etc`.<br>
We can accomplish this by adding a `/*` to the end of a path.

## parameters

Sometimes you want to have a prefix, but also a suffix. To accomplish this, we introduce parameters.
A parameter is a part of the path which can be any kind of string, so a path `/:var/a` will listen to `/foo/a` but also
in `/bar/a`.
To make it even better, you can also request all variables inside an `HttpRequest` using `HttpRequest->getPathParams()`,
this will return an array with the parameter name as key and the value set to the value in the path.

## Queries

The query of a path is everything behind the `?` in a path, so `/?foo=bar`. There can be multiple queries after the `?`
by using the `&` sign between two values.
A single query is represented as `<name>=<value>`.<br>
To request all queries you can use `HttpURI->getQuery()`, or to request only a single value you can
use `HttpURI->getQueryParam($name)`.