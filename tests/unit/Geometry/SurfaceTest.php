<?php

namespace geoPHP\Tests\Unit\Geometry;

use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\Polygon;
use geoPHP\Geometry\Surface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of abstract Surface geometry
 *
 * @group geometry
 * @coversDefaultClass geoPHP\Geometry\Surface
 *
 * @uses geoPHP\Geometry\Point
 * @uses geoPHP\Geometry\LineString
 */
class SurfaceTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $stub = $this->getMockForAbstractClass(Surface::class, [[]]);

        $this->assertNotNull($stub);
    }

    /**
     * @covers ::geometryType
     */
    public function testGeometryType(): void
    {
        $stub = $this->getMockForAbstractClass(Surface::class, [[]]);

        $this->assertEquals(Polygon::SURFACE, $stub->geometryType());

        $this->assertInstanceOf(Surface::class, $stub);
        $this->assertInstanceOf(\geoPHP\Geometry\Collection::class, $stub);
        $this->assertInstanceOf(Geometry::class, $stub);
    }

    /**
     * @covers ::dimension
     */
    public function testDimension(): void
    {
        $stub = $this->getMockForAbstractClass(Surface::class, [[]]);

        $this->assertSame(2, $stub->dimension());
    }

    /**
     * @covers ::isEmpty
     */
    public function testIsEmpty(): void
    {
        $stub = $this->getMockForAbstractClass(Surface::class, [[]]);
        $this->assertTrue($stub->isEmpty());

        $geometryStub = $this->getMockForAbstractClass(Geometry::class, []);
        $stub = $this->getMockForAbstractClass(Surface::class, [[$geometryStub]]);
        $this->assertFalse($stub->isEmpty());
    }

    /**
     * @covers ::startPoint
     * @covers ::endPoint
     * @covers ::pointN
     * @covers ::isClosed
     * @covers ::isRing
     * @covers ::length
     * @covers ::length3D
     * @covers ::haversineLength
     * @covers ::vincentyLength
     * @covers ::greatCircleLength
     * @covers ::minimumZ
     * @covers ::maximumZ
     * @covers ::minimumM
     * @covers ::maximumM
     * @covers ::elevationGain
     * @covers ::elevationLoss
     * @covers ::zDifference
     */
    public function testTrivialMethods(): void
    {
        $stub = $this->getMockForAbstractClass(Surface::class, [[]]);

        $this->assertSame(null, $stub->startPoint());

        $this->assertSame(null, $stub->endPoint());

        $this->assertSame(null, $stub->pointN(1));

        $this->assertSame(null, $stub->isClosed());

        $this->assertSame(null, $stub->isRing());

        $this->assertSame(0.0, $stub->length());

        $this->assertSame(0.0, $stub->length3D());

        $this->assertSame(0.0, $stub->haversineLength());

        $this->assertSame(0.0, $stub->vincentyLength());

        $this->assertSame(0.0, $stub->greatCircleLength());

        $this->assertSame(null, $stub->minimumZ());

        $this->assertSame(null, $stub->maximumZ());

        $this->assertSame(null, $stub->minimumM());

        $this->assertSame(null, $stub->maximumM());

        $this->assertSame(null, $stub->elevationGain());

        $this->assertSame(null, $stub->elevationLoss());

        $this->assertSame(null, $stub->zDifference());
    }
}
