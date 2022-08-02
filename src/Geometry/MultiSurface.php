<?php

declare(strict_types=1);

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
     * @param string    $allowedComponentType A class the components must be instance of. Default: Curve.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(
        array $components = [],
        string $allowedComponentType = Surface::class
    ) {
        parent::__construct($components, $allowedComponentType);
    }

    public function geometryType(): string
    {
        return Geometry::MULTI_SURFACE;
    }

    public function dimension(): int
    {
        return 2;
    }
}
