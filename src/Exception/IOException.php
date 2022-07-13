<?php

namespace geoPHP\Exception;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when an error occurs on reading or writing
 */
class IOException extends RuntimeException implements Exception
{
    /**
     * @param string|null    $message Additional message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(?string $message = null, int $code = 0, ?Throwable $previous = null)
    {
        $message = 'IO error: ' . $message;
        parent::__construct($message, $code, $previous);
    }
}
