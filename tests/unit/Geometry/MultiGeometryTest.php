<?php

namespace geoPHP\Tests\Unit\Geometry;

use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\MultiGeometry;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of abstract geometry MultiGeometry
 *
 * @group geometry
 * @coversDefaultClass geoPHP\Geometry\MultiGeometry
 *
 * @uses geoPHP\Geometry\Point
 */
class MultiGeometryTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $geom1 = $this->getMockForAbstractClass(Geometry::class, []);

        $this->assertInstanceOf(
            MultiGeometry::class,
            $this->getMockForAbstractClass(MultiGeometry::class, [[$geom1]])
        );

        $this->assertInstanceOf(
            MultiGeometry::class,
            $this->getMockForAbstractClass(MultiGeometry::class, [[$geom1], Geometry::class])
        );

        $this->assertInstanceOf(
            MultiGeometry::class,
            $this->getMockForAbstractClass(MultiGeometry::class, [[$geom1], Geometry::class, true])
        );
    }

    /**
     * @return array<mixed>
     */
    public function providerIsSimple(): array
    {
        return [
            'empty' =>
                [[], true],
            'two point' =>
                [[new Point(), new Point()], true],
            'two curves, second is empty' =>
                [[[[0, 0], [1, 1]], []]],
        ];
    }

    public function testIsSimple(): void
    {
        $simpleGeom = $this->getMockForAbstractClass(Geometry::class);
        $simpleGeom->expects($this->any())
            ->method('isSimple')
            ->willReturn(true);
        $nonSimpleGeom = $this->getMockForAbstractClass(Geometry::class);
        $nonSimpleGeom->expects($this->any())
            ->method('isSimple')
            ->willReturn(false);

        // Empty components => simple
        $stub = $this->getMockForAbstractClass(MultiGeometry::class, [[]]);
        $this->assertTrue($stub->isSimple());

        // Simple components => simple
        $stub = $this->getMockForAbstractClass(MultiGeometry::class, [[$simpleGeom, $simpleGeom]]);
        $this->assertTrue($stub->isSimple());

        // One simple and one non simple component => not simple
        $stub = $this->getMockForAbstractClass(MultiGeometry::class, [[$simpleGeom, $nonSimpleGeom]]);
        $this->assertFalse($stub->isSimple());

        // Two non simple component => not simple
        $stub = $this->getMockForAbstractClass(MultiGeometry::class, [[$nonSimpleGeom, $nonSimpleGeom]]);
        $this->assertFalse($stub->isSimple());
    }
}
