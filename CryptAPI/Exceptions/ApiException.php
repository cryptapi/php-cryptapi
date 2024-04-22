<?php

namespace CryptAPI\Exceptions;

use Exception;

class ApiException extends Exception {
    protected $statusCode;

    public function __construct($message = "", $statusCode = 0, ?\Exception $previous = null) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
    }

    public static function withStatus($statusCode, $apiError = null, Exception $previous = null): ApiException
    {
        $message = $apiError ?? self::getDefaultMessageForStatusCode($statusCode);

        return new self($message, $statusCode, $previous);
    }

    // Method to get a default message based on the status code
    private static function getDefaultMessageForStatusCode($statusCode): string
    {
        switch ($statusCode) {
            case 400: return "Bad Request";
            case 401: return "Unauthorized";
            case 403: return "Forbidden";
            case 404: return "Not Found";
            case 500: return "Internal Server Error";
            default: return "An unexpected error occurred";
        }
    }
}
