<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;

/**
 * A Surface is a 2-dimensional abstract geometric object.
 *
 * OGC 06-103r4 6.1.10 specification:
 * A simple Surface may consists of a single “patch” that is associated with one “exterior boundary” and 0 or more
 * “interior” boundaries. A single such Surface patch in 3-dimensional space is isometric to planar Surfaces, by a
 * simple affine rotation matrix that rotates the patch onto the plane z = 0. If the patch is not vertical, the
 * projection onto the same plane is an isomorphism, and can be represented as a linear transformation, i.e. an affine.
 *
 * @package geoPHP\Geometry
 */
abstract class Surface extends Collection
{
    /**
     * Checks and stores geometry components.
     *
     * @param Curve[]|Polygon[] $components Array of Curve or Polygon components.
     * @param string  $allowedComponentType A class the components must be instance of. Default: Geometry.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(
        array $components = [],
        string $allowedComponentType = Geometry::class
    ) {
        parent::__construct($components, $allowedComponentType);
    }

    public function geometryType(): string
    {
        return Geometry::SURFACE;
    }

    public function dimension(): int
    {
        return 2;
    }

    /**
     * Returns true if the Surface represents the empty set.
     *
     * A Surface is empty if and only if it has no components.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->components) === 0;
    }

    public function startPoint(): ?Point
    {
        return null;
    }

    public function endPoint(): ?Point
    {
        return null;
    }

    public function pointN(int $n): ?Point
    {
        return null;
    }

    public function isClosed(): ?bool
    {
        return null;
    }

    public function isRing(): ?bool
    {
        return null;
    }

    public function length(): float
    {
        return 0.0;
    }

    public function length3D(): float
    {
        return 0.0;
    }

    public function haversineLength(): float
    {
        return 0.0;
    }

    public function vincentyLength(): float
    {
        return 0.0;
    }

    public function greatCircleLength(float $radius = null): float
    {
        return 0.0;
    }

    public function minimumZ(): ?float
    {
        return null;
    }

    public function maximumZ(): ?float
    {
        return null;
    }

    public function minimumM(): ?float
    {
        return null;
    }

    public function maximumM(): ?float
    {
        return null;
    }

    public function elevationGain(float $verticalTolerance = 0.0): ?float
    {
        return null;
    }

    public function elevationLoss(float $verticalTolerance = 0.0): ?float
    {
        return null;
    }

    public function zDifference(): ?float
    {
        return null;
    }
}
