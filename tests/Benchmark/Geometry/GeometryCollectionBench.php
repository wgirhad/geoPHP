<?php

namespace geoPHP\Tests\Benchmark\Geometry;

use geoPHP\Geometry\GeometryCollection;

/**
 * @property GeometryCollection $geometry
 */
class GeometryCollectionBench extends AbstractGeometryBench
{
    public function setUpGeometryCollectionBig(): void
    {
        $this->geometry = $this->createGeometryCollection(1);
    }

    public function setUpGeometryCollectionSmall(): void
    {
        $this->geometry = $this->createGeometryCollection(1);
    }

    /**
     * @BeforeMethods("setUpGeometryCollectionBig")
     */
    public function benchGetPoints(): void
    {
        $this->geometry->getPoints();
    }

    /**
     * @BeforeMethods("setUpGeometryCollectionBig")
     */
    public function benchExplodeGeometries(): void
    {
        $this->geometry->explodeGeometries();
    }
}
