<?php

namespace geoPHP\Tests\Benchmark\Geometry;

use geoPHP\Geometry\Point;

/**
 * @Revs(1000)
 *
 * @property Point $geometry
 */
class PointBench extends AbstractGeometryBench
{
    public function benchCreatePointEmpty(): void
    {
        new Point();
    }

    public function benchCreatePointXY(): void
    {
        new Point(1, 2);
    }

    public function benchCreatePointXYZ(): void
    {
        new Point(1, 2, 3);
    }

    public function benchCreatePointXYZM(): void
    {
        new Point(1, 2, 3, 4);
    }

    public function benchFromArray(): void
    {
        Point::fromArray([1, 2, 4, 5]);
    }
}
