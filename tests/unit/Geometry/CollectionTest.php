<?php

/**
 * This file contains the CollectionTest class.
 * For more information see the class description below.
 *
 * @author Peter Bathory <peter.bathory@cartographia.hu>
 * @since 2020-03-19
 */

namespace geoPHP\Tests\Unit\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Collection;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\Polygon;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of abstracts Collection class
 *
 * @coversDefaultClass geoPHP\Geometry\Collection
 *
 * @uses geoPHP\Geometry\Point
 * @uses geoPHP\Geometry\LineString
 */
class CollectionTest extends TestCase
{
    /**
     * @return array<array{Geometry[], string}>
     */
    public function providerConstructorAllowedComponentType(): array
    {
        return [
            [[Point::fromArray([1, 2])], Point::class],
            [[LineString::fromArray([[1, 2], [3, 4]])], LineString::class],
            [
                [LineString::fromArray([[1, 2], [3, 4]]), MultiPoint::fromArray([[1, 2], [3, 4]])],
                 Collection::class,
            ]
        ];
    }

    /**
     * @dataProvider providerConstructorAllowedComponentType
     * @covers ::__construct
     *
     * @param Geometry[] $components
     * @param string $allowedComponentType
     */
    public function testConstructorAllowedComponentTypeParameter(array $components, string $allowedComponentType): void
    {
        $this->assertNotNull(
            $this->getMockForAbstractClass(Collection::class, [$components, $allowedComponentType])
        );

        // Not allowed component type
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/^Cannot construct .+\. Expected .+Polygon components, got .+\.$/');
        $this->getMockForAbstractClass(Collection::class, [$components, Polygon::class]);
    }

    public function testConstructorWrongComponent(): void
    {
        // Not allowed component type
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/^Cannot construct .+\. Expected .+Geometry components, got string\.$/');
        $this->getMockForAbstractClass(Collection::class, [[new Point(), 'foo'], Geometry::class, true]);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorAllowEmptyParameter(): void
    {
        $emptyComponent = new Point();
        $component = new Point(1, 2);

        // Allowed empty, given non empty
        $this->assertNotNull(
            $this->getMockForAbstractClass(Collection::class, [[$component], Geometry::class, true])
        );

        // Allowed empty, given empty
        $this->assertNotNull(
            $this->getMockForAbstractClass(Collection::class, [[$emptyComponent], Geometry::class, true])
        );

        // Not allowed empty, given non empty
        $this->assertNotNull(
            $this->getMockForAbstractClass(Collection::class, [[$component], Geometry::class, false])
        );

        // Not allowed empty, given empty throws exception
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessage('Cannot create a collection of empty Points (1. component)');
        $this->getMockForAbstractClass(Collection::class, [[$emptyComponent], Geometry::class, false]);
    }

    /**
     * @return array<string, array{Geometry[], bool}>
     */
    public function providerIs3D(): array
    {
        return [
                '2D' => [[new Point(1, 2)], false],
                '3D' => [[new Point(1, 2, 3)], true],
                'mixed' => [[new Point(1, 2, 3), new Point(1, 2)], true],
        ];
    }

    /**
     * @dataProvider providerIs3D
     * @covers ::is3D
     * @covers ::__construct
     *
     * @param Geometry[] $components
     * @param bool       $result
     */
    public function testIs3D(array $components, bool $result): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertEquals($result, $stub->is3D());
    }

    /**
     * @return array<array{Geometry[], bool}>
     */
    public function providerIsMeasured(): array
    {
        return [
                [[new Point()], false],
                [[new Point(1, 2)], false],
                [[new Point(1, 2, 3)], false],
                [[new Point(1, 2, 3, 4)], true],
                [[new Point(1, 2, 3, 4), new Point(1, 2)], true],
        ];
    }

    /**
     * @dataProvider providerIsMeasured
     * @covers ::isMeasured
     * @covers ::__construct
     *
     * @param Geometry[] $components
     * @param bool       $result
     */
    public function testIsMeasured(array $components, bool $result): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertEquals($result, $stub->isMeasured());
    }

    /**
     * @dataProvider providerIsMeasured
     * @dataProvider providerInvertXY
     * @covers ::getComponents
     *
     * @param Geometry[] $components
     */
    public function testGetComponents(array $components): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertEquals($components, $stub->getComponents());
    }

    /**
     * @return array<array{Geometry[], Geometry[]}>
     */
    public function providerInvertXY(): array
    {
        return [
                [
                    [new Point(1, 2, 3)],
                    [new Point(2, 1, 3)]
                ],
                [
                    [LineString::fromArray([[1, 2, 3], [5, 6, 7]]), Point::fromArray([10, 11, 12])],
                    [LineString::fromArray([[2, 1, 3], [6, 5, 7]]), Point::fromArray([11, 10, 12])],
                ],
        ];
    }

    /**
     * @dataProvider providerInvertXY
     * @covers ::invertXY
     *
     * @param Geometry[] $components
     */
    public function testInvertXY(array $components): void
    {
        /** @var Collection */
        $collection = $this->getMockForAbstractClass(Collection::class, [$components]);
        /** @var Collection */
        $expectedCollection = $this->getMockForAbstractClass(Collection::class, [$components]);

        $inverse = $collection->invertXY();

        // InvertXY returns the inverted geometry
        $this->assertEquals($expectedCollection, $inverse);

        // invertXY() alters the original geometry
        $this->assertSame($collection, $inverse);

        // Must be symmetric, invertXY()->invertXY() gives the original geometry
        $this->assertEquals($expectedCollection, $inverse->invertXY()->invertXY());
    }

    /**
     * @covers ::flatten
     */
    public function testFlatten(): void
    {
        $components = [
                new Point(1, 2, 3, 4),
                new Point(5, 6, 7, 8),
                new LineString([new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)]),
        ];

        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components]);
        $stub->flatten();

        $this->assertFalse($stub->is3D());
        $this->assertFalse($stub->isMeasured());
        $this->assertFalse($stub->getPoints()[0]->is3D());
    }

    /**
     * @return array<array{array<Geometry>, bool}>
     */
    public function providerIsEmpty(): array
    {
        return [
                [[], true],
                [[new Point()], true],
                [[new Point(1, 2)], false],
        ];
    }

    /**
     * @dataProvider providerIsEmpty
     * @covers ::isEmpty
     *
     * @param Geometry[] $components
     * @param bool       $result
     */
    public function testIsEmpty(array $components, bool $result): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertEquals($result, $stub->isEmpty());
    }

    /**
     * @covers ::x
     * @covers ::y
     * @covers ::z
     * @covers ::m
     */
    public function testNonApplicableMethods(): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [[], Geometry::class, true]);

        $this->assertNull($stub->x());
        $this->assertNull($stub->y());
        $this->assertNull($stub->z());
        $this->assertNull($stub->m());
    }

    /**
     * @covers ::asArray
     */
    public function testAsArray(): void
    {
        $components = [
                new Point(1, 2),
                new LineString(),
                LineString::fromArray([[1, 2, 3], [5, 6, 7]])
        ];
        $expected = [
                [1, 2],
                [],
                [[1, 2, 3], [5, 6, 7]],
        ];

        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertEquals($expected, $stub->asArray());
    }

    /**
     * @dataProvider providerConstructorAllowedComponentType
     * @covers ::numGeometries
     *
     * @param array<mixed> $components
     */
    public function testNumGeometries(array $components): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components]);

        $this->assertEquals(count($components), $stub->numGeometries());
    }

    /**
     * @dataProvider providerConstructorAllowedComponentType
     * @covers ::GeometryN
     *
     * @param array<mixed> $components
     */
    public function testGeometryN(array $components): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components]);

        for ($i = 0; $i < count($components) + 1; ++$i) {
            $this->assertEquals($components[$i] ?? null, $stub->geometryN($i + 1));
        }
    }

    /**
     * @return array<string, array<int|Geometry[]>>
     */
    public function providerNumPoints(): array
    {
        return [
            'no components'     => [0, []],
            'linestring'        => [2, [LineString::fromArray([[1, 2], [3, 4]])]],
            'linestring, point' => [3, [LineString::fromArray([[1, 2], [3, 4]]), new Point(5, 6)]],
        ];
    }

    /**
     * @dataProvider providerNumPoints
     * @covers ::numPoints
     *
     * @param Geometry[]|array{} $components
     */
    public function testNumPoints(int $expectedNum, array $components): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components]);

        $this->assertEquals($expectedNum, $stub->numPoints());
    }

    /**
     * @return array<string, array<int|Geometry[]>>
     */
    public function providerGetPoints(): array
    {
        return [
            'no components'     => [[], []],
            'linestring, point' => [
                [LineString::fromArray([[1, 2], [3, 4]]), new Point(5, 6)],
                [new Point(1, 2), new Point(3, 4), new Point(5, 6)]
            ],
        ];
    }

    /**
     * @dataProvider providerGetPoints
     * @covers ::getPoints
     *
     * @param Geometry[]|array{} $components
     * @param Point[]|array{} $expectedPoints
     */
    public function testGetPoints(array $components, array $expectedPoints): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components]);

        $this->assertEquals($expectedPoints, $stub->getPoints());
    }

    /**
     * @covers ::explode
     */
    public function testExplode(): void
    {
        $points = [new Point(1, 2), new Point(3, 4), new Point(5, 6), new Point(1, 2)];
        $components = [
                new \geoPHP\Geometry\Polygon([new LineString($points)])
        ];

        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components]);

        $segments = $stub->explode();
        $this->assertCount(count($points) - 1, $segments);
        foreach ($segments as $i => $segment) {
            $this->assertCount(2, $segment->getComponents());

            $this->assertSame($points[$i], $segment->startPoint());
            $this->assertSame($points[$i + 1], $segment->endPoint());
        }
    }

    /**
     * @return array<string, array{Geometry[], Geometry, ?float}>
     */
    public function providerDistance(): array
    {
        return [
            "collection of points to point" => [
                [new Point(1, 1), new Point(2, 1)],
                new Point(2, 0),
                1.0
            ],
            "collection of points to touching point" => [
                [new Point(1, 1), new Point(2, 1)],
                new Point(2, 1),
                0.0
            ],
            "collection of points to touching point in between" => [
                [new Point(1, 1), new Point(2, 1)],
                new Point(1.5, 1),
                0.5
            ],
            "collection of points to empty point" => [
                [new Point(1, 1), new Point(2, 2)],
                new Point(),
                null
            ],
            "collection of points with one empty point to point" => [
                [new Point(1, 0), new Point()],
                new Point(2, 0),
                1.0
            ],
            "collection of points to linestring" => [
                [new Point(1, 1), new Point(2, 1)],
                LineString::fromArray([[0, 0], [2, 0]]),
                1.0
            ],
            //  . | .
            //    |
            "collection of points to crossing linestring" => [
                [new Point(0, 0), new Point(2, 0)],
                LineString::fromArray([[1, -1], [1, 1]]),
                1.0
            ],
        ];
    }

    /**
     * @dataProvider providerDistance
     * @covers ::distance
     *
     * @param Geometry[] $components
     * @param Geometry $otherGeometry
     * @param float|null $expectedDistance
     */
    public function testDistance(array $components, Geometry $otherGeometry, ?float $expectedDistance): void
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertSame($expectedDistance, $stub->distance($otherGeometry));
    }
}
