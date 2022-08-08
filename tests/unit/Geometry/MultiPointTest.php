<?php

namespace geoPHP\Tests\Unit\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\MultiPoint;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of MultiPoint geometry.
 *
 * @group geometry
 * @coversDefaultClass geoPHP\Geometry\MultiPoint
 *
 * @uses geoPHP\Geometry\Point
 * @uses geoPHP\Geometry\LineString
 * @uses geoPHP\Geometry\Collection
 */
class MultiPointTest extends TestCase
{
    /**
     * @return array<string, array<array<?Point>>>
     */
    public function providerValidComponents(): array
    {
        return [
            'no components'    => [[]],
            'empty Point comp' => [[new Point()]],
            'xy'               => [[new Point(1, 2)]],
            '2 xy'             => [[new Point(1, 2), new Point(3, 4)]],
            '2 xyzm'           => [[new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)]],
            'one is empty'     => [[new Point(), new Point(1, 2)]],
        ];
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::__construct
     *
     * @param array<?Point> $points
     */
    public function testValidComponents(array $points): void
    {
        $multiPoint = new MultiPoint($points);

        $this->assertNotNull($multiPoint);

        $this->assertInstanceOf(MultiPoint::class, $multiPoint);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function providerInvalidComponents(): array
    {
        return [
            'LineString component' => [[LineString::fromArray([[1,2],[3,4]])]],
            'string component'     => [["text"]],
        ];
    }

    /**
     * @dataProvider providerInvalidComponents
     * @covers ::__construct
     *
     * @param array<mixed> $components
     */
    public function testConstructorWithInvalidComponents($components): void
    {
        $this->expectException(InvalidGeometryException::class);

        new MultiPoint($components);
    }

    /**
     * @covers ::fromArray
     */
    public function testFromArray(): void
    {
        $this->assertEquals(
            MultiPoint::fromArray([[1,2,3,4], [5,6,7,8]]),
            new MultiPoint([new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)])
        );
    }

    /**
     * @covers ::__construct
     * @covers ::geometryType
     */
    public function testGeometryType(): void
    {
        $multiPoint = new MultiPoint();

        $this->assertEquals(\geoPHP\Geometry\Geometry::MULTI_POINT, $multiPoint->geometryType());

        $this->assertInstanceOf('\geoPHP\Geometry\MultiPoint', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\MultiGeometry', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\Geometry', $multiPoint);
    }

    /**
     * @covers ::is3D
     */
    public function testIs3D(): void
    {
        $this->assertFalse((new MultiPoint([new Point(1, 2)]))->is3D());
        $this->assertTrue((new MultiPoint([new Point(1, 2, 3)]))->is3D());
        $this->assertTrue((new MultiPoint([new Point(1, 2, 3, 4)]))->is3D());
    }

    /**
     * @covers ::isMeasured
     */
    public function testIsMeasured(): void
    {
        $this->assertFalse((new MultiPoint([new Point(1, 2)]))->isMeasured());
        $this->assertFalse((new MultiPoint([new Point(1, 2, 3)]))->isMeasured());
        $this->assertTrue((new MultiPoint([new Point(1, 2, 3, 4)]))->isMeasured());
    }

    /**
     * @return array<mixed>
     */
    public function providerCentroid(): array
    {
        return [
            [[], []],
            [[[0, 0], [0, 10]], [0, 5]]
        ];
    }

    /**
     * @dataProvider providerCentroid
     * @covers ::centroid
     *
     * @param array<mixed> $components
     * @param array<int>   $expectedCentroid
     */
    public function testCentroid(array $components, array $expectedCentroid): void
    {
        $multiPoint = MultiPoint::fromArray($components);
        $centroid = $multiPoint->centroid();

        $this->assertEquals(Point::fromArray($expectedCentroid), $centroid);
    }

    /**
     * @return array{array{array<mixed>, bool}}
     */
    public function providerIsSimple(): array
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
     *
     * @param array<mixed> $points
     * @param bool         $result
     */
    public function testIsSimple(array $points, bool $result): void
    {
        $multiPoint = MultiPoint::fromArray($points);

        $this->assertSame($result, $multiPoint->isSimple());
    }

    /**
     * @return array<string, array<int|Point[]>>
     */
    public function providerNumPoints(): array
    {
        return [
            'no components'    => [0, []],
            'empty Point comp' => [0, [new Point()]],
            'xy'               => [1, [new Point(1, 2)]],
            '2 xy'             => [2, [new Point(1, 2), new Point(3, 4)]],
            'one is empty'     => [1, [new Point(), new Point(1, 2)]],
        ];
    }

    /**
     * @dataProvider providerNumPoints
     * @covers ::numPoints
     *
     * @param Point[]|array{} $points
     */
    public function testNumPoints(int $expectedNum, array $points): void
    {
        $multiPoint = new MultiPoint($points);

        $this->assertEquals($expectedNum, $multiPoint->numPoints());
    }

    /**
     * @dataProvider providerValidComponents
     * @covers ::dimension
     * @covers ::boundary
     * @covers ::explode
     *
     * @param array<mixed> $points
     */
    public function testTrivialAndNotValidMethods(array $points): void
    {
        $point = new MultiPoint($points);

        $this->assertSame(0, $point->dimension());

        $this->assertEquals(new \geoPHP\Geometry\GeometryCollection(), $point->boundary());

        $this->assertNull($point->explode());

        $this->assertTrue($point->isSimple());
    }
}
