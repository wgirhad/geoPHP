<?php

namespace geoPHP\Tests\Unit\Exception;

use geoPHP\Exception\Exception;
use geoPHP\Exception\IOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @coversDefaultClass geoPHP\Exception\IOException
 */
class IOExceptionTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $e = new IOException();

        $this->assertInstanceOf(Exception::class, $e);
        $this->assertInstanceOf(RuntimeException::class, $e);

        $this->assertSame(0, $e->getCode());
        $this->assertNull($e->getPrevious());
        $this->assertSame('IO error: ', $e->getMessage());
    }

    public function testConstruct(): void
    {
        $previousStub = $this->getMockForAbstractClass(Exception::class);
        $e = new IOException("Error message", 1, $previousStub);

        $this->assertSame('IO error: Error message', $e->getMessage());
    }
}
