<?php

namespace geoPHP\Tests\Unit\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Curve;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of Curve abstract geometry
 *
 * @group geometry
 * @coversDefaultClass geoPHP\Geometry\Curve
 *
 * @uses geoPHP\Geometry\Point
 * @uses geoPHP\Geometry\LineString
 * @uses geoPHP\Geometry\MultiPoint
 */
class CurveTest extends TestCase
{
    public const DELTA = 1e-8;

    /**
     * @param array<array<int|float>> $coordinateArray
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
            'CURVE Z' =>
                [[[0, 0, 0], [1, 1, 1]]],
            'CURVE M' =>
                [[[0, 0, null, 0], [1, 1, null, 1]]],
            'CURVE ZM' =>
                [[[0, 0, 0, 0], [1, 1, 1, 1]]],
            'CURVE of 5 points' =>
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
        $curveStub = $this->getMockForAbstractClass(Curve::class, [$this->createPoints($points)]);

        $this->assertNotNull($curveStub);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorEmptyComponentThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot create a collection of empty Points.+/');

        // Empty points
        $this->getMockForAbstractClass(Curve::class, [[new Point(), new Point(), new Point()]]);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorSinglePointThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot construct a [a-zA-Z_\\\\]+Curve.* with a single point/');

        $this->getMockForAbstractClass(Curve::class, [[new Point(1, 2)]]);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorWrongComponentTypeThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot construct .+Curve.*\. Expected .+Point components, got.+/');

        // @phpstan-ignore-next-line
        $this->getMockForAbstractClass(Curve::class, [[new LineString(), new LineString()]]);
    }

    /**
     * @covers ::geometryType
     */
    public function testGeometryType(): void
    {
        $curveStub = $this->getMockForAbstractClass(Curve::class, []);

        $this->assertEquals(LineString::CURVE, $curveStub->geometryType());

        $this->assertInstanceOf(Curve::class, $curveStub);
        $this->assertInstanceOf(\geoPHP\Geometry\Collection::class, $curveStub);
        $this->assertInstanceOf(\geoPHP\Geometry\Geometry::class, $curveStub);
    }

    /**
     * @return array<array{array<mixed>, MultiPoint}>
     */
    public function providerBoundary(): array
    {
        return [
            'Empty'    => [[], new MultiPoint()],
            'Closed'   => [[[1, 2], [3, 4], [1, 2]], new MultiPoint()],
            '3 points' => [[[1, 2], [3, 4], [5, 6]], new MultiPoint([new Point(1, 2), new Point(5, 6)])],
        ];
    }

    /**
     * @dataProvider providerBoundary
     * @covers ::boundary
     *
     * @param array<mixed> $components
     * @param MultiPoint $expectedBoundary
     */
    public function testBoundary(array $components, MultiPoint $expectedBoundary): void
    {
        $curveStub = $this->getMockForAbstractClass(Curve::class, [$this->createPoints($components)]);

        $this->assertEquals($expectedBoundary, $curveStub->boundary());
    }

    /**
     * @return array<array{array<mixed>, ?Point, ?Point}>
     */
    public function providerStartEndPoint(): array
    {
        return [
            'Empty'    => [[], null, null],
            '3 points' => [[[1, 2], [3, 4], [5, 6]], new Point(1, 2), new Point(5, 6)],
            'Closed'   => [[[1, 2], [3, 4], [1, 2]], new Point(1, 2), new Point(1, 2)],
        ];
    }

    /**
     * @dataProvider providerStartEndPoint
     * @covers ::startPoint
     * @covers ::endPoint
     *
     * @param array<mixed> $components
     * @param Point|null $expectedStartPoint
     * @param Point|null $expectedEndPoint
     */
    public function testStartEndPoint(array $components, ?Point $expectedStartPoint, ?Point $expectedEndPoint): void
    {
        $curveStub = $this->getMockForAbstractClass(Curve::class, [$this->createPoints($components)]);

        $this->assertEquals($expectedStartPoint, $curveStub->startPoint());
        $this->assertEquals($expectedEndPoint, $curveStub->endPoint());
    }

    /**
     * @return array<array{array<mixed>, bool}>
     */
    public function providerIsClosed(): array
    {
        return [
            'Empty'            => [[], false],
            '3 points'         => [[[1, 2], [3, 4], [5, 6]], false],
            'Closed'           => [[[1, 2], [3, 4], [1, 2]], true],
            'Enough close'     => [[[1, 2], [3, 4], [1.0000000001, 2.0000000001]], true],
            'Not enough close' => [[[1, 2], [3, 4], [1.00001, 2.00001]], false],
        ];
    }

    /**
     * @dataProvider providerIsClosed
     * @covers ::isClosed
     *
     * @param array<mixed> $components
     * @param bool $isClosed
     */
    public function testIsClosed(array $components, bool $isClosed): void
    {
        $curveStub = $this->getMockForAbstractClass(Curve::class, [$this->createPoints($components)]);

        $this->assertEquals($isClosed, $curveStub->isClosed());
    }

    /**
     * @return array<array{array<mixed>, bool}>
     */
    public function providerIsRing(): array
    {
        return [
            'Empty'            => [[], false],
            '3 points'         => [[[1, 2], [3, 4], [5, 6]], false],
            'Closed'           => [[[1, 2], [3, 4], [1, 2]], true],
            'Enough close'     => [[[1, 2], [3, 4], [1.0000000001, 2.0000000001]], true],
            'Not enough close' => [[[1, 2], [3, 4], [1.00001, 2.00001]], false],
        ];
    }

    /**
     * @dataProvider providerIsClosed
     * @covers ::isClosed
     *
     * @param array<mixed> $components
     * @param bool $isClosed
     */
    public function testIsRing(array $components, bool $isClosed): void
    {
        $curveStub = $this->getMockForAbstractClass(Curve::class, [$this->createPoints($components)]);

        $this->assertEquals($isClosed, $curveStub->isClosed());
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::getPoints
     *
     * @param array<mixed> $components
     */
    public function testGetPoints(array $components): void
    {
        $componentPoints = $this->createPoints($components);
        $curveStub = $this->getMockForAbstractClass(Curve::class, [$componentPoints]);

        $this->assertEquals($componentPoints, $curveStub->getPoints());
    }

    /**
     * @covers ::area
     * @covers ::exteriorRing
     * @covers ::numInteriorRings
     * @covers ::interiorRingN
     */
    public function testTrivialMethods(): void
    {
        $stub = $this->getMockForAbstractClass(Curve::class, [[]]);

        $this->assertSame(0.0, $stub->area());

        $this->assertSame(null, $stub->exteriorRing());

        $this->assertSame(null, $stub->numInteriorRings());

        $this->assertSame(null, $stub->interiorRingN(1));
    }
}
