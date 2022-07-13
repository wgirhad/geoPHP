<?php

namespace geoPHP\Exception;

use Throwable;

/**
 * Exception thrown if XML parser can't load input data.
 */
class InvalidXmlException extends IOException
{
    /**
     * @param string|null    $message Additional message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(?string $message = null, int $code = 0, ?Throwable $previous = null)
    {
        $message = 'Invalid XML.' . ($message ? ' ' . $message : '');
        parent::__construct($message, $code, $previous);
    }
}
