<?php

namespace geoPHP\Tests\Exception;

use geoPHP\Exception\Exception;
use geoPHP\Exception\FileFormatException;
use geoPHP\Exception\IOException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass geoPHP\Exception\FileFormatExceptionTest
 */
class FileFormatExceptionTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $e = new FileFormatException();

        $this->assertInstanceOf(Exception::class, $e);
        $this->assertInstanceOf(IOException::class, $e);
    }
}
