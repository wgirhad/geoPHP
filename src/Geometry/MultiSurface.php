<?php

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;

/**
 * Class MultiSurface
 * TODO write this
 *
 * @package geoPHP\Geometry
 */
abstract class MultiSurface extends MultiGeometry
{
    /**
     * Checks and stores geometry components.
     *
     * @param Surface[] $components           Array of Surface components.
     * @param bool      $allowEmptyComponents Allow creating geometries with empty components. Default: false.
     * @param string    $allowedComponentType A class the components must be instance of. Default: Curve.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(
        array $components = [],
        bool $allowEmptyComponents = false,
        string $allowedComponentType = Surface::class
    ) {
        parent::__construct($components, $allowEmptyComponents, $allowedComponentType);
    }

    public function geometryType()
    {
        return Geometry::MULTI_SURFACE;
    }

    public function dimension()
    {
        return 2;
    }
}
