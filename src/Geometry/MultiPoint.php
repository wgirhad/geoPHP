<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\geoPHP;
use geoPHP\Exception\InvalidGeometryException;

/**
 * A MultiPoint is a 0-dimensional Collection.
 * The elements of a MultiPoint are restricted to Points.
 * The Points are not connected or ordered in any semantically important way.
 * A MultiPoint is simple if no two Points in the MultiPoint are equal (have identical coordinate values in X and Y).
 * Every MultiPoint is spatially equal under the definition in OGC 06-103r4 Clause 6.1.15.3 to a simple Multipoint.
 *
 * @method   Point[] getComponents()
 * @property Point[] $components The elements of a MultiPoint are Points
 *
 * @phpstan-consistent-constructor
 */
class MultiPoint extends MultiGeometry
{
    /**
     * Checks and stores geometry components.
     *
     * @param Point[] $components Array of Point components.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(array $components = [])
    {
        parent::__construct($components, Point::class);
    }

    /**
     *
     * Creates a MultiPoint from array of coordinates
     *
     * @param array{}|array<array{}|array<float|int|null>> $coordinateArray Multi-dimensional array of coordinates.
     *
     * @throws InvalidGeometryException
     *
     * @return MultiPoint
     */
    public static function fromArray(array $coordinateArray): MultiPoint
    {
        $points = [];
        foreach ($coordinateArray as $point) {
            $points[] = Point::fromArray($point);
        }
        return new static($points);
    }

    /**
     * @return string
     */
    public function geometryType(): string
    {
        return Geometry::MULTI_POINT;
    }

    /**
     * MultiPoint is 0-dimensional
     * @return int 0
     */
    public function dimension(): int
    {
        return 0;
    }

    /**
     * A MultiPoint is simple if no two Points in the MultiPoint are equal
     * (have identical coordinate values in X and Y).
     *
     * @return bool
     */
    public function isSimple(): ?bool
    {
        $componentCount = count($this->components);
        for ($i = 0; $i < $componentCount; $i++) {
            for ($j = $i + 1; $j < $componentCount; $j++) {
                if ($this->components[$i]->equals($this->components[$j])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * The boundary of a MultiPoint is the empty set.
     * @return GeometryCollection
     */
    public function boundary(): ?Geometry
    {
        return new GeometryCollection();
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
        foreach ($this->getComponents() as $component) {
            $x += $component->x();
            $y += $component->y();
        }
        return new Point($x / $this->numPoints(), $y / $this->numPoints());
    }

    // Not valid for this geometry type
    // --------------------------------
    public function explode(bool $toArray = false): ?array  // @phpstan-ignore-line
    {
        return null;
    }
}
