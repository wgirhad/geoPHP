<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;

use function gettype;
use function is_finite;
use function is_numeric;
use function sqrt;

/**
 * A Point is a 0-dimensional geometric object and represents a single location in coordinate space.
 * A Point has an x-coordinate value, a y-coordinate value.
 * If called for by the associated Spatial Reference System, it may also have coordinate values for z and m.
 *
 * @phpstan-consistent-constructor
 */
class Point extends Geometry
{
    /**
     * @var float|null
     */
    protected $x = null;

    /**
     * @var float|null
     */
    protected $y = null;

    /**
     * @var float|null
     */
    protected $z = null;

    /**
     * @var float|null
     */
    protected $m = null;

    /**
     * Checks and stores coordinates.
     *
     * @param int|float|string|null $x The x coordinate (or longitude)
     * @param int|float|string|null $y The y coordinate (or latitude)
     * @param int|float|string|null $z The z coordinate (or altitude) - optional
     * @param int|float|string|null $m Measure - optional
     *
     * @throws InvalidGeometryException
     */
    public function __construct($x = null, $y = null, $z = null, $m = null)
    {
        // If both X and Y is null, than it is an empty point
        if ($x === null && $y === null) {
            return;
        }

        // Basic validation: x and y must be numeric and finite (non NaN).
        if (!is_numeric($x) || !is_numeric($y) || !is_finite((float) $x) || !is_finite((float) $y)) {
            throw new InvalidGeometryException(
                'Cannot construct Point, x and y must be numeric and finite, ' . gettype($x) . ' given.'
            );
        }

        // Convert to float in case they are passed in as a string or integer etc.
        $this->x = (float) $x;
        $this->y = (float) $y;

        // Check to see if this point has Z (height) value
        if ($z !== null) {
            if (!is_numeric($z) || !is_finite((float) $z)) {
                throw new InvalidGeometryException(
                    'Cannot construct Point, z must be numeric and finite, ' . gettype($z) . ' given.'
                );
            }
            $this->z = (float) $z;
        }

        // Check to see if this is a measure
        if ($m !== null) {
            if (!is_numeric($m) || !is_finite((float) $m)) {
                throw new InvalidGeometryException(
                    'Cannot construct Point, m must be numeric and finite, ' . gettype($m) . ' given.'
                );
            }
            $this->m = (float) $m;
        }
    }

    /**
     *
     * Creates a Point from array of coordinates
     *
     * @param array<float|int|null> $coordinateArray Array of coordinates.
     *
     * @throws InvalidGeometryException
     *
     * @return Point
     */
    public static function fromArray(array $coordinateArray): Point
    {
        [$x, $y, $z, $m] = array_merge($coordinateArray, [null, null, null, null]);

        return new static($x, $y, $z, $m);
    }

    public function geometryType(): string
    {
        return Geometry::POINT;
    }

    public function dimension(): int
    {
        return 0;
    }

    /**
     * Get X (longitude) coordinate
     *
     * @return float|null The X coordinate
     */
    public function x(): ?float
    {
        return $this->x;
    }

    /**
     * Returns Y (latitude) coordinate
     *
     * @return float|null The Y coordinate
     */
    public function y(): ?float
    {
        return $this->y;
    }

    /**
     * Returns Z (altitude) coordinate
     *
     * @return float|null The Z coordinate or NULL is not a 3D point
     */
    public function z(): ?float
    {
        return $this->z;
    }

    /**
     * Returns M (measured) value
     *
     * @return float|null The measured value
     */
    public function m(): ?float
    {
        return $this->m;
    }

    public function is3D(): bool
    {
        return $this->z !== null;
    }

    public function isMeasured(): bool
    {
        return $this->m !== null;
    }

    /**
     * Inverts x and y coordinates
     * Useful with old applications still using lng lat
     *
     * @return self
     * */
    public function invertXY(): self
    {
        $tempX = $this->x;
        $this->x = $this->y;
        $this->y = $tempX;

        $this->flushGeosCache();

        return $this;
    }

    /**
     * Centroid of a point is itself
     *
     * @return self
     */
    public function centroid(): self
    {
        return $this;
    }

    /**
     * @return array{maxy: ?float, miny: ?float, maxx: ?float, minx: ?float}
     */
    public function getBBox(): array
    {
        return [
                'maxy' => $this->y(),
                'miny' => $this->y(),
                'maxx' => $this->x(),
                'minx' => $this->x(),
        ];
    }

    /**
     * @return array{}|array{float, float}|array{float, float, float}|array{float, float, float|null, float}
     */
    public function asArray(): array
    {
        if ($this->isEmpty()) {
            return [];
        }
        if (!$this->is3D() && !$this->isMeasured()) {
            return [$this->x, $this->y];
        }
        if ($this->is3D() && $this->isMeasured()) {
            return [$this->x, $this->y, $this->z, $this->m];
        }
        if ($this->is3D()) {
            return [$this->x, $this->y, $this->z];
        }
        // if isMeasured
        return [$this->x, $this->y, null, $this->m];
    }

    /**
     * The boundary of a Point is the empty set.
     *
     * @return GeometryCollection
     */
    public function boundary(): ?Geometry
    {
        return new GeometryCollection();
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->x === null;
    }

    /**
     * Returns 1 if point is not empty, or 0 if empty.
     *
     * OGC SFA defines numPoints() only for LineStrings, but PostGIS implements it for all geometries.
     * PostGIS' approach seems more useful, so we follow their standard.
     * PostGIS doesn't take empty points into acount.
     *
     * Note: Behaviour of this method has changed in version 2.1.
     *
     * @return int
     */
    public function numPoints(): int
    {
        return $this->isEmpty()
            ? 0
            : 1;
    }

    /**
     * @return Point[]
     */
    public function getPoints(): array
    {
        return [$this];
    }

    /**
     * @return Point[]
     */
    public function getComponents(): array
    {
        return [$this];
    }

    /**
     * Determines weather the specified geometry is spatially equal to this Point
     *
     * Because of limited floating point precision in PHP, equality can be only approximated
     * @see: http://php.net/manual/en/function.bccomp.php
     * @see: http://php.net/manual/en/language.types.float.php
     *
     * @param Geometry $geometry
     *
     * @return bool
     */
    public function equals(Geometry $geometry): bool
    {
        return $geometry->geometryType() === Geometry::POINT
            ? (abs($this->x() - $geometry->x()) <= 1.0E-9 && abs($this->y() - $geometry->y()) <= 1.0E-9)
            : false;
    }

    public function isSimple(): bool
    {
        return true;
    }

    public function flatten(): void
    {
        $this->z = null;
        $this->m = null;

        $this->flushGeosCache();
    }

    /**
     * @param Geometry $geometry
     * @return float|null
     */
    public function distance(Geometry $geometry): ?float
    {
        if ($this->isEmpty() || $geometry->isEmpty()) {
            return null;
        }
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->distance($geometry->getGeos());
            // @codeCoverageIgnoreEnd
        }
        if ($geometry->geometryType() == Geometry::POINT) {
            return sqrt(
                ($this->x() - $geometry->x()) ** 2 +
                ($this->y() - $geometry->y()) ** 2
            );
        }
        if ($geometry instanceof MultiGeometry) {
            $distance = null;
            foreach ($geometry->getComponents() as $component) {
                $checkDistance = $this->distance($component);
                if ($checkDistance === 0.0) {
                    return 0.0;
                }
                if ($checkDistance === null) {
                    continue;
                }
                if ($distance === null || $checkDistance < $distance) {
                    $distance = $checkDistance;
                }
            }
            return $distance;
        } else {
            // For LineString, Polygons, MultiLineString and MultiPolygon. the nearest point might be a vertex,
            // but it could also be somewhere along a line-segment that makes up the geometry (between vertices).
            // Here we brute force check all line segments that make up these geometries
            $distance = null;
            foreach ($geometry->explode(true) as $seg) {
                // As per http://stackoverflow.com/questions/849211/shortest-distance-between-a-point-and-a-line-segment
                // and http://paulbourke.net/geometry/pointlineplane/
                /** @var Point[] $seg */
                $x1 = $seg[0]->x();
                $y1 = $seg[0]->y();
                $x2 = $seg[1]->x();
                $y2 = $seg[1]->y();
                $dx21 = $x2 - $x1;
                $dy21 = $y2 - $y1;
                $segNorm = ($dx21 * $dx21) + ($dy21 * $dy21);
                if ($segNorm == 0) {
                    // Line-segment's endpoints are identical. This is merely a point masquerading as a line-segment.
                    $componentDistance = $this->distance($seg[1]);
                } else {
                    $x3 = $this->x();
                    $y3 = $this->y();
                    $dx31 = $x3 - $x1;
                    $dy31 = $y3 - $y1;
                    $uNorm =  (($dx31 * $dx21) + ($dy31 * $dy21)) / $segNorm;

                    if ($uNorm >= 1) {
                        $closestPointX = $x2;
                        $closestPointY = $y2;
                    } elseif ($uNorm <= 0) {
                        $closestPointX = $x1;
                        $closestPointY = $y1;
                    } else {
                        // Closest point is between p1 and p2
                        $closestPointX = $x1 + ($uNorm * $dx21);
                        $closestPointY = $y1 + ($uNorm * $dy21);
                    }
                    $dx = $closestPointX - $x3;
                    $dy = $closestPointY - $y3;

                    $componentDistance = sqrt(($dx * $dx) + ($dy * $dy));
                }
                if ($componentDistance === 0.0) {
                    return 0.0;
                }
                if ($distance === null || $componentDistance < $distance) {
                    $distance = $componentDistance;
                }
            }
            return $distance;
        }
    }

    public function minimumZ(): ?float
    {
        return $this->z();
    }

    public function maximumZ(): ?float
    {
        return $this->z();
    }

    public function minimumM(): ?float
    {
        return $this->m();
    }

    public function maximumM(): ?float
    {
        return $this->m();
    }

    /* The following methods are not valid for this geometry type */

    public function area(): float
    {
        return 0.0;
    }

    public function length(): float
    {
        return 0.0;
    }

    public function length3D(): float
    {
        return 0.0;
    }

    public function greatCircleLength(float $radius = null): float
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

    public function zDifference(): ?float
    {
        return null;
    }

    public function elevationGain(?float $verticalTolerance = 0.0): ?float
    {
        return null;
    }

    public function elevationLoss(?float $verticalTolerance = 0.0): ?float
    {
        return null;
    }

    public function numGeometries(): ?int
    {
        return null;
    }

    public function geometryN(int $n = null): ?Geometry
    {
        return null;
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

    public function pointN(int $n = null): ?Point
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

    public function interiorRingN(int $n = null): ?LineString
    {
        return null;
    }

    /**
     * @param bool $toArray
     * @return null
     */
    public function explode(bool $toArray = false): ?array  // @phpstan-ignore-line
    {
        return null;
    }
}
