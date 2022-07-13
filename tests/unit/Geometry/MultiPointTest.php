<?php

namespace geoPHP\Tests\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\MultiPoint;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of MultiPoint geometry
 *
 * @group geometry
 * @coversDefaultClass geoPHP\Geometry\MultiPoint
 *
 * @uses geoPHP\Geometry\Point
 */
class MultiPointTest extends TestCase
{
    public function providerValidComponents()
    {
        return [
            'no components'    => [[]],
            'empty Point comp' => [[new Point()]],
            'xy'               => [[new Point(1, 2)]],
            '2 xy'             => [[new Point(1, 2), new Point(3, 4)]],
            '2 xyzm'           => [[new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)]],
        ];
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::__construct
     */
    public function testValidComponents(array $points)
    {
        $multiPoint = new MultiPoint($points);

        $this->assertNotNull($multiPoint);

        $this->assertInstanceOf(MultiPoint::class, $multiPoint);
    }

    public function providerInvalidComponents()
    {
        return [
            'LineString component' => [[LineString::fromArray([[1,2],[3,4]])]],
            'non-array'            => [new Point()],
            'string component'     => [["text"]],
        ];
    }

    /**
     * @dataProvider providerInvalidComponents
     * @covery ::__construct
     */
    public function testConstructorWithInvalidComponents($components)
    {
        $this->expectException(InvalidGeometryException::class);

        new MultiPoint($components);
    }

    /**
     * @covers ::fromArray
     */
    public function testFromArray()
    {
        $this->assertEquals(
            MultiPoint::fromArray([[1,2,3,4], [5,6,7,8]]),
            new MultiPoint([new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)])
        );
    }

    /**
     * @covery ::__construct
     */
    public function testGeometryType()
    {
        $multiPoint = new MultiPoint();

        $this->assertEquals(\geoPHP\Geometry\Geometry::MULTI_POINT, $multiPoint->geometryType());

        $this->assertInstanceOf('\geoPHP\Geometry\MultiPoint', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\MultiGeometry', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\Geometry', $multiPoint);
    }

    /**
     * @covery ::is3D
     */
    public function testIs3D()
    {
        $this->assertTrue((new Point(1, 2, 3))->is3D());
        $this->assertTrue((new Point(1, 2, 3, 4))->is3D());
        $this->assertTrue((new Point(null, null, 3, 4))->is3D());
    }

    /**
     * @covery ::isMeasured
     */
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
     * @covers ::centroid
     */
    public function testCentroid(array $components, array $centroid)
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
     * @covers ::isSimple
     */
    public function testIsSimple(array $points, bool $result)
    {
        $multiPoint = MultiPoint::fromArray($points);

        $this->assertSame($result, $multiPoint->isSimple());
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::numPoints
     */
    public function testNumPoints(array $points)
    {
        $multiPoint = new MultiPoint($points);

        $this->assertEquals(count($points), $multiPoint->numPoints());
        $this->assertEquals($multiPoint->numPoints(), $multiPoint->numGeometries());
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::numGeometries
     */
    public function testNumGeometries(array $points)
    {
        $multiPoint = new MultiPoint($points);

        $this->assertEquals(count($points), $multiPoint->numGeometries());
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::dimension
     * @covers ::boundary
     * @covers ::explode
     */
    public function testTrivialAndNotValidMethods(array $ponts)
    {
        $point = new MultiPoint($ponts);

        $this->assertSame(0, $point->dimension());

        $this->assertEquals(new \geoPHP\Geometry\GeometryCollection(), $point->boundary());

        $this->assertNull($point->explode());

        $this->assertTrue($point->isSimple());
    }
}
