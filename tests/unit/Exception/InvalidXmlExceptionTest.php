<?php

namespace geoPHP\Tests\Unit\Exception;

use geoPHP\Exception\Exception;
use geoPHP\Exception\InvalidXmlException;
use geoPHP\Exception\IOException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass geoPHP\Exception\InvalidXmlException
 */
class InvalidXmlExceptionTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $e = new InvalidXmlException();

        $this->assertInstanceOf(Exception::class, $e);
        $this->assertInstanceOf(IOException::class, $e);

        $this->assertSame(0, $e->getCode());
        $this->assertNull($e->getPrevious());
        $this->assertSame('IO error: Invalid XML.', $e->getMessage());
    }

    public function testConstruct(): void
    {
        $previousStub = $this->getMockForAbstractClass(Exception::class);
        $e = new InvalidXmlException("Error message", 1, $previousStub);

        $this->assertSame('IO error: Invalid XML. Error message', $e->getMessage());
    }
}
