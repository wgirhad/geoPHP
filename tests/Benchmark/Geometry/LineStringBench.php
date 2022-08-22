<?php

namespace geoPHP\Tests\Benchmark\Geometry;

use geoPHP\Geometry\LineString;

/**
 * @property LineString $geometry
 */
class LineStringBench extends AbstractGeometryBench
{
    public function setUpLineString(): void
    {
        $this->geometry = $this->createLineString(50);
    }

    /**
     * @BeforeMethods("setUpLineString")
     * @Revs(100)
     */
    public function benchInvertXY(): void
    {
        $this->geometry->invertXY();
    }

    /**
     * @BeforeMethods("setUpLineString")
     * @Revs(200)
     */
    public function benchIsEmpty(): void
    {
        $this->geometry->isEmpty();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchIsSimple(): void
    {
        $this->geometry->isSimple();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchAsArray(): void
    {
        $this->geometry->asArray();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchGetBBox(): void
    {
        $this->geometry->getBBox();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchExplode(): void
    {
        $this->geometry->explode();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchExplodeTrue(): void
    {
        $this->geometry->explode(true);
    }

    /**
     * @BeforeMethods("setUpLineString")
     * @Revs(200)
     */
    public function benchGeometryN(): void
    {
        $this->geometry->geometryN(10);
    }

    /**
     * @BeforeMethods("setUpLineString")
     * @Revs(200)
     */
    public function benchEndPoint(): void
    {
        $this->geometry->endPoint();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchLength(): void
    {
        $this->geometry->length();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchLength3D(): void
    {
        $this->geometry->length3D();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchGreatCircleLength(): void
    {
        $this->geometry->greatCircleLength();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchHaversineLength(): void
    {
        $this->geometry->haversineLength();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchVincentyLength(): void
    {
        $this->geometry->vincentyLength();
    }

    /**
     * @BeforeMethods("setUpLineString")
     */
    public function benchIsClosed(): void
    {
        $this->geometry->isClosed();
    }
}
