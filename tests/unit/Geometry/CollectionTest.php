<?php
/**
 * This file contains the CollectionTest class.
 * For more information see the class description below.
 *
 * @author Peter Bathory <peter.bathory@cartographia.hu>
 * @since 2020-03-19
 */

namespace geoPHP\Tests\Geometry;

use \geoPHP\Geometry\Collection;
use \geoPHP\Geometry\Point;
use \geoPHP\Geometry\LineString;
use \PHPUnit\Framework\TestCase;

/**
 * This class... TODO: Complete this
 */
class CollectionTest extends TestCase
{

    public function providerIs3D()
    {
        return [
                [[new Point(1, 2)], false],
                [[new Point(1, 2, 3)], true],
                [[new Point(1, 2, 3), new Point(1, 2)], true],
        ];
    }

    /**
     * @dataProvider providerIs3D
     * @param $components
     * @param $value
     */
    public function testIs3D($components, $value)
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, true]);

        $this->assertEquals($stub->is3D(), $value);
    }

    public function providerIsMeasured()
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
     * @param $components
     * @param $value
     */
    public function testIsMeasured($components, $value)
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, true]);

        $this->assertEquals($stub->isMeasured(), $value);
    }

    public function providerIsEmpty()
    {
        return [
                [[], true],
                [[new Point()], true],
                [[new Point(1, 2)], false],
        ];
    }

    /**
     * @dataProvider providerIsEmpty
     * @param $components
     * @param $value
     */
    public function testIsEmpty($components, $value)
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, true]);

        $this->assertEquals($stub->isEmpty(), $value);
    }

    public function testNonApplicableMethods()
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [[], true]);

        $this->assertNull($stub->x());
        $this->assertNull($stub->y());
        $this->assertNull($stub->z());
        $this->assertNull($stub->m());
    }

    public function testAsArray()
    {
        $components = [
                new Point(1, 2),
                new LineString()
        ];
        $expected = [
                [1, 2],
                []
        ];

        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, true]);

        $this->assertEquals($stub->asArray(), $expected);
    }

    public function testFlatten()
    {
        $components = [
                new Point(1, 2, 3, 4),
                new Point(5, 6, 7, 8),
                new LineString([new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)]),
        ];

        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components]);
        $stub->flatten();

        $this->assertFalse($stub->hasZ());
        $this->assertFalse($stub->isMeasured());
        $this->assertFalse($stub->getPoints()[0]->hasZ());
    }

    public function testExplode()
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

            $this->assertSame($segment->startPoint(), $points[$i]);
            $this->assertSame($segment->endPoint(), $points[$i + 1]);
        }
    }
}