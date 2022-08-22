<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\geoPHP;

use function abs;
use function array_merge;
use function count;
use function implode;
use function max;
use function min;

/**
 * A Polygon is a planar Surface defined by 1 exterior boundary and 0 or more interior boundaries.
 * Each interior boundary defines a hole in the Polygon.
 *
 * @method   LineString[] getComponents()
 * @method   LineString|null geometryN(int $n)
 * @property LineString[] $components
 *
 * @phpstan-consistent-constructor
 */
class Polygon extends Surface
{
    /**
     * Checks and stores geometry components.
     *
     * @param LineString[] $components  Array of LineString components.
     * @param bool         $forceCreate Force create polygon even if it's invalid because a ring is not closed.
     *                                  Default is false.
     * @throws InvalidGeometryException
     */
    public function __construct(array $components = [], bool $forceCreate = false)
    {
        parent::__construct($components, LineString::class);

        foreach ($this->getComponents() as $i => $component) {
            if ($component->numPoints() < 4) {
                throw new InvalidGeometryException(
                    'Cannot create Polygon: Invalid number of points in LinearRing. Found ' .
                    $component->numPoints() . ', expected more than 3'
                );
            }
            if (!$component->isClosed()) {
                if ($forceCreate) {
                    $this->components[$i] = new LineString(
                        array_merge($component->getComponents(), [$component->startPoint()])
                    );
                } else {
                    throw new InvalidGeometryException(
                        'Cannot create Polygon: contains non-closed ring (first point: '
                            . implode(' ', $component->startPoint()->asArray()) . ', last point: '
                            . implode(' ', $component->endPoint()->asArray()) . ')'
                    );
                }
            }
        }
    }

    /**
     *
     * Creates a Polygon from array of coordinates.
     *
     * @param array{}|array<array<array<float|int|null>>> $coordinateArray Multi-dimensional array of coordinates.
     *
     * @throws InvalidGeometryException
     *
     * @return Polygon
     */
    public static function fromArray(array $coordinateArray): Polygon
    {
        $rings = [];
        foreach ($coordinateArray as $ring) {
            $rings[] = LineString::fromArray($ring);
        }
        return new static($rings);
    }

    public function geometryType(): string
    {
        return Geometry::POLYGON;
    }

    /**
     * @param bool|false $exteriorOnly Calculate the area of exterior ring only, or the polygon with holes
     * @param bool|false $signed       Usually we want to get positive area,
     *                                 but vertices order (CW or CCW) can be determined from signed area.
     *
     * @return float
     */
    public function area(bool $exteriorOnly = false, bool $signed = false): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        if ($this->getGeos() && !$exteriorOnly) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->area();
            // @codeCoverageIgnoreEnd
        }

        $exteriorRing = $this->components[0];
        $points = $exteriorRing->getComponents();
        $pointCount = count($points);

        $a = 0.0;
        foreach ($points as $k => $p) {
            $j = ($k + 1) % $pointCount;
            $a = $a + ($p->x() * $points[$j]->y()) - ($p->y() * $points[$j]->x());
        }

        $area = $signed ? ($a / 2) : abs(($a / 2));

        if ($exteriorOnly) {
            return $area;
        }
        foreach ($this->components as $delta => $component) {
            if ($delta != 0) {
                $innerPoly = new Polygon([$component]);
                $area -= $innerPoly->area();
            }
        }
        return $area;
    }

    /**
     * @return Point
     */
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
        $totalArea = 0;
        foreach ($this->getComponents() as $i => $component) {
            $ca = $this->getRingCentroidAndArea($component);
            if ($i == 0) {
                $totalArea += $ca['area'];
                $x += $ca['x'] * $ca['area'];
                $y += $ca['y'] * $ca['area'];
            } else {
                $totalArea -= $ca['area'];
                $x += $ca['x'] * $ca['area'] * -1;
                $y += $ca['y'] * $ca['area'] * -1;
            }
        }
        if ($totalArea == 0.0) {
            return new Point();
        }
        return new Point($x / $totalArea, $y / $totalArea);
    }

    /**
     * @param LineString $ring
     * @return array{area: float, x: ?float, y: ?float}
     */
    protected function getRingCentroidAndArea(LineString $ring): array
    {
        $area = (new Polygon([$ring]))->area(true, true);

        $points = $ring->getPoints();
        $pointCount = count($points);
        if ($pointCount === 0 || $area == 0.0) {
            return ['area' => 0.0, 'x' => null, 'y' => null];
        }
        $x = 0;
        $y = 0;
        foreach ($points as $k => $point) {
            $j = ($k + 1) % $pointCount;
            $p = ($point->x() * $points[$j]->y()) - ($point->y() * $points[$j]->x());
            $x += ($point->x() + $points[$j]->x()) * $p;
            $y += ($point->y() + $points[$j]->y()) * $p;
        }
        return ['area' => abs($area), 'x' => $x / (6 * $area), 'y' => $y / (6 * $area)];
    }

    /**
     * Find the outermost point from the centroid.
     *
     * @returns Point The outermost point
     */
    public function outermostPoint(): Point
    {
        $centroid = $this->centroid();

        if ($centroid->isEmpty()) {
            return $centroid;
        }

        $maxDistance = 0.0;
        $maxPoint = null;

        foreach ($this->exteriorRing()->getPoints() as $point) {
            $distance = $centroid->distance($point);

            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $maxPoint = $point;
            }
        }

        return $maxPoint;
    }

    /**
     * Returns the exterior ring of the Polygon.
     *
     * @return LineString
     */
    public function exteriorRing(): LineString
    {
        if ($this->isEmpty()) {
            return new LineString();
        }
        return $this->components[0];
    }

    /**
     * Returns the number of interior rings in the Polygon.
     *
     * @return int
     */
    public function numInteriorRings(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }
        return $this->numGeometries() - 1;
    }

    /**
     * Returns the Nth interior ring for the Polygon as a LineString.
     *
     * @param int $n
     * @return LineString|null
     */
    public function interiorRingN(int $n): ?LineString
    {
        return $this->geometryN($n + 1);
    }

    /**
     * @return bool|null
     */
    public function isSimple(): ?bool
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->isSimple();
            // @codeCoverageIgnoreEnd
        }

        $segments = $this->explode(true);

        //TODO: instead of this O(n^2) algorithm implement Shamos-Hoey Algorithm which is only O(n*log(n))
        foreach ($segments as $i => $segment) {
            foreach ($segments as $j => $checkSegment) {
                if ($i != $j) {
                    if (Geometry::segmentIntersects($segment[0], $segment[1], $checkSegment[0], $checkSegment[1])) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * For a given point, determine whether it's bounded by the given polygon.
     * Adapted from @source http://www.assemblysys.com/dataServices/php_pointinpolygon.php
     *
     * @see http://en.wikipedia.org/wiki/Point%5Fin%5Fpolygon
     *
     * @param Point $point
     * @param boolean $pointOnBoundary - whether a boundary should be considered "in" or not
     * @param boolean $pointOnVertex - whether a vertex should be considered "in" or not
     *
     * @return boolean
     */
    public function pointInPolygon(Point $point, bool $pointOnBoundary = true, bool $pointOnVertex = true): bool
    {
        $vertices = $this->getPoints();

        // Check if the point sits exactly on a vertex
        if ($this->pointOnVertex($point)) {
            return $pointOnVertex ? true : false;
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;
        $verticesCount = count($vertices);
        for ($i = 1; $i < $verticesCount; $i++) {
            $vertex1 = $vertices[$i - 1];
            $vertex2 = $vertices[$i];
            if (
                $vertex1->y() == $vertex2->y()
                && $vertex1->y() == $point->y()
                && $point->x() > min($vertex1->x(), $vertex2->x())
                && $point->x() < max($vertex1->x(), $vertex2->x())
            ) {
                // Check if point is on an horizontal polygon boundary
                return $pointOnBoundary ? true : false;
            }
            if (
                $point->y() > min($vertex1->y(), $vertex2->y())
                && $point->y() <= max($vertex1->y(), $vertex2->y())
                && $point->x() <= max($vertex1->x(), $vertex2->x())
                && $vertex1->y() != $vertex2->y()
            ) {
                $xinters =
                        ($point->y() - $vertex1->y()) * ($vertex2->x() - $vertex1->x())
                        / ($vertex2->y() - $vertex1->y())
                        + $vertex1->x();
                if ($xinters == $point->x()) {
                    // Check if point is on the polygon boundary (other than horizontal)
                    return $pointOnBoundary ? true : false;
                }
                if ($vertex1->x() == $vertex2->x() || $point->x() <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is even, then it's in the polygon.
        return $intersections % 2 !== 0;
    }

    /**
     * @param Point $point
     * @return bool
     */
    public function pointOnVertex(Point $point): bool
    {
        foreach ($this->getPoints() as $vertex) {
            if ($point->equals($vertex)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether the given geometry is spatially inside the Polygon
     * TODO: rewrite this. Currently supports point, linestring and polygon with only outer ring
     * @param Geometry $geometry
     * @return bool
     */
    public function contains(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->contains($geometry->getGeos());
            // @codeCoverageIgnoreEnd
        }

        $isInside = false;
        foreach ($geometry->getPoints() as $p) {
            if ($this->pointInPolygon($p)) {
                $isInside = true; // at least one point of the innerPoly is inside the outerPoly
                break;
            }
        }
        if (!$isInside) {
            return false;
        }

        if ($geometry->geometryType() === Geometry::POLYGON) {
            $geometry = $geometry->exteriorRing();
        } elseif ($geometry->geometryType() !== Geometry::LINE_STRING) {
            return false;
        }

        foreach ($geometry->explode(true) as $innerEdge) {
            foreach ($this->exteriorRing()->explode(true) as $outerEdge) {
                if (Geometry::segmentIntersects($innerEdge[0], $innerEdge[1], $outerEdge[0], $outerEdge[1])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return null|array{maxy: float, miny: float, maxx: float, minx: float}
     */
    public function getBBox(): ?array
    {
        return $this->exteriorRing()->getBBox();
    }

    /**
     * The boundary of a simple Surface is the set of closed Curves,
     * corresponding to its “exterior” and “interior” boundaries.

     * @return LineString|MultiLineString
     */
    public function boundary(): ?Geometry
    {
        if ($this->isEmpty()) {
            return new LineString();
        }

        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            return geoPHP::geosToGeometry($this->getGeos()->boundary());
            // @codeCoverageIgnoreEnd
        }

        $rings = $this->getComponents();

        return $this->numInteriorRings() === 0
            ? $rings[0]
            : new MultiLineString($rings);
    }
}
