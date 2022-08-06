<?php

namespace geoPHP\Tests\Unit\Geometry;

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
 * @coversDefaultClass geoPHP\Geometry\Point
 * @group geometry
 */
class PointTest extends TestCase
{
    public const DELTA = 1e-8;

    /**
     * @return array<string, array<int|float>>
     */
    public function providerValidCoordinatesXY(): array
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
     * @covers ::__construct
     * @covers ::x
     * @covers ::y
     *
     * @param int|float $x
     * @param int|float $y
     */
    public function testValidCoordinatesXY($x, $y): void
    {
        $point = new Point($x, $y);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());

        $this->assertIsFloat($point->x());
        $this->assertIsFloat($point->y());
    }

    /**
     * @return array<string, array<int|float>>
     */
    public function providerValidCoordinatesXYZorXYM(): array
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
     * @covers ::__construct
     * @covers ::x
     * @covers ::y
     * @covers ::z
     *
     * @param int|float $x
     * @param int|float $y
     * @param int|float $z
     */
    public function testValidCoordinatesXYZ($x, $y, $z): void
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
     * @covers ::__construct
     * @covers ::x
     * @covers ::y
     * @covers ::m
     *
     * @param int|float $x
     * @param int|float $y
     * @param int|float $m
     */
    public function testValidCoordinatesXYM($x, $y, $m): void
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

    /**
     * @return array<string, array<int|float>>
     */
    public function providerValidCoordinatesXYZM(): array
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
     * @covers ::__construct
     * @covers ::x
     * @covers ::y
     * @covers ::z
     * @covers ::m
     *
     * @param int|float $x
     * @param int|float $y
     * @param int|float $z
     * @param int|float $m
     */
    public function testValidCoordinatesXYZM($x, $y, $z, $m): void
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

    /**
     * @covers ::__construct
     * @covers ::x
     * @covers ::y
     * @covers ::z
     * @covers ::m
     */
    public function testConstructorWithoutParameters(): void
    {
        $point = new Point();

        $this->assertTrue($point->isEmpty());

        $this->assertNull($point->x());
        $this->assertNull($point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
    }

    /**
     * @return array<string, array<?int>>
     */
    public function providerIsEmpty(): array
    {
        return [
            'no coordinates'     => [],
            'x and y is null'    => [null, null, 30],
            'x, y, z is null'    => [null, null, null, 40],
            'x, y, z, m is null' => [null, null, null, null],
        ];
    }

    /**
     * @dataProvider providerIsEmpty
     * @covers ::isEmpty
     * @covers ::__construct
     *
     * @param int|float|null $x
     * @param int|float|null $y
     * @param int|float|null $z
     * @param int|float|null $m
     */
    public function testIsEmpty($x = null, $y = null, $z = null, $m = null): void
    {
        $point = new Point($x, $y, $z, $m);

        $this->assertTrue($point->isEmpty());

        $this->assertNull($point->x());
        $this->assertNull($point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
    }

    /**
     * @dataProvider providerValidCoordinatesXY
     * @dataProvider providerValidCoordinatesXYZorXYM
     * @dataProvider providerValidCoordinatesXYZM
     * @dataProvider providerIsEmpty
     *
     * @covers ::fromArray
     *
     * @param int|float|null $x
     * @param int|float|null $y
     * @param int|float|null $z
     * @param int|float|null $m
     */
    public function testFromArray($x = null, $y = null, $z = null, $m = null): void
    {
        $positions = [$x, $y, $z, $m];
        $point = Point::fromArray($positions);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        if ($x !== null) {
            $this->assertEquals($z, $point->z());
            $this->assertEquals($m, $point->m());
        }
    }

    /**
     * @covers ::numPoints
     */
    public function testNumPoints(): void
    {
        $point = new Point(1, 2);
        $this->assertSame(1, $point->numPoints());

        $pointEmpty = new Point();
        $this->assertSame(0, $pointEmpty->numPoints());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function providerInvalidCoordinates(): array
    {
        return [
            'x is null'           => [null, 20],
            'y is null'           => [10, null],
            'string coordinates'  => ['x', 'y'],
            'boolean coordinates' => [true, false],
            'z is non numeric'    => [1, 2, 'z'],
            'm is non numeric'    => [1, 2, 3, 'm'],
            'x is NaN'            => [NAN, 1],
            'y is NaN'            => [1, NAN],
            'x is infinite'       => [INF, 1],
            'y is infinite'       => [1, INF],
            'z is NaN'            => [1, 2, NAN],
            'z is infinite'       => [1, 2, INF],
            'm is NaN'            => [1, 2, 3, NAN],
            'm is infinite'       => [1, 2, 3, INF],
        ];
    }

    /**
     * @dataProvider providerInvalidCoordinates
     * @covers ::__construct
     *
     * @param mixed $x
     * @param mixed $y
     * @param mixed $z
     * @param mixed $m
     */
    public function testConstructorWithInvalidCoordinates($x, $y, $z = null, $m = null): void
    {
        $this->expectException(InvalidGeometryException::class);

        new Point($x, $y, $z, $m);
    }

    /**
     * @covers ::geometryType
     */
    public function testGeometryType(): void
    {
        $point = new Point();

        $this->assertEquals(Geometry::POINT, $point->geometryType());

        $this->assertInstanceOf(Point::class, $point);
        $this->assertInstanceOf(Geometry::class, $point);
    }

    /**
     * @return array<string, array<bool|int|null>>
     */
    public function providerIs3D(): array
    {
        return [
            'empty point'        => [false],
            '2 coordinates'      => [false, 1, 2],
            '3 coordinates'      => [true, 1, 2, 3],
            '4 coordinates'      => [true, 1, 2, 3, 4],
            'empty point with z' => [false, null, null, 3, 4],
            'z is null'          => [false, 1, 2, null, 4],
        ];
    }

    /**
     * @dataProvider providerIs3D
     * @covers ::is3D
     *
     * @param bool $result
     * @param int|float|null $x
     * @param int|float|null $y
     * @param int|float|null $z
     * @param int|float|null $m
     */
    public function testIs3D(bool $result, $x = null, $y = null, $z = null, $m = null): void
    {
        $this->assertSame($result, (new Point($x, $y, $z, $m))->is3D());
    }

    /**
     * @return array<string, array<bool|int|null>>
     */
    public function providerIsMeasured(): array
    {
        return [
            'empty point'               => [false],
            '2 coordinates is false'    => [false, 1, 2],
            '3 coordinates is false'    => [false, 1, 2, 3],
            '4 coordinates'             => [true, 1, 2, 3, 4],
            'empty point with z and m' => [false, null, null, 3, 4],
            'empty point with m'       => [false, null, null, null, 4],
            'm is null'                 => [false, 1, 2, 3, null],
            'z is null'                 => [true, 1, 2, null, 4],
        ];
    }

    /**
     * @dataProvider providerIsMeasured
     * @covers ::isMeasured
     *
     * @param bool $result
     * @param int|float|null $x
     * @param int|float|null $y
     * @param int|float|null $z
     * @param int|float|null $m
     */
    public function testIsMeasured(bool $result, $x = null, $y = null, $z = null, $m = null): void
    {
        $this->assertSame($result, (new Point($x, $y, $z, $m))->isMeasured());
    }

    /**
     * @covers ::getComponents
     */
    public function testGetComponents(): void
    {
        $point = new Point(1, 2);
        $components = $point->getComponents();

        $this->assertIsArray($components);
        $this->assertCount(1, $components);
        $this->assertSame($point, $components[0]);
    }

    /**
     * @dataProvider providerValidCoordinatesXYZM
     * @covers ::invertXY
     *
     * @param int|float|null $x
     * @param int|float|null $y
     * @param int|float|null $z
     * @param int|float|null $m
     */
    public function testInvertXY($x, $y, $z, $m): void
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

    /**
     * @covers ::centroid
     */
    public function testCentroidIsThePointItself(): void
    {
        $point = new Point(1, 2, 3, 4);
        $this->assertSame($point, $point->centroid());
    }

    /**
     * @covers ::getBBox
     */
    public function testBBox(): void
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

    /**
     * @covers ::getBBox
     */
    public function testBBoxOfEmpty(): void
    {
        $point = new Point();
        $this->assertSame(
            $point->getBBox(),
            [
                'maxy' => null,
                'miny' => null,
                'maxx' => null,
                'minx' => null,
            ]
        );
    }

    /**
     * @covers ::asArray
     * @testWith [[]]
     *           [[1.0, 2.0]]
     *           [[1.0, 2.0, 3.0]]
     *           [[1.0, 2.0, null, 3.0]]
     *           [[1.0, 2.0, 3.0, 4.0]]
     *
     * @param array<?float> $points
     */
    public function testAsArray(array $points): void
    {
        $point = Point::fromArray($points);
        $pointAsArray = $point->asArray();

        $this->assertSame($points, $pointAsArray);
    }

    /**
     * @covers ::boundary
     */
    public function testBoundary(): void
    {
        $this->assertEquals((new Point(1, 2))->boundary(), new GeometryCollection());
    }

    /**
     * @covers ::equals
     */
    public function testEquals(): void
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

    /**
     * @covers ::flatten
     */
    public function testFlatten(): void
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

    /**
     * @return array<string, array{Geometry, ?float}>
     */
    public function providerDistance(): array
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
            'LineString, point is the first vertex' =>
                [LineString::fromArray([[0, 0], [10, 10]]), 0.0],
            'LineString, point is the second vertex' =>
                [LineString::fromArray([[-10, 10], [0, 0]]), 0.0],
            'LineString, containing a vertex twice' =>
                [LineString::fromArray([[0, 10], [0, 10]]), 10.0],
            'LineString, point on line' =>
                [LineString::fromArray([[-10, -10], [10, 10]]), 0.0],

            'MultiPoint, closest distance is 0' =>
                [MultiPoint::fromArray([[0, 0], [10, 20]]), 0.0],
            'MultiPoint, closest distance is 10' =>
                [MultiPoint::fromArray([[10, 20], [0, 10]]), 10.0],
            'MultiPoint, one is empty' =>
                [MultiPoint::fromArray([[10, 0], []]), 10.0],

            'GeometryCollection, closest component is 10' =>
                [new GeometryCollection([new Point(0, 10), new Point(20, 0)]), 10.0]
            // TODO: test other types
        ];
    }

    /**
     * @dataProvider providerDistance
     * @covers ::distance
     */
    public function testDistance(Geometry $otherGeometry, ?float $expectedDistance): void
    {
        $point = new Point(0, 0);

        $this->assertEqualsWithDelta($expectedDistance, $point->distance($otherGeometry), self::DELTA);
    }

    /**
     * @dataProvider providerDistance
     * @covers ::distance
     */
    public function testDistanceEmpty(Geometry $otherGeometry): void
    {
        $point = new Point();

        $this->assertNull($point->distance($otherGeometry));
    }

    /**
     * @covers ::dimension
     * @covers ::getPoints
     * @covers ::isSimple
     */
    public function testTrivialMethods(): void
    {
        $point = new Point(1, 2, 3, 4);

        $this->assertSame(0, $point->dimension());

        $this->assertSame([$point], $point->getPoints());

        $this->assertTrue($point->isSimple());
    }

    /**
     * @covers ::minimumZ
     * @covers ::maximumZ
     * @covers ::minimumM
     * @covers ::maximumM
     */
    public function testMinMaxMethods(): void
    {
        $point = new Point(1, 2, 3, 4);

        $this->assertEquals(3, $point->minimumZ());
        $this->assertEquals(3, $point->maximumZ());
        $this->assertEquals(4, $point->minimumM());
        $this->assertEquals(4, $point->maximumM());
    }

    /**
     * @return array{array<string>}
     */
    public function providerMethodsNotValidForPointReturnsNull(): array
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
     * @covers ::zDifference
     * @covers ::elevationGain
     * @covers ::elevationLoss
     * @covers ::numGeometries
     * @covers ::geometryN
     * @covers ::startPoint
     * @covers ::endPoint
     * @covers ::isRing
     * @covers ::isClosed
     * @covers ::pointN
     * @covers ::exteriorRing
     * @covers ::numInteriorRings
     * @covers ::interiorRingN
     * @covers ::explode
     */
    public function testPlaceholderMethodsReturnsNull(string $methodName): void
    {
        $this->assertNull((new Point(1, 2, 3, 4))->$methodName());
    }


    /**
     * @testWith ["area"]
     *           ["length"]
     *           ["length3D"]
     *           ["greatCircleLength"]
     *           ["haversineLength"]
     *           ["vincentyLength"]
     *
     * @covers ::area
     * @covers ::length
     * @covers ::length3D
     * @covers ::greatCircleLength
     * @covers ::haversineLength
     * @covers ::vincentyLength
     */
    public function testPlaceholderMethods(string $methodName): void
    {
        $this->assertSame(0.0, (new Point(1, 2, 3, 4))->$methodName());
    }
}
