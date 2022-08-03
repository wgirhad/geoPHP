<?php

namespace geoPHP\Tests\Unit\Exception;

use geoPHP\Exception\Exception;
use geoPHP\Exception\UnsupportedMethodException;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass geoPHP\Exception\UnsupportedMethodException
 */
class UnsupportedMethodExceptionTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $e = new UnsupportedMethodException("Test");

        $this->assertInstanceOf(Exception::class, $e);
        $this->assertInstanceOf(LogicException::class, $e);

        $this->assertSame(0, $e->getCode());
        $this->assertNull($e->getPrevious());
        $this->assertSame('Method Test() is not supported yet.', $e->getMessage());
    }

    public function testConstruct(): void
    {
        $previousStub = $this->getMockForAbstractClass(Exception::class);
        $e = new UnsupportedMethodException("Test", "Error message", 1, $previousStub);

        $this->assertSame('Method Test() is not supported yet. Error message', $e->getMessage());
    }

    public function testGeos(): void
    {
        $e = UnsupportedMethodException::geos("Test");

        $this->assertInstanceOf(UnsupportedMethodException::class, $e);
        $this->assertSame('Method Test() is not supported yet. Please install GEOS extension.', $e->getMessage());
    }
}
