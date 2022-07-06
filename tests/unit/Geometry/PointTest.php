<?php

namespace geoPHP\Tests\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of Point geometry
 *
 * @group geometry
 *
 */
class PointTest extends TestCase
{
    public const DELTA = 1e-8;

    public function providerValidCoordinatesXY()
    {
        return [
            'null coordinates' => [0, 0],
            'positive integer' => [10, 20],
            'negative integer' => [-10, -20],
            'WGS84'            => [47.1234056789, 19.9876054321],
            'HD72/EOV'         => [238084.12, 649977.59],
        ];
    }

    /**
     * @dataProvider providerValidCoordinatesXY
     *
     * @param int|float $x
     * @param int|float $y
     */
    public function testValidCoordinatesXY($x, $y)
    {
        $point = new Point($x, $y);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());

        $this->assertIsFloat($point->x());
        $this->assertIsFloat($point->y());
    }

    public function providerValidCoordinatesXYZorXYM()
    {
        return [
            'null coordinates' => [0, 0, 0],
            'positive integer' => [10, 20, 30],
            'negative integer' => [-10, -20, -30],
            'WGS84'            => [47.1234056789, 19.9876054321, 100.1],
            'HD72/EOV'         => [238084.12, 649977.59, 56.38],
        ];
    }

    /**
     * @dataProvider providerValidCoordinatesXYZorXYM
     *
     * @param int|float $x
     * @param int|float $y
     * @param int|float $z
     */
    public function testValidCoordinatesXYZ($x, $y, $z)
    {
        $point = new Point($x, $y, $z);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertEquals($z, $point->z());
        $this->assertNull($point->m());

        $this->assertIsFloat($point->x());
        $this->assertIsFloat($point->y());
        $this->assertIsFloat($point->z());
    }

    /**
     * @dataProvider providerValidCoordinatesXYZorXYM
     *
     * @param int|float $x
     * @param int|float $y
     * @param int|float $m
     */
    public function testValidCoordinatesXYM($x, $y, $m)
    {
        $point = new Point($x, $y, null, $m);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertEquals($m, $point->m());
        $this->assertNull($point->z());

        $this->assertIsFloat($point->x());
        $this->assertIsFloat($point->y());
        $this->assertIsFloat($point->m());
    }

    public function providerValidCoordinatesXYZM()
    {
        return [
            'null coordinates' => [0, 0, 0, 0],
            'positive integer' => [10, 20, 30, 40],
            'negative integer' => [-10, -20, -30, -40],
            'WGS84'            => [47.1234056789, 19.9876054321, 100.1, 0.00001],
            'HD72/EOV'         => [238084.12, 649977.59, 56.38, -0.00001],
        ];
    }

    /**
     * @dataProvider providerValidCoordinatesXYZM
     *
     * @param int|float $x
     * @param int|float $y
     * @param int|float $z
     * @param int|float $m
     */
    public function testValidCoordinatesXYZM($x, $y, $z, $m)
    {
        $point = new Point($x, $y, $z, $m);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertEquals($z, $point->z());
        $this->assertEquals($m, $point->m());

        $this->assertIsFloat($point->x());
        $this->assertIsFloat($point->y());
        $this->assertIsFloat($point->z());
        $this->assertIsFloat($point->m());
    }

    public function testConstructorWithoutParameters()
    {
        $point = new Point();

        $this->assertTrue($point->isEmpty());

        $this->assertNull($point->x());
        $this->assertNull($point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
    }

    public function providerEmpty()
    {
        return [
            'no coordinates'     => [],
            'x is null'          => [null, 20],
            'y is null'          => [10, null],
            'x and y is null'    => [null, null, 30],
            'x, y, z is null'    => [null, null, null, 40],
            'x, y, z, m is null' => [null, null, null, null],
        ];
    }

    /**
     * @dataProvider providerEmpty
     *
     * @param int|float|null $x
     * @param int|float|null $y
     * @param int|float|null $z
     * @param int|float|null $m
     */
    public function testEmpty($x = null, $y = null, $z = null, $m = null)
    {
        $point = new Point($x, $y, $z, $m);

        $this->assertTrue($point->isEmpty());

        $this->assertNull($point->x());
        $this->assertNull($point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
    }

    public function providerInvalidCoordinates()
    {
        return [
            'string coordinates'  => ['x', 'y'],
            'boolean coordinates' => [true, false],
            'z is string'         => [1, 2, 'z'],
            'm is string'         => [1, 2, 3, 'm'],
        ];
    }

    /**
     * @dataProvider providerInvalidCoordinates
     *
     * @param mixed $x
     * @param mixed $y
     * @param mixed $z
     * @param mixed $m
     */
    public function testConstructorWithInvalidCoordinates($x, $y, $z = null, $m = null)
    {
        $this->expectException(InvalidGeometryException::class);

        new Point($x, $y, $z, $m);
    }

    public function testGeometryType()
    {
        $point = new Point();

        $this->assertEquals(\geoPHP\Geometry\Geometry::POINT, $point->geometryType());

        $this->assertInstanceOf(Point::class, $point);
        $this->assertInstanceOf(\geoPHP\Geometry\Geometry::class, $point);
    }

    public function providerIs3D()
    {
        return [
            '2 coordinates is not 3D'   => [false, 1, 2],
            '3 coordinates'             => [true, 1, 2, 3],
            '4 coordinates'             => [true, 1, 2, 3, 4],
            'x, y is null but z is not' => [true, null, null, 3, 4],
            'z is null'                 => [false, 1, 2, null, 4],
            'empty point'               => [false],
        ];
    }

    /**
     * @dataProvider providerIs3D
     */
    public function testIs3D($result, $x = null, $y = null, $z = null, $m = null)
    {
        $this->assertSame($result, (new Point($x, $y, $z, $m))->is3D());
    }

    public function providerIsMeasured()
    {
        return [
            '2 coordinates is false'    => [false, 1, 2],
            '3 coordinates is false'    => [false, 1, 2, 3],
            '4 coordinates'             => [true, 1, 2, 3, 4],
            'x, y is null but m is not' => [true, null, null, 3, 4],
            'm is null'                 => [false, 1, 2, 3, null],
            'empty point'               => [false],
        ];
    }

    /**
     * @dataProvider providerIsMeasured
     */
    public function testIsMeasured($result, $x = null, $y = null, $z = null, $m = null)
    {
        $this->assertSame($result, (new Point($x, $y, $z, $m))->isMeasured());
    }

    public function testGetComponents()
    {
        $point = new Point(1, 2);
        $components = $point->getComponents();

        $this->assertIsArray($components);
        $this->assertCount(1, $components);
        $this->assertSame($point, $components[0]);
    }

    /**
     * @dataProvider providerValidCoordinatesXYZM
     *
     * @param int|float $x
     * @param int|float $y
     * @param int|float $z
     * @param int|float $m
     */
    public function testInvertXY($x, $y, $z, $m)
    {
        $point = new Point($x, $y, $z, $m);
        $originalPoint = clone $point;
        $point->invertXY();

        $this->assertEquals($x, $point->y());
        $this->assertEquals($y, $point->x());
        $this->assertEquals($z, $point->z());
        $this->assertEquals($m, $point->m());

        $point->invertXY();
        $this->assertEquals($point, $originalPoint);
    }

    public function testCentroidIsThePointItself()
    {
        $point = new Point(1, 2, 3, 4);
        $this->assertSame($point, $point->centroid());
    }

    public function testBBox()
    {
        $point = new Point(1, 2);
        $this->assertSame(
            $point->getBBox(),
            [
                'maxy' => 2.0,
                'miny' => 2.0,
                'maxx' => 1.0,
                'minx' => 1.0,
            ]
        );
    }

    public function testAsArray()
    {
        $pointAsArray = (new Point())->asArray();
        $this->assertCount(2, $pointAsArray);
        $this->assertNan($pointAsArray[0]);
        $this->assertNan($pointAsArray[1]);

        $pointAsArray = (new Point(1, 2))->asArray();
        $this->assertSame([1.0, 2.0], $pointAsArray);

        $pointAsArray = (new Point(1, 2, 3))->asArray();
        $this->assertSame([1.0, 2.0, 3.0], $pointAsArray);

        $pointAsArray = (new Point(1, 2, null, 3))->asArray();
        $this->assertSame([1.0, 2.0, null, 3.0], $pointAsArray);

        $pointAsArray = (new Point(1, 2, 3, 4))->asArray();
        $this->assertSame([1.0, 2.0, 3.0, 4.0], $pointAsArray);
    }

    public function testBoundary()
    {
        $this->assertEquals((new Point(1, 2))->boundary(), new GeometryCollection());
    }

    public function testEquals()
    {
        $this->assertTrue((new Point())->equals(new Point()));

        $point = new Point(1, 2, 3, 4);
        $this->assertTrue($point->equals(new Point(1, 2, 3, 4)));

        $this->assertTrue($point->equals(new Point(1.0000000001, 2.0000000001, 3, 4)));
        $this->assertTrue($point->equals(new Point(0.9999999999, 1.9999999999, 3, 4)));

        $this->assertFalse($point->equals(new Point(1.000000001, 2.000000001, 3, 4)));
        $this->assertFalse($point->equals(new Point(0.999999999, 1.999999999, 3, 4)));

        $this->assertFalse($point->equals(new GeometryCollection()));
    }

    public function testFlatten()
    {
        $point = new Point(1, 2, 3, 4);
        $point->flatten();

        $this->assertEquals(1, $point->x());
        $this->assertEquals(2, $point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
        $this->assertFalse($point->is3D());
        $this->assertFalse($point->isMeasured());
    }

    public function providerDistance()
    {
        return [
            'empty Point' =>
                [new Point(), null],
            'Point x+10' =>
                [new Point(10, 0), 10.0],
            'Point y+10' =>
                [new Point(0, 10), 10.0],
            'Point x+10,y+10' =>
                [new Point(10, 10), 14.142135623730951],
            'LineString, point is a vertex' =>
                [LineString::fromArray([[-10, 10], [0, 0], [10, 10]]), 0.0],
            'LineString, containing a vertex twice' =>
                [LineString::fromArray([[0, 10], [0, 10]]), 10.0],
            'LineString, point on line' =>
                [LineString::fromArray([[-10, -10], [10, 10]]), 0.0],

            'MultiPoint, closest distance is 0' =>
                [MultiPoint::fromArray([[0, 0], [10, 20]]), 0.0],
            'MultiPoint, closest distance is 10' =>

                [MultiPoint::fromArray([[10, 20], [0, 10]]), 10.0],
            'MultiPoint, one of two is empty' => [MultiPoint::fromArray([[], [0, 10]]), 10.0],

            'GeometryCollection, closest component is 10' =>
                [new GeometryCollection([new Point(0, 10), new Point()]), 10.0]
            // FIXME: this geometry collection crashes GEOS
            // TODO: test other types
        ];
    }

    /**
     * @dataProvider providerDistance
     *
     * @param Geometry $otherGeometry
     * @param float $expectedDistance
     */
    public function testDistance($otherGeometry, $expectedDistance)
    {
        $point = new Point(0, 0);

        $this->assertEqualsWithDelta($expectedDistance, $point->distance($otherGeometry), self::DELTA);
    }

    /**
     * @dataProvider providerDistance
     *
     * @param Geometry $otherGeometry
     */
    public function testDistanceEmpty($otherGeometry)
    {
        $point = new Point();

        $this->assertNull($point->distance($otherGeometry));
    }

    public function testTrivialMethods()
    {
        $point = new Point(1, 2, 3, 4);

        $this->assertSame(0, $point->dimension());

        $this->assertSame(1, $point->numPoints());

        $this->assertSame([$point], $point->getPoints());

        $this->assertTrue($point->isSimple());
    }

    public function testMinMaxMethods()
    {
        $point = new Point(1, 2, 3, 4);

        $this->assertEquals(3, $point->minimumZ());
        $this->assertEquals(3, $point->maximumZ());
        $this->assertEquals(4, $point->minimumM());
        $this->assertEquals(4, $point->maximumM());
    }

    public function providerMethodsNotValidForPointReturnsNull()
    {
        return [
                ['zDifference'],
                ['elevationGain'],
                ['elevationLoss'],
                ['numGeometries'],
                ['geometryN'],
                ['startPoint'],
                ['endPoint'],
                ['isRing'],
                ['isClosed'],
                ['pointN'],
                ['exteriorRing'],
                ['numInteriorRings'],
                ['interiorRingN'],
                ['explode']
        ];
    }

    /**
     * @dataProvider providerMethodsNotValidForPointReturnsNull
     *
     * @param string $methodName
     */
    public function testPlaceholderMethodsReturnsNull($methodName)
    {
        $this->assertNull((new Point(1, 2, 3, 4))->$methodName(null));
    }

    public function providerMethodsNotValidForPointReturns0()
    {
        return [
            ['area'],
            ['length'],
            ['length3D'],
            ['greatCircleLength'],
            ['haversineLength']
        ];
    }

    /**
     * @dataProvider providerMethodsNotValidForPointReturns0
     *
     * @param string $methodName
     */
    public function testPlaceholderMethods($methodName)
    {
        $this->assertSame(0.0, (new Point(1, 2, 3, 4))->$methodName(null));
    }
}
