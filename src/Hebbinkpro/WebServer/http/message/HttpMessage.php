<?php

namespace Hebbinkpro\WebServer\http\message;

use Hebbinkpro\WebServer\http\HttpMessageHeaders;
use Hebbinkpro\WebServer\http\HttpVersion;

/**
 * Interface for all methods required inside a HTTP Message
 */
interface HttpMessage
{
    /**
     * Get the HTTP version of the message
     * @return HttpVersion
     */
    public function getVersion(): HttpVersion;

    /**
     * Get the HTTP message headers
     * @return HttpMessageHeaders
     */
    public function getHeaders(): HttpMessageHeaders;

    /**
     * Get the HTTP message body
     * @return string
     */
    public function getBody(): string;

}