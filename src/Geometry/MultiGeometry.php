<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\geoPHP;

use function abs;

use const PHP_INT_MAX;

/**
 * MultiGeometry is an abstract collection of geometries.
 *
 * @package geoPHP\Geometry
 */
abstract class MultiGeometry extends Collection
{
    /**
     * Checks and stores geometry components.
     *
     * @param Geometry[] $components        Array of Geometry components.
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
     * A collection is simple if all it's components are simple.
     *
     * @return bool
     */
    public function isSimple(): ?bool
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->isSimple();
            // @codeCoverageIgnoreEnd
        }

        foreach ($this->components as $component) {
            if (!$component->isSimple()) {
                return false;
            }
        }

        return true;
    }

    /**
     * By default, the boundary of a collection is the boundary of it's components.
     *
     * @return Geometry
     */
    public function boundary(): ?Geometry
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

    public function area(): float
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->area();
            // @codeCoverageIgnoreEnd
        }

        $area = 0.0;
        foreach ($this->components as $component) {
            $area += $component->area();
        }
        return $area;
    }

    /**
     *  Returns the length of this Collection in its associated spatial reference.
     * Eg. if Geometry is in geographical coordinate system it returns the length in degrees
     * @return float
     */
    public function length(): float
    {
        $length = 0.0;
        foreach ($this->components as $component) {
            $length += $component->length();
        }
        return $length;
    }

    public function length3D(): float
    {
        $length = 0.0;
        foreach ($this->components as $component) {
            $length += $component->length3D();
        }
        return $length;
    }

    /**
     * Returns the degree based Geometry' length in meters.
     *
     * @param float $radius Default is the semi-major axis of WGS84.
     * @return float Length in meters.
     */
    public function greatCircleLength(float $radius = geoPHP::EARTH_WGS84_SEMI_MAJOR_AXIS): float
    {
        $length = 0.0;
        foreach ($this->components as $component) {
            $length += $component->greatCircleLength($radius);
        }
        return $length;
    }

    public function haversineLength(): float
    {
        $length = 0.0;
        foreach ($this->components as $component) {
            $length += $component->haversineLength();
        }
        return $length;
    }

    public function vincentyLength(): float
    {
        $length = 0.0;
        foreach ($this->components as $component) {
            $length += $component->vincentyLength();
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
            return (float) abs($startPoint->z() - $endPoint->z());
        } else {
            return null;
        }
    }

    public function elevationGain(float $verticalTolerance = 0.0): float
    {
        $gain = 0.0;
        foreach ($this->components as $component) {
            $gain += $component->elevationGain($verticalTolerance);
        }
        return (float) $gain;
    }

    public function elevationLoss(float $verticalTolerance = 0.0): float
    {
        $loss = 0.0;
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



    public function startPoint(): ?Point
    {
        return null;
    }

    public function endPoint(): ?Point
    {
        return null;
    }

    public function isRing(): ?bool
    {
        return null;
    }

    public function isClosed(): ?bool
    {
        return null;
    }

    public function pointN(int $n): ?Point
    {
        return null;
    }

    public function exteriorRing(): ?LineString
    {
        return null;
    }

    public function numInteriorRings(): ?int
    {
        return null;
    }

    public function interiorRingN(int $n): ?LineString
    {
        return null;
    }
}
