<?php

namespace geoPHP\Tests\Unit\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\Polygon;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of Polygon geometry
 *
 * @group geometry
 * @coversDefaultClass geoPHP\Geometry\Polygon
 *
 * @uses geoPHP\Geometry\Point
 * @uses geoPHP\Geometry\LineString
 */
class PolygonTest extends TestCase
{
    /**
     * @param array<mixed> $coordinateArray
     * @return LineString[]
     */
    private function createComponents(array $coordinateArray): array
    {
        $lines = [];
        foreach ($coordinateArray as $point) {
            $lines[] = LineString::fromArray($point);
        }
        return $lines;
    }

    /**
     * @return array<string, mixed>
     */
    public function providerConstructorValidComponents(): array
    {
        return [
            'empty' =>
                [[]],
            'of 4 points' =>
                [[[[0, 0], [0, 1], [1, 1], [0, 0]]]],
            'Polygon Z' =>
            [[[[0, 0, 0], [0, 1, 1], [1, 1, 2], [0, 0, 3]]]],
            'Polygon M' =>
            [[[[0, 0, null, 0], [0, 1, null, 1], [1, 1, null, 2], [0, 0, null, 3]]]],
            'Polygon ZM' =>
            [[[[0, 0, 0, 0], [0, 1, 1, 1], [1, 1, 2, 2], [0, 0, 3, 3]]]],
            'Polygon with two rings' =>
                [[[[0, 0], [0, 10], [10, 10], [10, 0], [0, 0]], [[2, 2], [2, 4], [3, 4], [2, 2]]]],
        ];
    }

    /**
     * @dataProvider providerConstructorValidComponents
     * @covers ::__construct
     *
     * @param array<mixed> $points
     */
    public function testConstructor(array $points): void
    {
        $polygon = new Polygon($this->createComponents($points));

        $this->assertNotNull($polygon);
        $this->assertInstanceOf(Polygon::class, $polygon);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorNonArrayComponentTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectErrorMessageMatches('/Argument #?1 .+ type array, string given/');

        // @phpstan-ignore-next-line
        new Polygon('foo');
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorEmptyComponent(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectErrorMessageMatches('/Cannot create a collection of empty LineStrings/');

        new Polygon([new LineString()]);
    }

    /**
     * @return array<string, array<LineString>>
     */
    public function providerConstructorFewPoints(): array
    {
        return [
            'two points'       => [LineString::fromArray([[1, 2], [2, 3]])],
            'three points'     => [LineString::fromArray([[1, 2], [2, 3], [4, 5]])],
        ];
    }

    /**
     * @dataProvider providerConstructorFewPoints
     * @covers ::__construct
     */
    public function testConstructorFewPointThrowsException(LineString $component): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches(
            '/Cannot create Polygon: Invalid number of points in LinearRing. Found \d+, expected more than 3/'
        );

        new Polygon([$component]);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorWrongComponentTypeThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot construct .+Polygon\. Expected .+LineString components, got.+/');

        // @phpstan-ignore-next-line
        new Polygon([new Point()]);
    }

    /**
     * @return array<mixed>
     */
    public function providerValidComponents(): array
    {
        $ring1Points = [[0, 0], [0, 10], [10, 10], [10, 0], [0, 0]];
        $ring2Points = [[1, 1], [1, 9], [5, 9], [1, 1]];
        $ring3Points = [[2, 2], [2, 8], [5, 8], [2, 2]];

        $ring1 = new LineString(
            [new Point(0, 0), new Point(0, 10), new Point(10, 10), new Point(10, 0), new Point(0, 0)]
        );
        $ring2 = new LineString([new Point(1, 1), new Point(1, 9), new Point(5, 9), new Point(1, 1)]);
        $ring3 = new LineString([new Point(2, 2), new Point(2, 8), new Point(5, 8), new Point(2, 2)]);

        return [
            'empty' => [
                [],
                new Polygon(),
                [
                    'exteriorRing'  => new LineString(),
                    'interiorRings' => [],
                    'boundaryType'  => Geometry::LINE_STRING,
                ]
            ],
            'one ring' => [
                [$ring1Points],
                new Polygon([$ring1]),
                [
                    'exteriorRing'  => $ring1,
                    'interiorRings' => [],
                    'boundaryType'  => Geometry::LINE_STRING,
                ]
            ],
            'two ring' => [
                [$ring1Points, $ring2Points],
                new Polygon([$ring1, $ring2]),
                [
                'exteriorRing'  => $ring1,
                'interiorRings' => [1 => $ring2],
                'boundaryType' => Geometry::MULTI_LINE_STRING,
                ]
            ],
            'three ring' => [
                [$ring1Points, $ring2Points, $ring3Points],
                new Polygon([$ring1, $ring2, $ring3]),
                [
                    'exteriorRing'  => $ring1,
                    'interiorRings' => [1 => $ring2, 2 => $ring3],
                    'boundaryType' => Geometry::MULTI_LINE_STRING,
                ]
            ],
        ];
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::fromArray
     *
     * @param array<?array<array<int|float>>> $points
     * @param Polygon $expectedGeometry
     */
    public function testFromArray(array $points, Polygon $expectedGeometry): void
    {
        $fromArray = Polygon::fromArray($points);

        $this->assertEquals($expectedGeometry, $fromArray);
    }

    /**
     * @covers ::geometryType
     */
    public function testGeometryType(): void
    {
        $polygon = new Polygon();

        $this->assertEquals(Polygon::POLYGON, $polygon->geometryType());

        $this->assertInstanceOf(Polygon::class, $polygon);
        $this->assertInstanceOf(\geoPHP\Geometry\Surface::class, $polygon);
        $this->assertInstanceOf(\geoPHP\Geometry\Collection::class, $polygon);
        $this->assertInstanceOf(\geoPHP\Geometry\Geometry::class, $polygon);
    }

    /**
     * @covers ::dimension
     */
    public function testDimension(): void
    {
        $polygon = new Polygon();

        $this->assertSame(2, $polygon->dimension());
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::exteriorRing
     *
     * @param array<array<int>> $points
     * @param Polygon $geometry
     * @param array<mixed> $results
     */
    public function testExteriorRing(array $points, Polygon $geometry, array $results): void
    {
        $this->assertEquals($results['exteriorRing'], $geometry->exteriorRing());
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::numInteriorRings
     *
     * @param array<array<int>> $points
     * @param Polygon $geometry
     * @param array<mixed> $results
     */
    public function testNumInteriorRings(array $points, Polygon $geometry, array $results): void
    {
        $this->assertSame(count($results['interiorRings']), $geometry->numInteriorRings());
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::interiorRingN
     *
     * @param array<array<int>> $points
     * @param Polygon $geometry
     * @param array<mixed> $results
     */
    public function testInteriorRingN(array $points, Polygon $geometry, array $results): void
    {
        if (!count($results['interiorRings'])) {
            $this->expectNotToPerformAssertions();
        }
        foreach ($results['interiorRings'] as $num => $ring) {
            $this->assertEquals($ring, $geometry->interiorRingN($num));
        }
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::boundary
     *
     * @param array<array<int>> $points
     * @param Polygon $geometry
     * @param array<mixed> $results
     */
    public function testBoundary(array $points, Polygon $geometry, array $results): void
    {
        $boundary = $geometry->boundary();

        $this->assertInstanceOf(Geometry::class, $boundary);
        $this->assertEquals($results['boundaryType'], $boundary->geometryType());
    }
}
