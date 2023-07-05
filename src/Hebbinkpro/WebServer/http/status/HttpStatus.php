<?php

namespace Hebbinkpro\WebServer\http\status;

class HttpStatus
{
    private static array $STATUS_CODES = [];

    private readonly int $code;
    private readonly string $message;

    public function __construct(int $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    public static function get(int $code): HttpStatus
    {
        return self::$STATUS_CODES["$code"];
    }

    public static function registerAll(): void
    {
        // Information responses
        self::register(new HttpStatus(100, "Continue"));
        self::register(new HttpStatus(101, "Switching Protocols"));
        self::register(new HttpStatus(102, "Processing"));
        self::register(new HttpStatus(103, "Early Hints"));

        // Successful responses
        self::register(new HttpStatus(200, "OK"));
        self::register(new HttpStatus(201, "Created"));
        self::register(new HttpStatus(202, "Accepted"));
        self::register(new HttpStatus(203, "Non-Authoritative Information"));
        self::register(new HttpStatus(204, "No Content"));
        self::register(new HttpStatus(205, "Reset Content"));
        self::register(new HttpStatus(206, "Partial Content"));
        self::register(new HttpStatus(207, "Multi-Status"));
        self::register(new HttpStatus(208, "Already Reported"));
        self::register(new HttpStatus(226, "IM Used"));

        // Redirection messages
        self::register(new HttpStatus(300, "Multiple Choices"));
        self::register(new HttpStatus(301, "Moved Permanently"));
        self::register(new HttpStatus(302, "Found"));
        self::register(new HttpStatus(303, "See Other"));
        self::register(new HttpStatus(304, "Not Modified"));
        self::register(new HttpStatus(307, "Temporary Redirect"));
        self::register(new HttpStatus(308, "Permanent Redirect"));

        // Client error responses
        self::register(new HttpStatus(400, "Bad Request"));
        self::register(new HttpStatus(401, "Unauthorized"));
        self::register(new HttpStatus(402, "Payment Required"));
        self::register(new HttpStatus(403, "Forbidden"));
        self::register(new HttpStatus(404, "Not Found"));
        self::register(new HttpStatus(405, "Method Not Allowed"));
        self::register(new HttpStatus(406, "Not Acceptable"));
        self::register(new HttpStatus(407, "Proxy Authentication Required"));
        self::register(new HttpStatus(408, "Request Timeout"));
        self::register(new HttpStatus(409, "Conflict"));
        self::register(new HttpStatus(410, "Gone"));
        self::register(new HttpStatus(411, "Length Required"));
        self::register(new HttpStatus(412, "Precondition Failed"));
        self::register(new HttpStatus(413, "Payload Too Large"));
        self::register(new HttpStatus(414, "URI Too Long"));
        self::register(new HttpStatus(415, "Unsupported Media Type"));
        self::register(new HttpStatus(416, "Range Not Satisfiable"));
        self::register(new HttpStatus(417, "Expectation Failed"));
        self::register(new HttpStatus(418, "I'm a Teapot"));
        self::register(new HttpStatus(421, "Misdirected Request"));
        self::register(new HttpStatus(422, "Unprocessable Entity"));
        self::register(new HttpStatus(423, "Locked"));
        self::register(new HttpStatus(424, "Failed Dependency"));
        self::register(new HttpStatus(425, "Too Early"));
        self::register(new HttpStatus(426, "Upgrade Required"));
        self::register(new HttpStatus(428, "Precondition Required"));
        self::register(new HttpStatus(429, "Too Many Requests"));
        self::register(new HttpStatus(431, "Request Header Fields Too Large"));
        self::register(new HttpStatus(451, "Unavailable For Legal Reasons"));

        // Server error responses
        self::register(new HttpStatus(500, "Internal Server Error"));
        self::register(new HttpStatus(501, "Not Implemented"));
        self::register(new HttpStatus(502, "Bad Gateway"));
        self::register(new HttpStatus(503, "Service Unavailable"));
        self::register(new HttpStatus(504, "Gateway Timeout"));
        self::register(new HttpStatus(505, "HTTP Version Not Supported"));
        self::register(new HttpStatus(506, "Variant Also Negotiates"));
        self::register(new HttpStatus(507, "Insufficient Storage"));
        self::register(new HttpStatus(508, "Loop Detected"));
        self::register(new HttpStatus(510, "Not Extended"));
        self::register(new HttpStatus(511, "Network Authentication Required"));
    }

    public static function register(HttpStatus $statusCode): void
    {
        $code = $statusCode->getCode();
        self::$STATUS_CODES["$code"] = $statusCode;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->code . " " . $this->message;
    }
}