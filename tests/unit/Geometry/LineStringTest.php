<?php

namespace geoPHP\Tests\Unit\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of LineString geometry
 *
 * @group geometry
 * @coversDefaultClass geoPHP\Geometry\LineString
 *
 * @uses geoPHP\Geometry\Point
 * @uses geoPHP\Geometry\GeometryCollection
 */
class LineStringTest extends TestCase
{
    public const DELTA = 1e-8;

    /**
     * @param array<array<int>> $coordinateArray
     * @return Point[]
     */
    private function createPoints(array $coordinateArray): array
    {
        $points = [];
        foreach ($coordinateArray as $point) {
            $points[] = Point::fromArray($point);
        }
        return $points;
    }

    /**
     * @return array<string, array<array<array<int|null>>>>
     */
    public function providerValidComponents(): array
    {
        return [
            'empty' =>
                [[]],
            'with two points' =>
                [[[0, 0], [1, 1]]],
            'LineString Z' =>
                [[[0, 0, 0], [1, 1, 1]]],
            'LineString M' =>
                [[[0, 0, null, 0], [1, 1, null, 1]]],
            'LineString ZM' =>
                [[[0, 0, 0, 0], [1, 1, 1, 1]]],
            'LineString with 5 points' =>
                [[[0, 0], [1, 1], [2, 2], [3, 3], [4, 4]]],
        ];
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::__construct
     *
     * @param array<array<int|null>> $points
     */
    public function testConstructor(array $points): void
    {
        $lineString = new LineString($this->createPoints($points));

        $this->assertNotNull($lineString);
        $this->assertInstanceOf(LineString::class, $lineString);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorEmptyComponentThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot create a collection of empty Points.+/');

        // Empty points
        new LineString([new Point(), new Point(), new Point()]);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorNonArrayComponentTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectErrorMessageMatches('/Argument #?1 .+ type array, string given/');

        // @phpstan-ignore-next-line
        new LineString('foo');
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorSinglePointThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot construct a [a-zA-Z_\\\\]+LineString with a single point/');

        new LineString([new Point(1, 2)]);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorWrongComponentTypeThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot construct .+LineString\. Expected .+Point components, got.+/');

        // @phpstan-ignore-next-line
        new LineString([new LineString(), new LineString()]);
    }

    /**
     * @covers ::fromArray
     */
    public function testFromArray(): void
    {
        $this->assertEquals(
            LineString::fromArray([[1,2,3,4], [5,6,7,8]]),
            new LineString([new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)])
        );
    }

    /**
     * @covers ::geometryType
     */
    public function testGeometryType(): void
    {
        $line = new LineString();

        $this->assertEquals(LineString::LINE_STRING, $line->geometryType());

        $this->assertInstanceOf(LineString::class, $line);
        $this->assertInstanceOf(\geoPHP\Geometry\Curve::class, $line);
        $this->assertInstanceOf(\geoPHP\Geometry\Collection::class, $line);
        $this->assertInstanceOf(\geoPHP\Geometry\Geometry::class, $line);
    }

    /**
     * @covers ::isEmpty
     */
    public function testIsEmpty(): void
    {
        $line1 = new LineString();
        $this->assertTrue($line1->isEmpty());

        $line2 = new LineString($this->createPoints([[1,2], [3,4]]));
        $this->assertFalse($line2->isEmpty());
    }

    /**
     * @covers ::dimension
     */
    public function testDimension(): void
    {
        $this->assertSame(1, (new LineString())->dimension());
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::numPoints
     *
     * @param array<array<int|null>> $points
     */
    public function testNumPoints(array $points): void
    {
        $line = new LineString($this->createPoints($points));
        $this->assertCount($line->numPoints(), $points);
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::pointN
     *
     * @param array<array<int|null>> $points
     */
    public function testPointN(array $points): void
    {
        $components = $this->createPoints($points);
        $line = new LineString($components);

        $this->assertNull($line->pointN(0));

        for ($i = 1; $i < count($components); $i++) {
            // positive n
            $this->assertEquals($components[$i - 1], $line->pointN($i));

            // negative n
            $this->assertEquals($components[count($components) - $i], $line->pointN(-$i));
        }
    }

    /**
     * @return array<string, array{mixed, Point}>
     */
    public function providerCentroid(): array
    {
        return [
            'empty LineString' => [[], new Point()],
            'null coordinates' => [[[0, 0], [0, 0]], new Point(0, 0)],
            '↗ vector' => [[[0, 0], [1, 1]], new Point(0.5, 0.5)],
            '↙ vector' => [[[0, 0], [-1, -1]], new Point(-0.5, -0.5)],
            'random geographical coordinates' => [[
                    [20.0390625, -16.97274101999901],
                    [-11.953125, 17.308687886770034],
                    [0.703125, 52.696361078274485],
                    [30.585937499999996, 52.696361078274485],
                    [42.5390625, 41.77131167976407],
                    [-13.359375, 38.8225909761771],
                    [18.984375, 17.644022027872726]
            ], new Point(8.71798087550578, 31.1304531386738)],
            'crossing the antimeridian' => [[[170, 47], [-170, 47]], new Point(0, 47)]
        ];
    }

    /**
     * @dataProvider providerCentroid
     * @covers ::centroid
     * @covers ::getCentroidAndLength
     *
     * @param array<array<int|null>> $points
     * @param Point                  $centroidPoint
     */
    public function testCentroid(array $points, Point $centroidPoint): void
    {
        $line = LineString::fromArray($points);
        $centroid = $line->centroid();

        $this->assertEqualsWithDelta($centroidPoint, $centroid, self::DELTA);
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public function providerIsSimple(): array
    {
        return [
                'simple' =>
                    [[[0, 0], [0, 10]], true],
                'self-crossing' =>
                    [[[0, 0], [10, 0], [10, 10], [0, -10]], false],
                // 'self-tangent' =>
                //     [[[0, 0], [10, 0], [-10, 0]], false, 'faulty'],
                // FIXME: isSimple() fails to check self-tangency
        ];
    }

    /**
     * @dataProvider providerIsSimple
     * @covers ::isSimple
     *
     * @param array<array<int|null>> $points
     * @param bool                   $result
     */
    public function testIsSimple(array $points, bool $result, ?string $skip = null): void
    {
        $line = LineString::fromArray($points);

        if ($skip === 'faulty') {
            $this->markTestIncomplete("Current implementation has known problems with self tangency.");
        }

        $this->assertSame($result, $line->isSimple());
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public function providerIsRing(): array
    {
        return [
                'empty' =>
                    [[], false],
                'non closed' =>
                    [[[0, 0], [0, 10], [10, 10]], false],
                'simple ring' =>
                    [[[0, 0], [0, 10], [10, 10], [0, 0]], true],
                'self-crossing' =>
                    [[[0, 0], [10, 0], [10, 10], [0, -10], [0, 0]], false],
        ];
    }

    /**
     * @dataProvider providerIsRing
     * @covers ::isRing
     *
     * @param array<array<int|null>> $points
     * @param bool                   $result
     */
    public function testIsRing(array $points, bool $result, ?string $skip = null): void
    {
        $line = LineString::fromArray($points);

        $this->assertSame($result, $line->isRing());
    }

    /**
     * @return array<array{array<mixed>, float}>
     */
    public function providerLength(): array
    {
        return [
                [[[0, 0], [10, 0]], 10.0],
                [[[1, 1], [2, 2], [2, 3.5], [1, 3], [1, 2], [2, 1]], 6.44646111349608],
        ];
    }

    /**
     * @dataProvider providerLength
     * @covers ::length
     *
     * @param array<mixed> $points
     * @param float        $result
     */
    public function testLength(array $points, float $result): void
    {
        $line = LineString::fromArray($points);

        $this->assertEqualsWithDelta($result, $line->length(), self::DELTA);

        // Results of Lengh and Length3D should be equal on 2D dataset.
        $this->assertEquals($line->length(), $line->length3D());
    }

    /**
     * @return array<array{array<mixed>, float}>
     */
    public function providerLength3D(): array
    {
        return [
                [[[0, 0, 0], [10, 0, 10]], 14.142135623731],
                [[[1, 1, 0], [2, 2, 2], [2, 3.5, 0], [1, 3, 2], [1, 2, 0], [2, 1, 2]], 11.926335310544],
        ];
    }

    /**
     * @dataProvider providerLength3D
     * @covers ::length3D
     *
     * @param array<mixed> $points
     * @param float        $result
     */
    public function testLength3D(array $points, float $result): void
    {
        $line = LineString::fromArray($points);

        $this->assertEqualsWithDelta($result, $line->length3D(), self::DELTA);
    }

    /**
     * @return array<array{array<mixed>, array<string, float>}>
     */
    public function providerLengths(): array
    {
        return [
                [[[0, 0], [0, 0]], [
                        'greatCircle' => 0.0,
                        'haversine'   => 0.0,
                        'vincenty'    => 0.0,
                        'PostGIS'     => 0.0
                ]],
                [[[0, 0], [10, 0]], [
                        'greatCircle' => 1113194.9079327357,
                        'haversine'   => 1113194.9079327371,
                        'vincenty'    => 1113194.9079322326,
                        'PostGIS'     => 1113194.90793274
                ]],
                [[[0, 0, 0], [10, 0, 5000]], [
                        'greatCircle' => 1113206.136817154,
                        'haversine'   => 1113194.9079327371,
                        'vincenty'    => 1113194.9079322326,
                        'PostGIS'     => 1113194.90793274
                ]],
                [[[0, 47], [10, 47]], [
                        'greatCircle' => 758681.06593496865,
                        'haversine'   => 758681.06593497901,
                        'vincenty'    => 760043.0186457854,
                        'postGIS'     => 760043.018642104
                ]],
                [[[1, 1, 0], [2, 2, 2], [2, 3.5, 0], [1, 3, 2], [1, 2, 0], [2, 1, 2]], [
                        'greatCircle' => 717400.38999229996,
                        'haversine'   => 717400.38992081373,
                        'vincenty'    => 714328.06433538091,
                        'postGIS'     => 714328.064406871
                ]],
                [[[19, 47], [19.000001, 47], [19.000001, 47.000001], [19.000001, 47.000002], [19.000002, 47.000002]], [
                        'greatCircle' => 0.37447839912084818,
                        'haversine'   => 0.36386002147417207,
                        'vincenty'    => 0.37445330532190713,
                        'postGIS'     => 0.374453678675281
                ]]
        ];
    }

    /**
     * @dataProvider providerLengths
     * @covers ::greatCircleLength
     *
     * @param array<mixed> $points
     * @param array<string, float> $results
     */
    public function testGreatCircleLength(array $points, array $results): void
    {
        $line = LineString::fromArray($points);

        $this->assertEqualsWithDelta($results['greatCircle'], $line->greatCircleLength(), self::DELTA);
    }

    /**
     * @dataProvider providerLengths
     * @covers ::haversineLength
     *
     * @param array<mixed> $points
     * @param array<string, float> $results
     */
    public function testHaversineLength(array $points, array $results): void
    {
        $line = LineString::fromArray($points);

        $this->assertEqualsWithDelta($results['haversine'], $line->haversineLength(), self::DELTA);
    }

    /**
     * @dataProvider providerLengths
     * @covers ::vincentyLength
     *
     * @param array<mixed> $points
     * @param array<string, float> $results
     */
    public function testVincentyLength(array $points, array $results): void
    {
        $line = LineString::fromArray($points);

        $this->assertEqualsWithDelta($results['vincenty'], $line->vincentyLength(), self::DELTA);
    }

    /**
     * @covers ::vincentyLength
     */
    public function testVincentyLengthAntipodalPoints(): void
    {
        $line = LineString::fromArray([[-89.7, 0], [89.7, 0]]);

        $this->expectException(\Exception::class);

        $line->vincentyLength();
    }

    /**
     * @covers ::explode
     */
    public function testExplode(): void
    {
        $point1 = new Point(1, 2);
        $point2 = new Point(3, 4);
        $point3 = new Point(5, 6);
        $line = new LineString([$point1, $point2, $point3]);

        $this->assertEquals(
            [new LineString([$point1, $point2]), new LineString([$point2, $point3])],
            $line->explode()
        );

        $this->assertSame(
            [[$point1, $point2], [$point2, $point3]],
            $line->explode(true)
        );

        $this->assertSame([], (new LineString())->explode());

        $this->assertSame([], (new LineString())->explode(true));
    }

    /**
     * @return array<string, array{Geometry, float}>
     */
    public function providerDistance(): array
    {
        return [
            'Point on vertex' =>
                [new Point(0, 10), 0.0],
            'Point, closest distance is 10' =>
                [new Point(10, 10), 10.0],
            'LineString, same points' =>
                [LineString::fromArray([[0, 10], [10, 10]]), 0.0],
            'LineString, closest distance is 10' =>
                [LineString::fromArray([[10, 10], [20, 20]]), 10.0],
            'intersecting line' =>
                [LineString::fromArray([[-10, 5], [10, 5]]), 0.0],
            'GeometryCollection' =>
                [new \geoPHP\Geometry\GeometryCollection([LineString::fromArray([[10, 10], [20, 20]])]), 10.0],
            // TODO: test other types
        ];
    }

    /**
     * @dataProvider providerDistance
     * @covers ::distance
     */
    public function testDistance(Geometry $otherGeometry, float $expectedDistance): void
    {
        $line = LineString::fromArray([[0, 0], [0, 10]]);

        $this->assertSame($expectedDistance, $line->distance($otherGeometry));
    }

    /**
     * @return array<string, array{array<mixed>, array<string, ?float>}>
     */
    public function providerElevation(): array
    {
        return [
            '2D' => [
                [[0, 0], [1, 1], [2, 2], [3, 3]],
                [
                    'minZ'  => null,
                    'maxZ'  => null,
                    'minM'  => null,
                    'maxM'  => null,
                    'zDiff' => null,
                ]
            ],
            '4D' => [
                [[0, 0, 100.0, 0.0], [1, 1, 50.0, -0.5], [2, 2, 150.0, -1.0], [3, 3, 75.0, 0.5]],
                [
                    'minZ'  => 50.0,
                    'maxZ'  => 150.0,
                    'minM'  => -1.0,
                    'maxM'  => 0.5,
                    'zDiff' => 25.0,
                ]
            ],
        ];
    }

    /**
     * @dataProvider providerElevation
     * @covers ::minimumZ
     * @covers ::maximumZ
     * @covers ::minimumM
     * @covers ::maximumM
     * @covers ::zDifference
     *
     * @param array<array<int>>    $points
     * @param array<string, float> $results
     */
    public function testMinimumAndMaximumZAndMAndDifference($points, $results): void
    {
        $line = LineString::fromArray($points);

        $this->assertSame($results['minZ'], $line->minimumZ());
        $this->assertSame($results['maxZ'], $line->maximumZ());

        $this->assertSame($results['minM'], $line->minimumM());
        $this->assertSame($results['maxM'], $line->maximumM());

        $this->assertSame($results['zDiff'], $line->zDifference());
    }

    /**
     * @return array<array<float>> [tolerance, gain, loss]
     */
    public function providerElevationGainAndLossByTolerance(): array
    {
        return [
                [0.0, 50.0, 30.0],
                [5.0, 48.0, 28.0],
                [15.0, 36.0, 16.0]
        ];
    }

    /**
     * @dataProvider providerElevationGainAndLossByTolerance
     * @covers ::elevationGain
     * @covers ::elevationLoss
     */
    public function testElevationGainAndLoss(?float $tolerance, float $gain, float $loss): void
    {
        $line = LineString::fromArray(
            [
                [0, 0, 100],
                [0, 0, 102],
                [0, 0, 105],
                [0, 0, 103],
                [0, 0, 110],
                [0, 0, 118],
                [0, 0, 102],
                [0, 0, 108],
                [0, 0, 102],
                [0, 0, 108],
                [0, 0, 102],
                [0, 0, 120],
            ]
        );

        $this->assertSame($gain, $line->elevationGain($tolerance));

        $this->assertSame($loss, $line->elevationLoss($tolerance));
    }

    /**
     * @covers ::elevationGain
     * @covers ::elevationLoss
     */
    public function testElevationGainAndLoss2D(): void
    {
        $line = LineString::fromArray([[1, 2], [3, 4], [5, 6]]);

        $this->assertSame(0.0, $line->elevationGain());
        $this->assertSame(0.0, $line->elevationLoss());
    }
}
