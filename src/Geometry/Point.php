<?php

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;

/**
 * A Point is a 0-dimensional geometric object and represents a single location in coordinate space.
 * A Point has an x-coordinate value, a y-coordinate value.
 * If called for by the associated Spatial Reference System, it may also have coordinate values for z and m.
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
        if (!is_numeric($x) || !is_numeric($y) || !is_finite($x) || !is_finite($y)) {
            throw new InvalidGeometryException(
                'Cannot construct Point, x and y must be numeric, ' . gettype($x) . ' given.'
            );
        }

        // Convert to float in case they are passed in as a string or integer etc.
        $this->x = (float) $x;
        $this->y = (float) $y;

        // Check to see if this point has Z (height) value
        if ($z !== null) {
            if (!is_numeric($z) || !is_finite($z)) {
                throw new InvalidGeometryException(
                    'Cannot construct Point, z must be numeric, ' . gettype($x) . ' given.'
                );
            }
            $this->z = (float) $z;
        }

        // Check to see if this is a measure
        if ($m !== null) {
            if (!is_numeric($m) || !is_finite($m)) {
                throw new InvalidGeometryException(
                    'Cannot construct Point, m must be numeric, ' . gettype($x) . ' given.'
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return (new \ReflectionClass(get_called_class()))->newInstanceArgs($coordinateArray);
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

        $this->setGeos(null);

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
     * @return int Returns always 1
     */
    public function numPoints(): int
    {
        return 1;
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

        $this->setGeos(null);
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
                pow(($this->x() - $geometry->x()), 2)
                + pow(($this->y() - $geometry->y()), 2)
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
                // and http://paulbourke.net/geometry/pointline/
                $x1 = $seg[0]->x();
                $y1 = $seg[0]->y();
                $x2 = $seg[1]->x();
                $y2 = $seg[1]->y();
                $px = $x2 - $x1;
                $py = $y2 - $y1;
                $d = ($px * $px) + ($py * $py);
                if ($d == 0) {
                    // Line-segment's endpoints are identical. This is merely a point masquerading as a line-segment.
                    $checkDistance = $this->distance($seg[1]);
                } else {
                    $x3 = $this->x();
                    $y3 = $this->y();
                    $u =  ((($x3 - $x1) * $px) + (($y3 - $y1) * $py)) / $d;
                    if ($u > 1) {
                        $u = 1;
                    }
                    if ($u < 0) {
                        $u = 0;
                    }
                    $x = $x1 + ($u * $px);
                    $y = $y1 + ($u * $py);
                    $dx = $x - $x3;
                    $dy = $y - $y3;
                    $checkDistance = sqrt(($dx * $dx) + ($dy * $dy));
                }
                if ($checkDistance === 0.0) {
                    return 0.0;
                }
                if ($distance === null || $checkDistance < $distance) {
                    $distance = $checkDistance;
                }
            }
            return $distance;
        }
    }

    public function minimumZ(): ?float
    {
        return $this->is3D()
            ? $this->z()
            : null;
    }

    public function maximumZ(): ?float
    {
        return $this->is3D()
            ? $this->z()
            : null;
    }

    public function minimumM(): ?float
    {
        return $this->isMeasured()
            ? $this->m()
            : null;
    }

    public function maximumM(): ?float
    {
        return $this->isMeasured()
            ? $this->m()
            : null;
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
