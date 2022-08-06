<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;

/**
 * Class Curve
 * TODO write this
 *
 * @package geoPHP\Geometry
 *
 * @property Point[] $components A curve consists of sequence of Points
 * @method Point[] getComponents()
 * @method Point|null geometryN(int $n)
 */
abstract class Curve extends Collection
{
    /**
     * Checks and stores geometry components.
     *
     * @param Point[] $components           Array of Point components.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(array $components = [])
    {
        if (is_array($components) && count($components) == 1) {
            throw new InvalidGeometryException("Cannot construct a " . static::class . " with a single point");
        }

        parent::__construct($components, Point::class, false);
    }

    /**
     * @var Point|null
     */
    protected $startPoint = null;

    /**
     * @var Point|null
     */
    protected $endPoint = null;

    /**
     * Returns the name of the instantiable subtype of Geometry of which the geometric object is an instantiable member.
     *
     * @return string
     */
    public function geometryType(): string
    {
        return Geometry::CURVE;
    }

    /**
     * The inherent dimension of the geometric object, which must be less than or equal to the coordinate dimension.
     * In non-homogeneous collections, this will return the largest topological dimension of the contained objects.
     *
     * @return int
     */
    public function dimension(): int
    {
        return 1;
    }

    /**
     * Returns true if the Curve represents the empty set.
     *
     * A Curve is empty if and only if it has no components.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->components) === 0;
    }

    /**
     * The boundary of a non-closed Curve consists of its two end Points.
     * End point are represented as a MultiPoint geometry.
     *
     * @see OGC SFA 6.1.6.1
     *
     * @return MultiPoint
     */
    public function boundary(): ?Geometry
    {
        return $this->isEmpty() || $this->isClosed()
            ? new MultiPoint()
            : new MultiPoint([$this->startPoint(), $this->endPoint()]);
    }

    public function startPoint(): ?Point
    {
        if (!isset($this->startPoint)) {
            $this->startPoint = $this->components[0] ?? null;
        }
        return $this->startPoint;
    }

    public function endPoint(): ?Point
    {
        if (!isset($this->endPoint)) {
            $this->endPoint = $this->components[count($this->components) - 1] ?? null;
        }
        return $this->endPoint;
    }

    /**
     * A Curve is closed if its start Point is equal to its end Point.
     *
     * @see OGC SFA 6.1.6.1
     *
     * @return boolean
     */
    public function isClosed(): bool
    {
        return !$this->isEmpty()
            ? $this->startPoint()->equals($this->endPoint())
            : false;
    }

    public function isRing(): bool
    {
        return ($this->isClosed() && $this->isSimple());
    }

    /**
     * @return Point[]
     */
    public function getPoints(): array
    {
        return $this->getComponents();
    }

    // Not valid for this geometry type
    // --------------------------------
    public function area(): float
    {
        return 0.0;
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
