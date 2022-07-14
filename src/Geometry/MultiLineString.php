<?php

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
        parent::__construct($components, false, LineString::class);
    }

    /**
     *
     * Creates a MultiLineString from array of coordinates
     *
     * @param array $coordinateArray Multi-dimensional array of coordinates
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

    public function geometryType()
    {
        return Geometry::MULTI_LINE_STRING;
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

    /**
     * The boundary of a MultiLineString is a MultiPoint, consists of the start and end points
     * of its non-closed LineStrings
     *
     * @return MultiPoint
     */
    public function boundary()
    {
        $points = [];
        foreach ($this->components as $line) {
            if (!$line->isEmpty() && !$line->isClosed()) {
                $points[] = $line->startPoint();
                $points[] = $line->endPoint();
            }
        }
        return new MultiPoint($points);
    }
}
