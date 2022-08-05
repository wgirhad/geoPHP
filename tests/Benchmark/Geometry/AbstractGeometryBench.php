<?php

namespace geoPHP\Tests\Benchmark\Geometry;

use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiPolygon;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\Polygon;

/**
 *
 */
abstract class AbstractGeometryBench
{
    /** @var Geometry */
    protected $geometry;

    protected function createLineString(int $pointCount = 2, bool $closed = false): LineString
    {
        $components = [];
        for ($i = 0; $i < $pointCount; ++$i) {
            $components[] = new Point($i, $i, 1, 2);
        }

        if ($closed) {
            $components[$pointCount] = $components[0];
        }

        return new LineString($components);
    }

    /**
     * @param integer $pointCount
     * @param integer $rings
     * @return array<mixed>
     */
    protected function createPolygonComponents(int $pointCount = 4, int $rings = 1): array
    {
        $components = [];
        for ($i = 0; $i < $rings; ++$i) {
            $components[] = $this->createLineString($pointCount, true);
        }

        return $components;
    }

    protected function createPolygon(int $pointCount = 4, int $rings = 1): Polygon
    {
        return new Polygon($this->createPolygonComponents($pointCount, $rings));
    }

    protected function createMultiPolygon(int $pointCount = 4, int $rings = 1, int $polygons = 1): MultiPolygon
    {
        $components = [];
        for ($i = 0; $i < $polygons; ++$i) {
            $components[] = $this->createPolygon($pointCount, $rings);
        }

        return new MultiPolygon($components);
    }

    protected function createGeometryCollection(int $scale = 1): GeometryCollection
    {
        $gc1 = new GeometryCollection(array_fill(0, 1 * $scale, new Point(1, 2)));
        $gc2 = new GeometryCollection(array_fill(0, 1 * $scale, $gc1));
        $gc3 = new GeometryCollection(array_fill(0, 1 * $scale, $gc2));

        return new GeometryCollection(
            array_merge(
                array_fill(0, 10 * $scale, new Point(1, 2)),
                array_fill(0, 10 * $scale, $this->createLineString(10 * $scale)),
                array_fill(0, 10 * $scale, $this->createPolygon(2 * $scale + 4, 5)),
                array_fill(0, 1 * $scale, $this->createMultiPolygon($scale + 4, 5, 5)),
                array_fill(0, 1 * $scale, $gc3)
            )
        );
    }
}
