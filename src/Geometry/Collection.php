<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\geoPHP;

use function count;
use function gettype;
use function get_class;

/**
 * Collection: Abstract class for compound geometries
 *
 * A geometry is a collection if it is made up of other
 * component geometries. Therefore everything but a Point
 * is a Collection. For example a LingString is a collection
 * of Points. A Polygon is a collection of LineStrings etc.
 */
abstract class Collection extends Geometry
{
    /**
     * @var Geometry[]|Collection[]
     */
    protected $components = [];

    /**
     * @var bool True if Geometry has Z (altitude) value
     */
    protected $hasZ = false;

    /**
     * @var bool True if Geometry has M (measure) value
     */
    protected $isMeasured = false;

    /**
     * Checks and stores geometry components.
     *
     * @param Geometry[] $components           Array of geometries.
     * @param bool       $allowEmptyComponents Allow creating geometries with empty components. Default: false.
     * @param string     $allowedComponentType A class the components must be instance of. Default: any Geometry.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(
        array $components = [],
        string $allowedComponentType = Geometry::class,
        bool $allowEmptyComponents = false
    ) {
        /** @var Geometry[] $components */
        foreach ($components as $i => $component) {
            if ($component instanceof $allowedComponentType) {
                if (!$allowEmptyComponents && $component->isEmpty()) {
                    throw new InvalidGeometryException(
                        'Cannot create a collection of empty ' .
                        $component->geometryType() . 's (' . ($i + 1) . '. component)'
                    );
                }
                if ($component->is3D() && !$this->hasZ) {
                    $this->hasZ = true;
                }
                if ($component->isMeasured() && !$this->isMeasured) {
                    $this->isMeasured = true;
                }
            } else {
                $componentType = gettype($component) !== 'object'
                    ? gettype($component)
                    : get_class($component);
                throw new InvalidGeometryException(
                    "Cannot construct " . static::class . '. ' .
                    "Expected $allowedComponentType components, got $componentType."
                );
            }
        }
        $this->components = $components;
    }

    /**
     * Returns TRUE if this geometric object has z coordinate values.
     *
     * @return bool
     */
    public function is3D(): bool
    {
        return $this->hasZ;
    }

    /**
     * Returns TRUE if this geometric object has m coordinate values.
     *
     * @return bool
     */
    public function isMeasured(): bool
    {
        return $this->isMeasured;
    }

    /**
     * Get all sub-geometry components of the geometry.
     *
     * @return Geometry[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Swaps X and Y coordinates of the geometry.
     *
     * Useful to fix geometries with lat-lng coordinate order.
     *
     * @return self
     */
    public function invertXY(): self
    {
        foreach ($this->components as $component) {
            $component->invertXY();
        }
        $this->flushGeosCache();
        return $this;
    }

    /**
     * Removes 3D information and measures from the geometry.
     *
     * @return void
     */
    public function flatten(): void
    {
        if ($this->is3D() || $this->isMeasured()) {
            foreach ($this->components as $component) {
                $component->flatten();
            }
            $this->hasZ = false;
            $this->isMeasured = false;
            $this->flushGeosCache();
        }
    }

    /**
     * The minimum bounding box of the Geometry as array.
     *
     * @see envelope()
     *
     * @return null|array{maxy: float, miny: float, maxx: float, minx: float}
     *         Array of min and max values of x and y coordinates.
     */
    public function getBBox(): ?array
    {
        if ($this->isEmpty()) {
            return null;
        }

        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            $envelope = $this->getGeos()->envelope();
            /** @noinspection PhpUndefinedMethodInspection */
            if ($envelope->typeName() == 'Point') {
                return geoPHP::geosToGeometry($envelope)->getBBox();
            }

            /** @noinspection PhpUndefinedMethodInspection */
            $geosRing = $envelope->exteriorRing();
            /** @noinspection PhpUndefinedMethodInspection */
            return [
                    'maxy' => $geosRing->pointN(3)->getY(),
                    'miny' => $geosRing->pointN(1)->getY(),
                    'maxx' => $geosRing->pointN(1)->getX(),
                    'minx' => $geosRing->pointN(3)->getX(),
            ];
            // @codeCoverageIgnoreEnd
        }

        // Go through each component and get the max and min x and y
        $maxX = $maxY = $minX = $minY = 0.0;
        foreach ($this->components as $i => $component) {
            $componentBoundingBox = $component->getBBox();
            if ($componentBoundingBox === null) {
                continue;
            }

            // On the first run through, set the bounding box to the component's bounding box
            if ($i == 0) {
                $maxX = $componentBoundingBox['maxx'];
                $maxY = $componentBoundingBox['maxy'];
                $minX = $componentBoundingBox['minx'];
                $minY = $componentBoundingBox['miny'];
            }

            // Do a check and replace on each boundary, slowly growing the bounding box
            $maxX = $componentBoundingBox['maxx'] > $maxX ? $componentBoundingBox['maxx'] : $maxX;
            $maxY = $componentBoundingBox['maxy'] > $maxY ? $componentBoundingBox['maxy'] : $maxY;
            $minX = $componentBoundingBox['minx'] < $minX ? $componentBoundingBox['minx'] : $minX;
            $minY = $componentBoundingBox['miny'] < $minY ? $componentBoundingBox['miny'] : $minY;
        }

        return [
                'maxy' => $maxY,
                'miny' => $minY,
                'maxx' => $maxX,
                'minx' => $minX,
        ];
    }

    /**
     * Returns every sub-geometry as a multidimensional array.
     *
     * @return array<mixed>
     */
    public function asArray(): array
    {
        $array = [];
        foreach ($this->components as $component) {
            $array[] = $component->asArray();
        }
        return $array;
    }

    /**
     * The number of component geometries in the collection.
     *
     * @return int|null
     */
    public function numGeometries(): ?int
    {
        return count($this->components);
    }

    /**
     * Returns the geometry N. in the collection. Note that the index starts at 1.
     *
     * @param int $n 1-based index.
     *
     * @return Geometry|null The geometry, or null if not found.
     */
    public function geometryN(int $n): ?Geometry
    {
        return isset($this->components[$n - 1]) ? $this->components[$n - 1] : null;
    }

    /**
     * Returns true if the geometric object is the empty Geometry.
     *
     * A collection is not empty if it has at least one non empty component.
     *
     * @return bool If true, then the geometric object represents the empty point set ∅ for the coordinate space.
     */
    public function isEmpty(): bool
    {
        foreach ($this->components as $component) {
            if (!$component->isEmpty()) {
                return false;
            }
        }
        return true;
    }

    /**
     * The number of Points in the Geometry.
     *
     * @return int
     */
    public function numPoints(): int
    {
        $num = 0;
        foreach ($this->components as $component) {
            $num += $component->numPoints();
        }
        return $num;
    }

    /**
     * Get all the points of the geometry.
     *
     * @return Point[]
     */
    public function getPoints(): array
    {
        $points = [];
        foreach ($this->getComponents() as $component) {
            if ($component instanceof Point) {
                $points[] = $component;
            } else {
                foreach ($component->getPoints() as $componentPoint) {
                    $points[] = $componentPoint;
                }
            }
        }
        return $points;
    }

    /**
     * Returns TRUE if this geometry is "spatially equal" to other geometry.
     *
     * @param Geometry $geometry
     *
     * @return bool
     */
    public function equals(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->equals($geometry->getGeos());
            // @codeCoverageIgnoreEnd
        }

        // To test for equality we check to make sure that there is a matching point
        // in the other geometry for every point in this geometry.
        // This is slightly more strict than the standard, which
        // uses Within(A,B) = true and Within(B,A) = true
        // @@TODO: Eventually we could fix this by using some sort of simplification
        // method that strips redundant vertices (that are all in a row)

        $thisPoints = $this->getPoints();
        $otherPoints = $geometry->getPoints();

        // First do a check to make sure they have the same number of vertices
        if (count($thisPoints) != count($otherPoints)) {
            return false;
        }

        foreach ($thisPoints as $point) {
            $foundMatch = false;
            foreach ($otherPoints as $key => $testPoint) {
                if ($point->equals($testPoint)) {
                    $foundMatch = true;
                    unset($otherPoints[$key]);
                    break;
                }
            }
            if (!$foundMatch) {
                return false;
            }
        }

        // All points match, return TRUE
        return true;
    }

    /**
     * Get all line segments.
     *
     * @param bool $toArray Return segments as LineString or array of start and end points. Explode(true) is faster.
     *
     * @return LineString[] Returns line segments.
     */
    public function explode(bool $toArray = false): ?array
    {
        $parts = [];
        foreach ($this->components as $component) {
            foreach ($component->explode($toArray) as $part) {
                $parts[] = $part;
            }
        }
        return $parts;
    }

    /**
     * The distance of this geometry to another geometry in their associated spatial reference.
     *
     * @return float|null
     */
    public function distance(Geometry $geometry): ?float
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->distance($geometry->getGeos());
            // @codeCoverageIgnoreEnd
        }
        $distance = null;
        foreach ($this->components as $component) {
            $componentDistance = $component->distance($geometry);
            // Stop if distance of a component is zero.
            if ($componentDistance === 0.0) {
                return 0.0;
            }
            // Distance to/from an empty geometry is null. Just skip this geometry.
            if ($componentDistance === null) {
                continue;
            }
            if ($distance === null || $componentDistance < $distance) {
                $distance = $componentDistance;
            }
        }
        return $distance;
    }

    // Not valid for this geometry type
    // --------------------------------
    public function x(): ?float
    {
        return null;
    }

    public function y(): ?float
    {
        return null;
    }

    public function z(): ?float
    {
        return null;
    }

    public function m(): ?float
    {
        return null;
    }
}
