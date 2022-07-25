<?php

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;

/**
 * Class MultiCurve
 * TODO write this
 *
 * @package geoPHP\Geometry
 * @method Curve[] getComponents()
 * @property Curve[] $components
 */
abstract class MultiCurve extends MultiGeometry
{
    /**
     * Checks and stores geometry components.
     *
     * @param Point[] $components           Array of Curve components.
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

    public function dimension(): int
    {
        return 1;
    }

    /**
     * MultiCurve is closed if all it's components are closed
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        foreach ($this->getComponents() as $line) {
            if (!$line->isClosed()) {
                return false;
            }
        }
        return true;
    }
}
