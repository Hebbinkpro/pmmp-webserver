<?php

namespace Hebbinkpro\WebServer\http;

/**
 * All known HTTP Request Methods according to the [Mozilla web docs](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods).
 */
enum HttpMethod: string
{
    /** Not part of the HTTP method spec, but is used as part of the routing process in Router */
    case ALL = "*";

    /** Transfer a current representation of the target resource. */
    case GET = "GET";
    /** Same as GET, but do not transfer the response content. */
    case HEAD = "HEAD";
    /** Perform resource-specific processing on the request content. */
    case POST = "POST";
    /** Replace all current representations of the target resource with the request content. */
    case PUT = "PUT";
    /** Remove all current representations of the target resource. */
    case DELETE = "DELETE";
    /** Establish a tunnel to the server identified by the target resource. */
    case CONNECT = "CONNECT";
    /** Describe the communication options for the target resource. */
    case OPTIONS = "OPTIONS";
    /** Perform a message loop-back test along the path to the target resource. */
    case TRACE = "TRACE";
    /** Make partial changes to an existing resource. */
    case PATCH = "PATCH";
}