<?php

namespace Hebbinkpro\WebServer\http\response;

/**
 * HTTP Status used for an HTTP Response
 */
class HttpResponseStatus
{
    private static array $STATUS_CODES = [];

    private int $code;
    private string $message;

    public function __construct(int $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * Get the HttpStatus corresponding to the status code
     * @param int $code
     * @return HttpResponseStatus
     */
    public static function get(int $code): HttpResponseStatus
    {
        return self::$STATUS_CODES["$code"];
    }

    public static function registerAll(): void
    {
        // TODO: why? I think we can solve a lot of issues by converting this to an SingletonTrait

        // Information responses
        self::register(new HttpResponseStatus(100, "Continue"));
        self::register(new HttpResponseStatus(101, "Switching Protocols"));
        self::register(new HttpResponseStatus(102, "Processing"));
        self::register(new HttpResponseStatus(103, "Early Hints"));

        // Successful responses
        self::register(new HttpResponseStatus(200, "OK"));
        self::register(new HttpResponseStatus(201, "Created"));
        self::register(new HttpResponseStatus(202, "Accepted"));
        self::register(new HttpResponseStatus(203, "Non-Authoritative Information"));
        self::register(new HttpResponseStatus(204, "No Content"));
        self::register(new HttpResponseStatus(205, "Reset Content"));
        self::register(new HttpResponseStatus(206, "Partial Content"));
        self::register(new HttpResponseStatus(207, "Multi-Status"));
        self::register(new HttpResponseStatus(208, "Already Reported"));
        self::register(new HttpResponseStatus(226, "IM Used"));

        // Redirection messages
        self::register(new HttpResponseStatus(300, "Multiple Choices"));
        self::register(new HttpResponseStatus(301, "Moved Permanently"));
        self::register(new HttpResponseStatus(302, "Found"));
        self::register(new HttpResponseStatus(303, "See Other"));
        self::register(new HttpResponseStatus(304, "Not Modified"));
        self::register(new HttpResponseStatus(307, "Temporary Redirect"));
        self::register(new HttpResponseStatus(308, "Permanent Redirect"));

        // Client error responses
        self::register(new HttpResponseStatus(400, "Bad Request"));
        self::register(new HttpResponseStatus(401, "Unauthorized"));
        self::register(new HttpResponseStatus(402, "Payment Required"));
        self::register(new HttpResponseStatus(403, "Forbidden"));
        self::register(new HttpResponseStatus(404, "Not Found"));
        self::register(new HttpResponseStatus(405, "Method Not Allowed"));
        self::register(new HttpResponseStatus(406, "Not Acceptable"));
        self::register(new HttpResponseStatus(407, "Proxy Authentication Required"));
        self::register(new HttpResponseStatus(408, "Request Timeout"));
        self::register(new HttpResponseStatus(409, "Conflict"));
        self::register(new HttpResponseStatus(410, "Gone"));
        self::register(new HttpResponseStatus(411, "Length Required"));
        self::register(new HttpResponseStatus(412, "Precondition Failed"));
        self::register(new HttpResponseStatus(413, "Payload Too Large"));
        self::register(new HttpResponseStatus(414, "URI Too Long"));
        self::register(new HttpResponseStatus(415, "Unsupported Media Type"));
        self::register(new HttpResponseStatus(416, "Range Not Satisfiable"));
        self::register(new HttpResponseStatus(417, "Expectation Failed"));
        self::register(new HttpResponseStatus(418, "I'm a Teapot"));
        self::register(new HttpResponseStatus(421, "Misdirected Request"));
        self::register(new HttpResponseStatus(422, "Unprocessable Entity"));
        self::register(new HttpResponseStatus(423, "Locked"));
        self::register(new HttpResponseStatus(424, "Failed Dependency"));
        self::register(new HttpResponseStatus(425, "Too Early"));
        self::register(new HttpResponseStatus(426, "Upgrade Required"));
        self::register(new HttpResponseStatus(428, "Precondition Required"));
        self::register(new HttpResponseStatus(429, "Too Many Requests"));
        self::register(new HttpResponseStatus(431, "Request Header Fields Too Large"));
        self::register(new HttpResponseStatus(451, "Unavailable For Legal Reasons"));

        // Server error responses
        self::register(new HttpResponseStatus(500, "Internal Server Error"));
        self::register(new HttpResponseStatus(501, "Not Implemented"));
        self::register(new HttpResponseStatus(502, "Bad Gateway"));
        self::register(new HttpResponseStatus(503, "Service Unavailable"));
        self::register(new HttpResponseStatus(504, "Gateway Timeout"));
        self::register(new HttpResponseStatus(505, "HTTP Version Not Supported"));
        self::register(new HttpResponseStatus(506, "Variant Also Negotiates"));
        self::register(new HttpResponseStatus(507, "Insufficient Storage"));
        self::register(new HttpResponseStatus(508, "Loop Detected"));
        self::register(new HttpResponseStatus(510, "Not Extended"));
        self::register(new HttpResponseStatus(511, "Network Authentication Required"));
    }

    /**
     * Register a new HTTP Status
     * @param HttpResponseStatus $statusCode
     * @return void
     */
    public static function register(HttpResponseStatus $statusCode): void
    {
        $code = $statusCode->getCode();
        self::$STATUS_CODES["$code"] = $statusCode;
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
    public function __toString(): string
    {
        // TODO: convert to decode function
        return $this->code . " " . $this->message;
    }
}