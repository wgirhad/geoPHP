<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\geoPHP;

/**
 * A MultiCurve is a 1-dimensional collection whose elements are Curves.
 *
 * MultiCurve is a non-instantiable class; it defines a set of methods for its subclasses and is
 * included for reasons of extensibility.
 *
 * @see OGC SFA 6.1.8.1
 *
 * @method Curve[] getComponents()
 * @property Curve[] $components
 */
abstract class MultiCurve extends MultiGeometry
{
    /**
     * Checks and stores geometry components.
     *
     * @param Curve[] $components           Array of Curve components.
     * @param string  $allowedComponentType A class the components must be instance of. Default: Curve.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(
        array $components = [],
        string $allowedComponentType = Curve::class
    ) {
        parent::__construct($components, $allowedComponentType);
    }

    public function geometryType(): string
    {
        return Geometry::MULTI_CURVE;
    }

    /**
     * A MultiCurve is a 1-dimensional collection.
     *
     * @return int
     */
    public function dimension(): int
    {
        return 1;
    }

    /**
     * MultiCurve is closed if all it's components are closed.
     *
     * @see OGC SFA 6.1.8.1
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        foreach ($this->getComponents() as $line) {
            if (!$line->isClosed()) {
                return false;
            }
        }
        return true;
    }

    /**
     * The boundary of a MultiCurve is obtained by applying the “mod 2” union rule:
     * A Point is in the boundary of a MultiCurve if it is in the boundaries of an odd number
     * of elements of the MultiCurve.
     * The boundary of a closed MultiCurve is always empty.
     *
     * @see OGC SFA 6.1.8.1
     *
     * @return Geometry|null
     */
    public function boundary(): ?Geometry
    {
        if (geoPHP::isGeosInstalled()) {
            // @codeCoverageIgnoreStart
            return geoPHP::geosToGeometry($this->getGeos()->boundary());
            // @codeCoverageIgnoreEnd
        }

        $points = [];
        foreach ($this->components as $line) {
            if (!$line->isEmpty() && !$line->isClosed()) {
                if (count($points) && $line->startPoint()->equals($points[count($points) - 1])) {
                    array_pop($points);
                } else {
                    $points[] = $line->startPoint();
                }

                $points[] = $line->endPoint();
            }
        }
        return new MultiPoint($points);
    }

    /**
     * A MultiCurve is simple if and only if all of its elements are simple and the only intersections
     * between any two elements occur at Points that are on the boundaries of both elements.
     *
     * @see OGC SFA 6.1.8.1
     *
     * @return bool
     */
    public function isSimple(): bool
    {
        // @codeCoverageIgnoreStart
        return parent::isSimple();
        // @codeCoverageIgnoreEnd
    }
}
