<?php

namespace geoPHP\Tests\Unit\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Curve;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\MultiCurve;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of abstract geometry MultiCurve
 *
 * @group geometry
 * @coversDefaultClass geoPHP\Geometry\MultiCurve
 *
 * @uses geoPHP\Geometry\Point
 */
class MultiCurveTest extends TestCase
{
    /**
     * @param array<array<array<int|float>>> $coordinateArray
     * @return Curve[]
     */
    private function createCurves(array $coordinateArray): array
    {
        $curves = [];
        foreach ($coordinateArray as $curvePoints) {
            $points = [];
            foreach ($curvePoints as $coordinates) {
                $points[] = Point::fromArray($coordinates);
            }
            $curves[] = $this->getMockForAbstractClass(Curve::class, [$points]);
        }
        return $curves;
    }

    /**
     * @return array<mixed>
     */
    public function providerValidComponents(): array
    {
        return [
            'empty' =>
                [[]],
            'two curves with two points' =>
                [[[[0, 0], [1, 1]], [[2, 2], [3, 3]]]],
            'two curves, second is empty' =>
                [[[[0, 0], [1, 1]], []]],
        ];
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::__construct
     *
     * @param array<?array<array<int|null>>> $points
     */
    public function testConstructor(array $points): void
    {
        $stub = $this->getMockForAbstractClass(MultiCurve::class, [$this->createCurves($points)]);

        $this->assertNotNull($stub);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorWrongComponentTypeThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot construct .+Curve.*\. Expected .+Curve components, got.+/');

        // @phpstan-ignore-next-line
        $this->getMockForAbstractClass(MultiCurve::class, [[new Point(), new Point()]]);
    }

    /**
     * @covers ::__construct
     * @covers ::geometryType
     * @covers ::dimension
     */
    public function testGeometryType(): void
    {
        $stub = $this->getMockForAbstractClass(MultiCurve::class, []);

        $this->assertEquals(Geometry::MULTI_CURVE, $stub->geometryType());

        $this->assertInstanceOf(MultiCurve::class, $stub);
        $this->assertInstanceOf(\geoPHP\Geometry\MultiGeometry::class, $stub);
        $this->assertInstanceOf(\geoPHP\Geometry\Collection::class, $stub);
        $this->assertInstanceOf(\geoPHP\Geometry\Geometry::class, $stub);

        $this->assertSame(1, $stub->dimension());
    }

    /**
     * @return array<mixed>
     */
    public function providerIsClosed(): array
    {
        return [
            'empty' =>
                [[], false],
            'two ring' =>
                [[[[0, 0], [1, 1], [0, 0]], [[2, 2], [3, 3], [2, 2]]], true],
            'two curve forming a ring' =>
                [[[[0, 0], [1, 1], [2, 2]], [[2, 2], [3, 3], [0, 0]]], false],
            'two curves, second is not closed' =>
                [[[[0, 0], [1, 1], [0, 0]], [[2, 2], [3, 3]]], false],
        ];
    }

    /**
     * @dataProvider providerIsClosed
     * @covers ::isClosed
     *
     * @param array<mixed> $components
     */
    public function testIsClosed(array $components, bool $isClosed): void
    {
        $stub = $this->getMockForAbstractClass(MultiCurve::class, [$this->createCurves($components)]);

        $this->assertSame($isClosed, $stub->isClosed());
    }

    /**
     * @return array<mixed>
     */
    public function providerBoundary(): array
    {
        return [
            'empty' => [
                [],
                new MultiPoint()
            ],
            'two curves' => [
                [[[1, 1], [2, 2]], [[5, 5], [6, 6]]],
                MultiPoint::fromArray([[1, 1], [2, 2], [5, 5], [6, 6]])
            ],
            'two curves, second is closed' => [
                [[[1, 1], [2, 2]], [[5, 5], [5, 5]]],
                MultiPoint::fromArray([[1, 1], [2, 2]])
            ],
            'connecting curves, "mod 2 rule"' => [
                [[[1, 1], [2, 2]], [[2, 2], [3, 3]]],
                MultiPoint::fromArray([[1, 1], [3, 3]])
            ],
            'complex example' => [
                [[[1, 1], [2, 2]], [[2, 2], [3, 3], [4, 4], [4, 4], [3, 3], [5, 5]]],
                MultiPoint::fromArray([[1, 1], [5, 5]])
            ],
        ];
    }

    /**
     * @dataProvider providerBoundary
     * @covers ::boundary
     *
     * @param array<mixed> $components
     */
    public function testBoundary(array $components, Geometry $expectedBoundary): void
    {
        $stub = $this->getMockForAbstractClass(MultiCurve::class, [$this->createCurves($components)]);

        $this->assertEquals($expectedBoundary, $stub->boundary());
    }
}
