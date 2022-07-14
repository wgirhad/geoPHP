<?php

namespace geoPHP\Geometry;

use geoPHP\geoPHP;
use geoPHP\Exception\InvalidGeometryException;

/**
 * MultiPolygon: A collection of Polygons
 *
 * @method Polygon[] getComponents()
 * @property Polygon[] $components
 *
 * @phpstan-consistent-constructor
 */
class MultiPolygon extends MultiSurface
{
    /**
     * Checks and stores geometry components.
     *
     * @param Polygon[] $components Array of Polygon components.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(array $components = [])
    {
        parent::__construct($components, false, Polygon::class);
    }

    /**
     *
     * Creates a MultiPolygon from array of coordinates
     *
     * @param array $coordinateArray Multi-dimensional array of coordinates
     *
     * @throws InvalidGeometryException
     *
     * @return MultiPolygon
     */
    public static function fromArray(array $coordinateArray): MultiPolygon
    {
        $points = [];
        foreach ($coordinateArray as $point) {
            $points[] = Polygon::fromArray($point);
        }
        return new static($points);
    }

    public function geometryType()
    {
        return Geometry::MULTI_POLYGON;
    }

    public function centroid()
    {
        if ($this->isEmpty()) {
            return new Point();
        }

        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            return geoPHP::geosToGeometry($this->getGeos()->centroid());
            // @codeCoverageIgnoreEnd
        }

        $x = 0;
        $y = 0;
        $totalArea = 0;
        foreach ($this->getComponents() as $component) {
            if ($component->isEmpty()) {
                continue;
            }
            $componentArea = $component->area();
            $totalArea += $componentArea;
            $componentCentroid = $component->centroid();
            $x += $componentCentroid->x() * $componentArea;
            $y += $componentCentroid->y() * $componentArea;
        }
        return new Point($x / $totalArea, $y / $totalArea);
    }

    public function area()
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->area();
            // @codeCoverageIgnoreEnd
        }

        $area = 0;
        foreach ($this->components as $component) {
            $area += $component->area();
        }
        return $area;
    }

    public function boundary()
    {
        $rings = [];
        foreach ($this->getComponents() as $component) {
            $rings = array_merge($rings, $component->components);
        }
        return new MultiLineString($rings);
    }
}
