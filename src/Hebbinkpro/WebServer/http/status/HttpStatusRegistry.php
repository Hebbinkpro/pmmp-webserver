<?php

namespace Hebbinkpro\WebServer\http\status;

use pocketmine\utils\SingletonTrait;

/**
 * A registry for all (known) HTTP status codes with their respective status message.
 */
final class HttpStatusRegistry
{
    use SingletonTrait;

    /** @var array<int, HttpStatus> */
    private array $statusCodes = [];

    public function __construct()
    {
        // Informational
        $this->register(new HttpStatus(100, "Continue"));
        $this->register(new HttpStatus(101, "Switching Protocols"));
        $this->register(new HttpStatus(102, "Processing"));
        $this->register(new HttpStatus(103, "Early Hints"));

        // Successful
        $this->register(new HttpStatus(200, "OK"));
        $this->register(new HttpStatus(201, "Created"));
        $this->register(new HttpStatus(202, "Accepted"));
        $this->register(new HttpStatus(203, "Non-Authoritative Information"));
        $this->register(new HttpStatus(204, "No Content"));
        $this->register(new HttpStatus(205, "Reset Content"));
        $this->register(new HttpStatus(206, "Partial Content"));
        $this->register(new HttpStatus(207, "Multi-Status"));
        $this->register(new HttpStatus(208, "Already Reported"));
        // ...
        $this->register(new HttpStatus(226, "IM Used"));

        // Redirect
        $this->register(new HttpStatus(300, "Multiple Choices"));
        $this->register(new HttpStatus(301, "Moved Permanently"));
        $this->register(new HttpStatus(302, "Found"));
        $this->register(new HttpStatus(303, "See Other"));
        $this->register(new HttpStatus(304, "Not Modified"));
        $this->register(new HttpStatus(305, "Use Proxy"));
        $this->register(new HttpStatus(306, "unused"));
        $this->register(new HttpStatus(307, "Temporary Redirect"));
        $this->register(new HttpStatus(308, "Permanent Redirect"));

        // Client error
        $this->register(new HttpStatus(400, "Bad Request"));
        $this->register(new HttpStatus(401, "Unauthorized"));
        $this->register(new HttpStatus(402, "Payment Required"));
        $this->register(new HttpStatus(403, "Forbidden"));
        $this->register(new HttpStatus(404, "Not Found"));
        $this->register(new HttpStatus(405, "Method Not Allowed"));
        $this->register(new HttpStatus(406, "Not Acceptable"));
        $this->register(new HttpStatus(407, "Proxy Authentication Required"));
        $this->register(new HttpStatus(408, "Request Timeout"));
        $this->register(new HttpStatus(409, "Conflict"));
        $this->register(new HttpStatus(410, "Gone"));
        $this->register(new HttpStatus(411, "Length Required"));
        $this->register(new HttpStatus(412, "Precondition Failed"));
        $this->register(new HttpStatus(413, "Payload Too Large"));
        $this->register(new HttpStatus(414, "URI Too Long"));
        $this->register(new HttpStatus(415, "Unsupported Media Type"));
        $this->register(new HttpStatus(416, "Range Not Satisfiable"));
        $this->register(new HttpStatus(417, "Expectation Failed"));
        $this->register(new HttpStatus(418, "I'm a Teapot"));
        // ...
        $this->register(new HttpStatus(421, "Misdirected Request"));
        $this->register(new HttpStatus(422, "Unprocessable Entity"));
        $this->register(new HttpStatus(423, "Locked"));
        $this->register(new HttpStatus(424, "Failed Dependency"));
        $this->register(new HttpStatus(425, "Too Early"));
        $this->register(new HttpStatus(426, "Upgrade Required"));
        // ...
        $this->register(new HttpStatus(428, "Precondition Required"));
        $this->register(new HttpStatus(429, "Too Many Requests"));
        // ...
        $this->register(new HttpStatus(431, "Request Header Fields Too Large"));
        // ...
        $this->register(new HttpStatus(451, "Unavailable For Legal Reasons"));

        // Server error
        $this->register(new HttpStatus(500, "Internal Server Error"));
        $this->register(new HttpStatus(501, "Not Implemented"));
        $this->register(new HttpStatus(502, "Bad Gateway"));
        $this->register(new HttpStatus(503, "Service Unavailable"));
        $this->register(new HttpStatus(504, "Gateway Timeout"));
        $this->register(new HttpStatus(505, "HTTP Version Not Supported"));
        $this->register(new HttpStatus(506, "Variant Also Negotiates"));
        $this->register(new HttpStatus(507, "Insufficient Storage"));
        $this->register(new HttpStatus(508, "Loop Detected"));
        // ...
        $this->register(new HttpStatus(510, "Not Extended"));
        $this->register(new HttpStatus(511, "Network Authentication Required"));
    }

    /**
     * Register a new HTTP Status code
     * @param HttpStatus $status
     * @return void
     */
    public function register(HttpStatus $status): void
    {
        $this->statusCodes[$status->getCode()] = $status;
    }

    /**
     * Get an HTTP status from its code
     * @param int $statusCode
     * @return HttpStatus|null
     */
    public function get(int $statusCode): ?HttpStatus
    {
        return $this->statusCodes[$statusCode] ?? null;
    }

    /**
     * Parse an HTTP Status from an integer or itself.
     *
     * This function can be used in code so that you don't have to write this code yourself if you want to accept both options.
     * @param int|HttpStatus $status
     * @return HttpStatus|null
     */
    public function parse(int|HttpStatus $status): ?HttpStatus
    {
        if (is_int($status)) $status = $this->statusCodes[$status] ?? null;
        return $status;
    }


}