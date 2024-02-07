<?php

namespace Hebbinkpro\WebServer\http\status;

use pmmp\thread\ThreadSafe;

/**
 * HTTP Status used for an HTTP Response
 */
class HttpStatus extends ThreadSafe
{
    private int $code;
    private string $message;

    public function __construct(int $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * Get the status code
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get the status message
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->code . " " . $this->message;
    }
}