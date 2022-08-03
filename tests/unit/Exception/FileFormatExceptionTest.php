<?php

namespace geoPHP\Tests\Unit\Exception;

use geoPHP\Exception\Exception;
use geoPHP\Exception\FileFormatException;
use geoPHP\Exception\IOException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass geoPHP\Exception\FileFormatException
 */
class FileFormatExceptionTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $e = new FileFormatException();

        $this->assertInstanceOf(Exception::class, $e);
        $this->assertInstanceOf(IOException::class, $e);

        $this->assertSame(0, $e->getCode());
        $this->assertNull($e->getPrevious());
        $this->assertSame('IO error: ', $e->getMessage());
    }

    public function testConstruct(): void
    {
        $previousStub = $this->getMockForAbstractClass(Exception::class);
        $e = new FileFormatException("Error message", '<xml malformed>', 1, $previousStub);

        $this->assertSame('IO error: Error message Data: "<xml malformed>"', $e->getMessage());
    }
}
