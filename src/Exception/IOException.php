<?php

namespace geoPHP\Exception;

/**
 * Exception thrown when an error occurs on reading or writing
 */
class IOException extends \Exception
{

    /**
     * @param string|null $message Additional message
     * @param int         $code
     */
    public function __construct($message, $code = 0)
    {
        $message = 'IO error: ' . $message;
        parent::__construct($message, $code);
    }

    /**
     * @param string $message
     *
     * @return IOException
     */
    public static function invalidGPX($message)
    {
        return new static('Invalid GPX. ' . $message, null);
    }
}