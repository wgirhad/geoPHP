<?php

namespace geoPHP\Tests\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\MultiPoint;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of MultiPoint geometry
 *
 * @group geometry
 *
 */
class MultiPointTest extends TestCase
{
    public function providerValidComponents()
    {
        return [
            [[]],                                   // no components, empty MultiPoint
            [[new Point()]],                        // empty component
            [[new Point(1, 2)]],
            [[new Point(1, 2), new Point(3, 4)]],
            [[new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)]],
        ];
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param Point[] $points
     */
    public function testValidComponents($points)
    {
        $this->assertNotNull(new MultiPoint($points));
    }

    public function providerInvalidComponents()
    {
        return [
            [[\geoPHP\Geometry\LineString::fromArray([[1,2],[3,4]])]],  // wrong component type
        ];
    }

    /**
     * @dataProvider providerInvalidComponents
     *
     * @param mixed $components
     */
    public function testConstructorWithInvalidComponents($components)
    {
        $this->expectException(InvalidGeometryException::class);

        new MultiPoint($components);
    }

    public function testGeometryType()
    {
        $multiPoint = new MultiPoint();

        $this->assertEquals(\geoPHP\Geometry\Geometry::MULTI_POINT, $multiPoint->geometryType());

        $this->assertInstanceOf('\geoPHP\Geometry\MultiPoint', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\MultiGeometry', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\Geometry', $multiPoint);
    }

    public function testIs3D()
    {
        $this->assertTrue((new Point(1, 2, 3))->is3D());
        $this->assertTrue((new Point(1, 2, 3, 4))->is3D());
        $this->assertTrue((new Point(null, null, 3, 4))->is3D());
    }

    public function testIsMeasured()
    {
        $this->assertTrue((new Point(1, 2, null, 4))->isMeasured());
        $this->assertTrue((new Point(null, null, null, 4))->isMeasured());
    }

    public function providerCentroid()
    {
        return [
            [[], []],
            [[[0, 0], [0, 10]], [0, 5]]
        ];
    }

    /**
     * @dataProvider providerCentroid
     *
     * @param array $components
     * @param array $centroid
     */
    public function testCentroid($components, $centroid)
    {
        $multiPoint = MultiPoint::fromArray($components);

        $this->assertEquals(Point::fromArray($centroid), $multiPoint->centroid());
    }

    public function providerIsSimple()
    {
        return [
            [[], true],
            [[[0, 0], [0, 10]], true],
            [[[1, 1], [2, 2], [1, 3], [1, 2], [2, 1]], true],
            [[[0, 10], [0, 10]], false],
        ];
    }

    /**
     * @dataProvider providerIsSimple
     *
     * @param array $points
     * @param bool  $result
     */
    public function testIsSimple($points, $result)
    {
        $multiPoint = MultiPoint::fromArray($points);

        $this->assertSame($result, $multiPoint->isSimple());
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param array $points
     */
    public function testNumPoints($points)
    {
        $multiPoint = new MultiPoint($points);

        $this->assertEquals(count($points), $multiPoint->numPoints());
        $this->assertEquals($multiPoint->numPoints(), $multiPoint->numGeometries());
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param array $points
     */
    public function testNumGeometries($points)
    {
        $multiPoint = new MultiPoint($points);

        $this->assertEquals(count($points), $multiPoint->numGeometries());
    }

    public function testTrivialAndNotValidMethods()
    {
        $point = new MultiPoint();

        $this->assertSame(0, $point->dimension());

        $this->assertEquals(new \geoPHP\Geometry\GeometryCollection(), $point->boundary());

        $this->assertNull($point->explode());

        $this->assertTrue($point->isSimple());
    }
}
