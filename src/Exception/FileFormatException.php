<?php

namespace geoPHP\Exception;

use Throwable;

/**
 * Exception thrown if an adapter can't parse input data.
 */
class FileFormatException extends IOException
{
    /**
     * @param string|null    $message Additional message
     * @param string|null    $invalidData The data that couldn't be interpreted. Will be cut down to a short sample.
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(
        ?string $message = null,
        string $invalidData = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        if ($invalidData) {
            $invalidData = strlen($invalidData) <= 50 ? $invalidData : substr($invalidData, 0, 49) . "…";
            $message .= " Data: \"$invalidData\"";
        }
        parent::__construct($message, $code, $previous);
    }
}
