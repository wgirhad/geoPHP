<?php

namespace geoPHP\Exception;

/**
 * Should be thrown if a method is not implemented yet
 */
class UnsupportedMethodException extends \Exception
{
    /**
     * Public constructor.
     *
     * @param string $method Name of the unsupported method
     * @param string|null $message Additional message
     * @param int $code
     */
    public function __construct(string $method, ?string $message = null, int $code = 0)
    {
        $message = 'Method ' . $method . '() is not supported yet.' . ($message ? ' ' . $message : '');
        parent::__construct($message, $code);
    }

    /**
     * Method is supported only with GEOS installed
     *
     * @param string $methodName Name of the unsupported method
     * @return UnsupportedMethodException
     */
    public static function geos(string $methodName): self
    {
        return new self($methodName, 'Please install GEOS extension.', 1);
    }
}
