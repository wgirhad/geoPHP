<?php

namespace geoPHP\Tests\Benchmark\Geometry;

use geoPHP\Geometry\Polygon;

/**
 * @property Polygon $geometry
 */
class PolygonBench extends AbstractGeometryBench
{
    public function setUpPolygonSmall(): void
    {
        $this->geometry = $this->createPolygon(100, 10);
    }

    public function setUpPolygonLarge(): void
    {
        $this->geometry = $this->createPolygon(1000, 100);
    }

    /**
     * @return array<mixed>
     */
    public function providePolygonComponents(): array
    {
        return [[$this->createPolygonComponents(10, 10)]];
    }

    /**
     * @ParamProviders("providePolygonComponents")
     *
     * @param array<mixed> $params
     */
    public function benchCreatePolygon(array $params): void
    {
        new Polygon($params[0]);
    }

    /**
     * @BeforeMethods("setUpPolygonLarge")
     * @Revs(200)
     */
    public function benchIsEmpty(): void
    {
        ($this->geometry->isEmpty());
    }

    /**
     * @BeforeMethods("setUpPolygonLarge")
     * @Revs(200)
     */
    public function benchExteriorRing(): void
    {
        $this->geometry->exteriorRing();
    }

    /**
     * @BeforeMethods("setUpPolygonSmall")
     * @Revs(10)
     */
    public function benchGetPoints(): void
    {
        $this->geometry->getPoints();
    }

    /**
     * @BeforeMethods("setUpPolygonSmall")
     * @Revs(10)
     */
    public function benchArea(): void
    {
        $this->geometry->area();
    }
}
