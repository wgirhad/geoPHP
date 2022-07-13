<?php

namespace geoPHP\Tests\Exception;

use geoPHP\Exception\Exception;
use geoPHP\Exception\InvalidGeometryException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @coversDefaultClass geoPHP\Exception\InvalidGeometryExceptionTest
 */
class InvalidGeometryExceptionTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $e = new InvalidGeometryException();

        $this->assertInstanceOf(Exception::class, $e);
        $this->assertInstanceOf(RuntimeException::class, $e);
    }
}
