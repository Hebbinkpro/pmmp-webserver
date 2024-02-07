<?php

namespace Hebbinkpro\WebServer\http;

/**
 * All known HTTP Request Methods according to the [Mozilla web docs](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods).
 */
enum HttpMethod: string
{
    case ALL = "*";
    case GET = "GET";
    case HEAD = "HEAD";
    case POST = "POST";
    case PUT = "PUT";
    case DELETE = "DELETE";
    case CONNECT = "CONNECT";
    case OPTIONS = "OPTIONS";
    case TRACE = "TRACE";
    case PATCH = "PATCH";
}