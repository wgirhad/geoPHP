<?php

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\geoPHP;

/**
 * MultiGeometry is an abstract collection of geometries
 *
 * @package geoPHP\Geometry
 */
abstract class MultiGeometry extends Collection
{
    /**
     * Checks and stores geometry components.
     *
     * @param Point[] $components           Array of Geometry components.
     * @param string  $allowedComponentType A class the components must be instance of. Default: Geometry.
     * @param bool    $allowEmptyComponents Allow creating geometries with empty components. Default: true.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(
        array $components = [],
        string $allowedComponentType = Geometry::class,
        bool $allowEmptyComponents = true
    ) {
        parent::__construct($components, $allowedComponentType, $allowEmptyComponents);
    }

    /**
     * @return bool|null
     */
    public function isSimple()
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->isSimple();
            // @codeCoverageIgnoreEnd
        }

        // A collection is simple if all it's components are simple
        foreach ($this->components as $component) {
            if (!$component->isSimple()) {
                return false;
            }
        }

        return true;
    }

    // By default, the boundary of a collection is the boundary of it's components
    public function boundary()
    {
        if ($this->isEmpty()) {
            return new LineString();
        }

        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->boundary());
            // @codeCoverageIgnoreEnd
        }

        $componentsBoundaries = [];
        foreach ($this->components as $component) {
            $componentsBoundaries[] = $component->boundary();
        }
        return geoPHP::buildGeometry($componentsBoundaries);
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

    /**
     *  Returns the length of this Collection in its associated spatial reference.
     * Eg. if Geometry is in geographical coordinate system it returns the length in degrees
     * @return float|int
     */
    public function length()
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->length();
        }
        return $length;
    }

    public function length3D()
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->length3D();
        }
        return $length;
    }

    /**
     * Returns the degree based Geometry' length in meters
     * @param float|null $radius Default is the semi-major axis of WGS84
     * @return int the length in meters
     */
    public function greatCircleLength($radius = geoPHP::EARTH_WGS84_SEMI_MAJOR_AXIS)
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->greatCircleLength($radius);
        }
        return $length;
    }

    public function haversineLength()
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->haversineLength();
        }
        return $length;
    }

    public function minimumZ(): ?float
    {
        if (!$this->is3D()) {
            return null;
        }
        $min = PHP_INT_MAX;
        foreach ($this->components as $component) {
            $componentMin = $component->minimumZ();
            if ($componentMin < $min) {
                $min = $componentMin;
            }
        }
        return $min < PHP_INT_MAX ? $min : null;
    }

    public function maximumZ(): ?float
    {
        if (!$this->is3D()) {
            return null;
        }
        $max = ~PHP_INT_MAX;
        foreach ($this->components as $component) {
            $componentMax = $component->maximumZ();
            if ($componentMax > $max) {
                $max = $componentMax;
            }
        }
        return $max > ~PHP_INT_MAX ? $max : null;
    }

    public function zDifference(): ?float
    {
        if (!$this->is3D()) {
            return null;
        }
        $startPoint = $this->startPoint();
        $endPoint = $this->endPoint();
        if ($startPoint && $endPoint) {
            return abs($startPoint->z() - $endPoint->z());
        } else {
            return null;
        }
    }

    public function elevationGain(float $verticalTolerance = 0.0): float
    {
        $gain = null;
        foreach ($this->components as $component) {
            $gain += $component->elevationGain($verticalTolerance);
        }
        return $gain;
    }

    public function elevationLoss(float $verticalTolerance = 0.0): float
    {
        $loss = null;
        foreach ($this->components as $component) {
            $loss += $component->elevationLoss($verticalTolerance);
        }
        return $loss;
    }

    public function minimumM(): ?float
    {
        if (!$this->isMeasured()) {
            return null;
        }
        $min = PHP_INT_MAX;
        foreach ($this->components as $component) {
            $componentMin = $component->minimumM();
            if ($componentMin < $min) {
                $min = $componentMin;
            }
        }
        return $min < PHP_INT_MAX ? $min : null;
    }

    public function maximumM(): ?float
    {
        if (!$this->isMeasured()) {
            return null;
        }
        $max = ~PHP_INT_MAX;
        foreach ($this->components as $component) {
            $componentMax = $component->maximumM();
            if ($componentMax > $max) {
                $max = $componentMax;
            }
        }
        return $max > ~PHP_INT_MAX ? $max : null;
    }



    public function startPoint()
    {
        return null;
    }

    public function endPoint()
    {
        return null;
    }

    public function isRing()
    {
        return null;
    }

    public function isClosed()
    {
        return null;
    }

    public function pointN($n)
    {
        return null;
    }

    public function exteriorRing()
    {
        return null;
    }

    public function numInteriorRings()
    {
        return null;
    }

    public function interiorRingN($n)
    {
        return null;
    }
}
