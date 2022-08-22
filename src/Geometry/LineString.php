<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\geoPHP;

use function abs;
use function atan;
use function atan2;
use function cos;
use function count;
use function deg2rad;
use function is_nan;
use function sin;
use function sqrt;
use function tan;

use const M_PI;
use const PHP_INT_MAX;

/**
 * A LineString is defined by a sequence of points, (X,Y) pairs, which define the reference points of the line string.
 * Linear interpolation between the reference points defines the resulting linestring.
 *
 * @phpstan-consistent-constructor
 */
class LineString extends Curve
{
    /**
     * Checks and stores geometry components.
     *
     * @param Point[] $points Array of at least two Points with which to build the LineString.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(array $points = [])
    {
        parent::__construct($points);
    }

    /**
     *
     * Creates a LineString from array of coordinates
     *
     * @param array{}|array<array<float|int|null>> $coordinateArray Multi-dimensional array of coordinates.
     *
     * @throws InvalidGeometryException
     *
     * @return LineString
     */
    public static function fromArray(array $coordinateArray): LineString
    {
        $points = [];
        foreach ($coordinateArray as $point) {
            $points[] = Point::fromArray($point);
        }
        return new static($points);
    }

    public function geometryType(): string
    {
        return Geometry::LINE_STRING;
    }

    /**
     * Returns the number of points of the LineString
     *
     * @return int
     */
    public function numPoints(): int
    {
        return count($this->components);
    }

    /**
     * Returns the 1-based Nth point of the LineString.
     * Negative values are counted backwards from the end of the LineString.
     *
     * @param int $n Nth point of the LineString
     * @return Point|null
     */
    public function pointN(int $n): ?Point
    {
        return $n >= 0
                ? $this->geometryN($n)
                : $this->geometryN(count($this->components) - abs($n + 1));
    }

    public function centroid(): Point
    {
        return $this->getCentroidAndLength();
    }

    public function getCentroidAndLength(float &$length = 0.0): Point
    {
        if ($this->isEmpty()) {
            return new Point();
        }

        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            //return geoPHP::geosToGeometry($this->getGeos()->centroid());
            // @codeCoverageIgnoreEnd
        }

        $x = 0;
        $y = 0;
        $length = 0.0;
        /** @var Point|null $previousPoint */
        $previousPoint = null;
        foreach ($this->getPoints() as $point) {
            if ($previousPoint) {
                // Equivalent to $previousPoint->distance($point) but much faster
                $segmentLength = sqrt(
                    ($previousPoint->x() - $point->x()) ** 2 +
                    ($previousPoint->y() - $point->y()) ** 2
                );
                $length += $segmentLength;
                $x += ($previousPoint->x() + $point->x()) / 2 * $segmentLength;
                $y += ($previousPoint->y() + $point->y()) / 2 * $segmentLength;
            }
            $previousPoint = $point;
        }
        if ($length === 0.0) {
            return $this->startPoint();
        }
        return new Point($x / $length, $y / $length);
    }

    /**
     *  Returns the length of this Curve in its associated spatial reference.
     * Eg. if Geometry is in geographical coordinate system it returns the length in degrees
     * @return float
     */
    public function length(): float
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->length();
            // @codeCoverageIgnoreEnd
        }
        $length = 0.0;
        /** @var Point|null $previousPoint */
        $previousPoint = null;
        foreach ($this->getComponents() as $point) {
            if ($previousPoint) {
                $length += sqrt(
                    ($previousPoint->x() - $point->x()) ** 2 +
                    ($previousPoint->y() - $point->y()) ** 2
                );
            }
            $previousPoint = $point;
        }
        return $length;
    }

    public function length3D(): float
    {
        $length = 0.0;
        /** @var Point|null $previousPoint */
        $previousPoint = null;
        foreach ($this->getComponents() as $point) {
            if ($previousPoint) {
                $length += sqrt(
                    ($previousPoint->x() - $point->x()) ** 2 +
                    ($previousPoint->y() - $point->y()) ** 2 +
                    ($previousPoint->z() - $point->z()) ** 2
                );
            }
            $previousPoint = $point;
        }
        return $length;
    }

    /**
     * @param float $radius Earth radius
     * @return float Length in meters
     */
    public function greatCircleLength(float $radius = geoPHP::EARTH_WGS84_SEMI_MAJOR_AXIS): float
    {
        $length = 0.0;
        $rad = M_PI / 180;
        $points = $this->getPoints();
        $numPoints = $this->numPoints() - 1;
        for ($i = 0; $i < $numPoints; ++$i) {
            // Simplified Vincenty formula with equal major and minor axes (a sphere)
            $lat1 = $points[$i]->y() * $rad;
            $lat2 = $points[$i + 1]->y() * $rad;
            $lon1 = $points[$i]->x() * $rad;
            $lon2 = $points[$i + 1]->x() * $rad;
            $deltaLon = $lon2 - $lon1;
            $cosLat1 = cos($lat1);
            $cosLat2 = cos($lat2);
            $sinLat1 = sin($lat1);
            $sinLat2 = sin($lat2);
            $cosDeltaLon = cos($deltaLon);
            $d =
                    $radius *
                    atan2(
                        sqrt(
                            ($cosLat2 * sin($deltaLon)) ** 2 +
                            ($cosLat1 * $sinLat2 - $sinLat1 * $cosLat2 * $cosDeltaLon) ** 2
                        ),
                        $sinLat1 * $sinLat2 +
                        $cosLat1 * $cosLat2 * $cosDeltaLon
                    );
            if ($points[$i]->is3D()) {
                $d = sqrt(
                    $d ** 2 +
                    ($points[$i + 1]->z() - $points[$i]->z()) ** 2
                );
            }

            $length += $d;
        }
        // Returns length in meters.
        return $length;
    }

    /**
     * @return float Haversine length of geometry in degrees
     */
    public function haversineLength(): float
    {
        $distance = 0.0;
        $points = $this->getPoints();
        $numPoints = $this->numPoints() - 1;
        for ($i = 0; $i < $numPoints; ++$i) {
            $point = $points[$i];
            $nextPoint = $points[$i + 1];
            $degree = (geoPHP::EARTH_WGS84_SEMI_MAJOR_AXIS *
                    acos(
                        sin(deg2rad($point->y())) * sin(deg2rad($nextPoint->y())) +
                            cos(deg2rad($point->y())) * cos(deg2rad($nextPoint->y())) *
                            cos(deg2rad(abs($point->x() - $nextPoint->x())))
                    )
            );
            if (!is_nan($degree)) {
                $distance += $degree;
            }
        }
        return $distance;
    }

    /**
     * @source https://github.com/mjaschen/phpgeo/blob/master/src/Location/Distance/Vincenty.php
     * @author Marcus Jaschen <mjaschen@gmail.com>
     * @license https://opensource.org/licenses/GPL-3.0 GPL
     * (note: geoPHP uses "GPL version 2 (or later)" license which is compatible with GPLv3)
     *
     * @return float Length in meters
     */
    public function vincentyLength(): float
    {
        $length = 0.0;
        $rad = M_PI / 180;
        $points = $this->getPoints();
        $numPoints = $this->numPoints() - 1;
        for ($i = 0; $i < $numPoints; ++$i) {
            // Inverse Vincenty formula
            $lat1 = $points[$i]->y() * $rad;
            $lat2 = $points[$i + 1]->y() * $rad;
            $lng1 = $points[$i]->x() * $rad;
            $lng2 = $points[$i + 1]->x() * $rad;

            $semiMajor = geoPHP::EARTH_WGS84_SEMI_MAJOR_AXIS;
            $semiMinor = geoPHP::EARTH_WGS84_SEMI_MINOR_AXIS;
            $f = 1 / geoPHP::EARTH_WGS84_FLATTENING;
            $deltaL  = $lng2 - $lng1;
            $u1 = atan((1 - $f) * tan($lat1));
            $u2 = atan((1 - $f) * tan($lat2));
            $iterationLimit = 100;
            $lambda         = $deltaL;
            $sinU1 = sin($u1);
            $sinU2 = sin($u2);
            $cosU1 = cos($u1);
            $cosU2 = cos($u2);
            do {
                $sinLambda = sin($lambda);
                $cosLambda = cos($lambda);
                $sinSigma = sqrt(
                    ($cosU2 * $sinLambda) *
                    ($cosU2 * $sinLambda) +
                    ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda) *
                    ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda)
                );
                if ($sinSigma == 0) {
                    return 0.0;
                }
                $cosSigma = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
                $sigma = atan2($sinSigma, $cosSigma);
                $sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
                $cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
                $cos2SigmaM = 0;
                if ($cosSqAlpha <> 0) {
                    $cos2SigmaM = $cosSigma - 2 * $sinU1 * $sinU2 / $cosSqAlpha;
                }
                $c = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
                $lambdaP = $lambda;
                $lambda = $deltaL + (1 - $c) * $f * $sinAlpha *
                    ($sigma + $c * $sinSigma * ($cos2SigmaM + $c * $cosSigma * (- 1 + 2 * $cos2SigmaM * $cos2SigmaM)));
            } while (abs($lambda - $lambdaP) > 1e-12 && --$iterationLimit > 0);
            if ($iterationLimit == 0) {
                throw new \RuntimeException('Vincenty distance calculation not converging.');
            }
            $uSq        = $cosSqAlpha
                            * ($semiMajor * $semiMajor - $semiMinor * $semiMinor)
                            / ($semiMinor * $semiMinor);
            $a          = 1 + $uSq / 16384 * (4096 + $uSq * (- 768 + $uSq * (320 - 175 * $uSq)));
            $b          = $uSq / 1024 * (256 + $uSq * (- 128 + $uSq * (74 - 47 * $uSq)));
            $deltaSigma = $b * $sinSigma * ($cos2SigmaM + $b / 4 *
                    ($cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) - $b / 6
                        * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma)
                        * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)));

            $length += $semiMinor * $a * ($sigma - $deltaSigma);
        }
        // Returns length in meters.
        return $length;
    }

    public function minimumZ(): ?float
    {
        if (!$this->is3D()) {
            return null;
        }
        $min = PHP_INT_MAX;
        foreach ($this->getPoints() as $point) {
            if ($point->z() < $min) {
                $min = $point->z();
            }
        }
        return $min < PHP_INT_MAX ? $min : null;
    }

    public function maximumZ(): ?float
    {
        if (!$this->is3D()) {
            return null;
        }
        $max = ~PHP_INT_MAX;
        foreach ($this->getPoints() as $point) {
            if ($point->z() > $max) {
                $max = $point->z();
            }
        }

        return $max > ~PHP_INT_MAX ? $max : null;
    }

    public function zDifference(): ?float
    {
        if (!$this->is3D()) {
            return null;
        }
        return abs($this->startPoint()->z() - $this->endPoint()->z());
    }

    /**
     * Returns the cumulative elevation gain of the LineString
     *
     * @param float $verticalTolerance Smoothing factor filtering noisy elevation data.
     *      Its unit equals to the z-coordinates unit (meters for geographical coordinates)
     *      If the elevation data comes from a DEM, a value around 3.5 can be acceptable.
     *
     * @return float
     */
    public function elevationGain(float $verticalTolerance = 0.0): float
    {
        if (!$this->is3D()) {
            return 0.0;
        }
        $gain = 0.0;
        $lastEle = $this->startPoint()->z();
        $pointCount = $this->numPoints();
        foreach ($this->getPoints() as $i => $point) {
            if (abs($point->z() - $lastEle) > $verticalTolerance || $i === $pointCount - 1) {
                if ($point->z() > $lastEle) {
                    $gain += $point->z() - $lastEle;
                }
                $lastEle = $point->z();
            }
        }
        return $gain;
    }

    /**
     * Returns the cumulative elevation loss of the LineString
     *
     * @param float $verticalTolerance Smoothing factor filtering noisy elevation data.
     *      Its unit equals to the z-coordinates unit (meters for geographical coordinates)
     *      If the elevation data comes from a DEM, a value around 3.5 can be acceptable.
     *
     * @return float
     */
    public function elevationLoss(float $verticalTolerance = 0.0): float
    {
        if (!$this->is3D()) {
            return 0.0;
        }
        $loss = 0.0;
        $lastEle = $this->startPoint()->z();
        $pointCount = $this->numPoints();
        foreach ($this->getPoints() as $i => $point) {
            if (abs($point->z() - $lastEle) > $verticalTolerance || $i === $pointCount - 1) {
                if ($point->z() < $lastEle) {
                    $loss += $lastEle - $point->z();
                }
                $lastEle = $point->z();
            }
        }
        return $loss;
    }

    public function minimumM(): ?float
    {
        if (!$this->isMeasured()) {
            return null;
        }
        $min = PHP_INT_MAX;
        foreach ($this->getPoints() as $point) {
            if ($point->m() < $min) {
                $min = $point->m();
            }
        }
        return $min < PHP_INT_MAX ? $min : null;
    }

    public function maximumM(): ?float
    {
        if (!$this->isMeasured()) {
            return null;
        }
        $max = ~PHP_INT_MAX;
        foreach ($this->getPoints() as $point) {
            if ($point->m() > $max) {
                $max = $point->m();
            }
        }

        return $max > ~PHP_INT_MAX ? $max : null;
    }

    /**
     * Get all line segments. By default returns segments as array of LineStrings of two points.
     *
     * @param bool $toArray Return segments as arrays of Point pairs.
     *
     * @return LineString[]|array<array{Point, Point}>
     */
    public function explode(bool $toArray = false): ?array
    {
        $points = $this->getPoints();
        $numPoints = count($points);
        if ($numPoints < 2) {
            return [];
        }
        $parts = [];
        for ($i = 1; $i < $numPoints; ++$i) {
            $segment = [$points[$i - 1], $points[$i]];
            $parts[] = $toArray ? $segment : new LineString($segment);
        }
        return $parts;
    }

    /**
     * Checks that LineString is a Simple Geometry.
     *
     * A Curve is simple if it does not pass through the same Point twice
     * with the possible exception of the two end points.
     *
     * WARNING: Current implementation has known problems with self tangency.
     *
     * @return boolean
     */
    public function isSimple(): ?bool
    {
        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->isSimple();
            // @codeCoverageIgnoreEnd
        }

        // As of OGC specification a ring is simple only if its start and end points equals in all coordinates
        // Neither GEOS, nor PostGIS support it
//        if ($this->is3D()
//                && $this->startPoint()->equals($this->endPoint())
//                && $this->startPoint()->z() !== $this->endPoint()->z()
//        ) {
//            return false;
//        }

        $segments = $this->explode(true);
        foreach ($segments as $i => $segment) {
            foreach ($segments as $j => $checkSegment) {
                if (
                    $i != $j
                    && Geometry::segmentIntersects($segment[0], $segment[1], $checkSegment[0], $checkSegment[1])
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param LineString $segment
     * @return bool
     */
    public function lineSegmentIntersect(LineString $segment): bool
    {
        return Geometry::segmentIntersects(
            $this->startPoint(),
            $this->endPoint(),
            $segment->startPoint(),
            $segment->endPoint()
        );
    }

    /**
     * @param Geometry|Collection $geometry
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

        if ($geometry->geometryType() === Geometry::POINT) {
            // This is defined in the Point class nicely
            return $geometry->distance($this);
        }
        if ($geometry->geometryType() === Geometry::LINE_STRING) {
            $distance = null;
            $geometrySegments = $geometry->explode();
            foreach ($this->explode() as $seg1) {
                /** @var LineString $seg2 */
                foreach ($geometrySegments as $seg2) {
                    if ($seg1->lineSegmentIntersect($seg2)) {
                        return 0.0;
                    }
                    // Because line-segments are straight, the shortest distance will be at one of the endpoints.
                    // If they are parallel endpoint calculation is still accurate.
                    $checkDistance1 = $seg1->startPoint()->distance($seg2);
                    $checkDistance2 = $seg1->endPoint()->distance($seg2);
                    $checkDistance3 = $seg2->startPoint()->distance($seg1);
                    $checkDistance4 = $seg2->endPoint()->distance($seg1);

                    $checkDistance = min($checkDistance1, $checkDistance2, $checkDistance3, $checkDistance4);
                    if ($checkDistance === 0.0) {
                        return 0.0;
                    }
                    if ($distance === null || $checkDistance < $distance) {
                        $distance = $checkDistance;
                    }
                }
            }
            return $distance;
        } else {
            // It can be treated as collection
            return parent::distance($geometry);
        }
    }
}
