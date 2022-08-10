<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\geoPHP;
use geoPHP\Exception\InvalidGeometryException;

/**
 * MultiLineString: A collection of LineStrings
 *
 * @method LineString[] getComponents()
 * @property LineString[] $components
 *
 * @phpstan-consistent-constructor
 */
class MultiLineString extends MultiCurve
{
    /**
     * Checks and stores geometry components.
     *
     * @param LineString[] $components Array of LineString components.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(array $components = [])
    {
        parent::__construct($components, LineString::class);
    }

    /**
     *
     * Creates a MultiLineString from array of coordinates
     *
     * @param array{}|array<array{}|array<array<float|int|null>>> $coordinateArray
     *                                                            Multi-dimensional array of coordinates.
     *
     * @throws InvalidGeometryException
     *
     * @return MultiLineString
     */
    public static function fromArray(array $coordinateArray): MultiLineString
    {
        $points = [];
        foreach ($coordinateArray as $point) {
            $points[] = LineString::fromArray($point);
        }
        return new static($points);
    }

    public function geometryType(): string
    {
        return Geometry::MULTI_LINE_STRING;
    }

    public function centroid(): Point
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
        $totalLength = 0;
        $componentLength = 0;
        $components = $this->getComponents();
        foreach ($components as $line) {
            if ($line->isEmpty()) {
                continue;
            }
            $componentCentroid = $line->getCentroidAndLength($componentLength);
            $x += $componentCentroid->x() * $componentLength;
            $y += $componentCentroid->y() * $componentLength;
            $totalLength += $componentLength;
        }

        return $totalLength === 0
            ? $this->getPoints()[0]
            : new Point($x / $totalLength, $y / $totalLength);
    }
}
