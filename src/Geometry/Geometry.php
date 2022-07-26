<?php

namespace geoPHP\Geometry;

use geoPHP\Exception\UnsupportedMethodException;
use geoPHP\geoPHP;
use GEOSGeometry;

/**
 * Geometry is the root class of the hierarchy. Geometry is an abstract (non-instantiable) class.
 *
 * OGC 06-103r4 6.1.2 specification:
 * The instantiable subclasses of Geometry defined in this Standard are restricted to
 * 0, 1 and 2-dimensional geometric objects that exist in 2, 3 or 4-dimensional coordinate space.
 *
 * Geometry values in R^2 have points with coordinate values for x and y.
 * Geometry values in R^3 have points with coordinate values for x, y and z or for x, y and m.
 * Geometry values in R^4 have points with coordinate values for x, y, z and m.
 * The interpretation of the coordinates is subject to the coordinate reference systems associated to the point.
 * All coordinates within a geometry object should be in the same coordinate reference systems.
 * Each coordinate shall be unambiguously associated to a coordinate reference system
 * either directly or through its containing geometry.
 *
 * The z coordinate of a point is typically, but not necessarily, represents altitude or elevation.
 * The m coordinate represents a measurement.
 */
abstract class Geometry
{
    // ----------------------------------------------- //
    //                 Type constants                  //
    // ----------------------------------------------- //
    const POINT = 'Point';
    const LINE_STRING = 'LineString';
    const POLYGON = 'Polygon';
    const MULTI_POINT = 'MultiPoint';
    const MULTI_LINE_STRING = 'MultiLineString';
    const MULTI_POLYGON = 'MultiPolygon';
    const GEOMETRY_COLLECTION = 'GeometryCollection';

    const CIRCULAR_STRING = 'CircularString';
    const COMPOUND_CURVE = 'CompoundCurve';
    const CURVE_POLYGON = 'CurvePolygon';
    const MULTI_CURVE = 'MultiCurve'; // Abstract
    const MULTI_SURFACE = 'MultiSurface'; // Abstract
    const CURVE = 'Curve'; // Abstract
    const SURFACE = 'Surface'; // Abstract
    const POLYHEDRAL_SURFACE = 'PolyhedralSurface';
    const TIN = 'TIN';
    const TRIANGLE = 'Triangle';

    /** @var int|null $srid Spatial Reference System Identifier (http://en.wikipedia.org/wiki/SRID) */
    protected $srid = null;

    /**
     * @var mixed|null Custom (meta)data
     */
    protected $data;

    /**
     * @var \GEOSGeometry|null
     */
    private $geos = null;

    // ----------------------------------------------- //
    //       Basic methods on geometric objects        //
    // ----------------------------------------------- //

    /**
     * Returns the name of the instantiable subtype of Geometry of which the geometric object is an instantiable member.
     *
     * @return string
     */
    abstract public function geometryType(): string;

    /**
     * The inherent dimension of the geometric object, which must be less than or equal to the coordinate dimension.
     * In non-homogeneous collections, this will return the largest topological dimension of the contained objects.
     *
     * @return int
     */
    abstract public function dimension(): int;

    /**
     * Returns true if the geometric object is the empty Geometry.
     * If true, then the geometric object represents the empty point set ∅ for the coordinate space.
     *
     * @return bool
     */
    abstract public function isEmpty(): bool;

    /**
     * Returns true if the geometric object has no anomalous geometric points,
     * such as self intersection or self tangency.
     *
     * The description of each instantiable geometric class will include the specific conditions
     * that cause an instance of that class to be classified as not simple
     *
     * @return bool|null
     */
    abstract public function isSimple(): ?bool;

    /**
     * Returns the closure of the combinatorial boundary of the geometric object
     *
     * @return Geometry|null
     */
    abstract public function boundary(): ?Geometry;

    /**
     * @return Geometry[]
     */
    abstract public function getComponents(): array;


    // ----------------------------------------------- //
    //  Methods applicable on certain geometry types   //
    // ----------------------------------------------- //

    /**
     * @return float
     */
    abstract public function area(): float;

    /**
     * @return Point
     */
    abstract public function centroid(): Point;

    /**
     * @return float
     */
    abstract public function length(): float;

    abstract public function length3D(): float;

    /**
     * @return float|null
     */
    abstract public function x(): ?float;

    /**
     * @return float|null
     */
    abstract public function y(): ?float;

    /**
     * @return float|null
     */
    abstract public function z(): ?float;

    /**
     * @return float|null
     */
    abstract public function m(): ?float;

    /**
     * @return int|null
     */
    abstract public function numGeometries(): ?int;

    /**
     * @param int $n One-based index.
     * @return Geometry|null The geometry, or null if not found.
     */
    abstract public function geometryN(int $n): ?Geometry;

    /**
     * @return Point|null
     */
    abstract public function startPoint(): ?Point;

    /**
     * @return Point|null
     */
    abstract public function endPoint(): ?Point;

    abstract public function isRing(): ?bool;

    abstract public function isClosed(): ?bool;

    abstract public function numPoints(): int;

    /**
     * @param int $n Nth point
     * @return Point|null
     */
    abstract public function pointN(int $n): ?Point;

    abstract public function exteriorRing(): ?LineString;

    abstract public function numInteriorRings(): ?int;

    abstract public function interiorRingN(int $n): ?LineString;

    abstract public function distance(Geometry $geom): ?float;

    abstract public function equals(Geometry $geom): bool;


    // ----------------------------------------------- //
    //          Abstract Non-Standard Methods          //
    // ----------------------------------------------- //

    abstract public function is3D(): bool;

    abstract public function isMeasured(): bool;

    abstract public function getBBox(): ?array;

    abstract public function asArray(): array;

    /**
     * @return Point[]
     */
    abstract public function getPoints(): array;

    abstract public function invertXY();

    /**
     * Get all line segments.
     * @param bool $toArray Return segments as LineString or array of start and end points. Explode(true) is faster.
     * @return array|null Returns line segments or null for 0-deminsional geometries.
     */
    abstract public function explode(bool $toArray = false): ?array;

    abstract public function greatCircleLength(float $radius = geoPHP::EARTH_WGS84_SEMI_MAJOR_AXIS): float; //meters

    abstract public function haversineLength(): float; //degrees

    abstract public function flatten(): void; // 3D to 2D

    // Elevations statistics

    abstract public function minimumZ(): ?float;

    abstract public function maximumZ(): ?float;

    abstract public function minimumM(): ?float;

    abstract public function maximumM(): ?float;

    abstract public function zDifference(): ?float;

    abstract public function elevationGain(float $verticalTolerance = 0.0): ?float;

    abstract public function elevationLoss(float $verticalTolerance = 0.0): ?float;


    // ----------------------------------------------- //
    //        Standard – Common to all geometries      //
    // ----------------------------------------------- //

    public function getSRID(): ?int
    {
        return $this->srid;
    }

    /**
     * @param int|null $srid Spatial Reference System Identifier
     */
    public function setSRID(?int $srid): void
    {
        if ($this->getGeos() && $srid !== null) {
            // @codeCoverageIgnoreStart
            $this->getGeos()->setSRID($srid);
            // @codeCoverageIgnoreEnd
        }
        $this->srid = $srid;
    }

    /**
     * Adds custom data to the geometry
     *
     * @param string|array $property The name of the data or an associative array.
     * @param mixed|null $value The data. Can be of any type (string, integer, array, etc.).
     */
    public function setData($property, $value = null): void
    {
        if (is_array($property)) {
            $this->data = $property;
        } else {
            $this->data[$property] = $value;
        }
    }

    /**
     * Returns the requested data by property name, or all data of the geometry.
     *
     * @param string|null $property The name of the data. If omitted, all data will be returned.
     *
     * @return mixed|null The data or null if not exists.
     */
    public function getData(string $property = null)
    {
        if ($property) {
            return $this->hasDataProperty($property) ? $this->data[$property] : null;
        } else {
            return $this->data;
        }
    }

    /**
     * Tells whether the geometry has data with the specified name.
     *
     * @param string $property The name of the property.
     *
     * @return bool True if the geometry has data with the specified name.
     */
    public function hasDataProperty(string $property): bool
    {
        return array_key_exists($property, $this->data ?: []);
    }

    public function envelope(): Geometry
    {
        if ($this->isEmpty()) {
            $type = 'geoPHP\\Geometry\\' . $this->geometryType();
            return new $type();
        }
        if ($this->geometryType() === Geometry::POINT) {
            return $this;
        }

        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->envelope());
            // @codeCoverageIgnoreEnd
        }

        $boundingBox = $this->getBBox();
        $points = [
            new Point($boundingBox['maxx'], $boundingBox['miny']),
            new Point($boundingBox['maxx'], $boundingBox['maxy']),
            new Point($boundingBox['minx'], $boundingBox['maxy']),
            new Point($boundingBox['minx'], $boundingBox['miny']),
            new Point($boundingBox['maxx'], $boundingBox['miny']),
        ];
        return $points
            ? new Polygon([new LineString($points)])
            : new Polygon();
    }

    // ----------------------------------------------- //
    // Non-Standard methods – Common to all geometries //
    // ----------------------------------------------- //

    /**
     * Converts the geometry to file using an adapter.
     *
     * @param string $format A file format or adapter name. E.g.: "GPX", or "GeoJSON".
     * @param mixed ...$args Additional adapter specific parameters.
     *
     * @return string
     */
    public function out(string $format, ...$args): string
    {
        $format = strtolower($format);
        if (strstr($format, 'xdr')) {   //Big Endian WKB
            $args[] = true;
            $format = str_replace('xdr', '', $format);
        }

        $processorType = 'geoPHP\\Adapter\\' . geoPHP::getAdapterMap()[$format];
        $processor = new $processorType();
        array_unshift($args, $this);

        return call_user_func_array([$processor, 'write'], $args);
    }

    public function __toString(): string
    {
        return $this->out('ewkt');
    }

    public function asText(): string
    {
        return (string) $this;
    }

    public function asBinary(): string
    {
        return $this->out('wkb');
    }

    public function coordinateDimension(): int
    {
        return 2 + ($this->z() ? 1 : 0) + ($this->isMeasured() ? 1 : 0);
    }

    /**
     * Utility function to check if any line segments intersect
     * Derived from:
     * @source http://stackoverflow.com/questions/563198/how-do-you-detect-where-two-line-segments-intersect
     *
     * @param Point $segment1Start
     * @param Point $segment1End
     * @param Point $segment2Start
     * @param Point $segment2End
     * @return bool
     */
    public static function segmentIntersects(
        Point $segment1Start,
        Point $segment1End,
        Point $segment2Start,
        Point $segment2End
    ): bool {
        $p0x = $segment1Start->x();
        $p0y = $segment1Start->y();
        $p1x = $segment1End->x();
        $p1y = $segment1End->y();
        $p2x = $segment2Start->x();
        $p2y = $segment2Start->y();
        $p3x = $segment2End->x();
        $p3y = $segment2End->y();

        $s1x = $p1x - $p0x;
        $s1y = $p1y - $p0y;
        $s2x = $p3x - $p2x;
        $s2y = $p3y - $p2y;

        $fps = (-$s2x * $s1y) + ($s1x * $s2y);
        $fpt = (-$s2x * $s1y) + ($s1x * $s2y);

        if ($fps == 0 || $fpt == 0) {
            return false;
        }

        $s = (-$s1y * ($p0x - $p2x) + $s1x * ($p0y - $p2y)) / $fps;
        $t = ($s2x * ($p0y - $p2y) - $s2y * ($p0x - $p2x)) / $fpt;

        // Return true if collision is detected
        return ($s > 0 && $s < 1 && $t > 0 && $t < 1);
    }

    // ----------------------------------------------- //
    //                     Aliases                     //
    // ----------------------------------------------- //

    /**
     * @deprecated 2.1
     */
    public function hasZ(): bool
    {
        return $this->is3D();
    }
    /**
     * @deprecated 2.1
     */
    public function getX(): ?float
    {
        return $this->x();
    }
    /**
     * @deprecated 2.1
     */
    public function getY(): ?float
    {
        return $this->y();
    }
    /**
     * @deprecated 2.1
     */
    public function getZ(): ?float
    {
        return $this->z();
    }
    /**
     * @deprecated 2.1
     */
    public function getM(): ?float
    {
        return $this->m();
    }
    /**
     * @deprecated 2.1
     */
    public function getBoundingBox(): ?array
    {
        return $this->getBBox();
    }
    /**
     * @deprecated 2.1
     */
    public function dump(): array
    {
        return $this->getComponents();
    }
    /**
     * @deprecated 2.1
     */
    public function getCentroid(): ?Point
    {
        return $this->centroid();
    }
    /**
     * @deprecated 2.1
     */
    public function getArea(): ?float
    {
        return $this->area();
    }
    /**
     * @deprecated 2.1
     */
    public function geos(): ?GEOSGeometry
    {
        return $this->getGeos();
    }
    /**
     * @deprecated 2.1
     */
    public function getGeomType(): string
    {
        return $this->geometryType();
    }
    /**
     * @deprecated 2.1
     */
    public function SRID(): ?int
    {
        return $this->getSRID();
    }

    // ----------------------------------------------- //
    //               GEOS Only Functions               //
    // ----------------------------------------------- //

    /**
     * Returns the GEOS representation of Geometry if GEOS is installed.
     *
     * GEOS supports SRID and Z-coordinate (3D), but lacks support of M-coordinate.
     *
     * @return \GEOSGeometry|null
     * @codeCoverageIgnore
     */
    public function getGeos(): ?\GEOSGeometry
    {
        // If it's already been set, just return it
        if ($this->geos && geoPHP::isGeosInstalled()) {
            return $this->geos;
        }
        // It hasn't been set yet, generate it
        if (geoPHP::isGeosInstalled()) {
            /** @noinspection PhpUndefinedClassInspection */
            $reader = new \GEOSWKBReader();
            /** @noinspection PhpUndefinedMethodInspection */
            $this->geos = $reader->read($this->out('ewkb'));
        } else {
            $this->geos = null;
        }
        return $this->geos;
    }

    public function setGeos(?\GEOSGeometry $geos): void
    {
        $this->geos = $geos;
    }

    /**
     * @return Geometry|Point
     *
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function pointOnSurface(): Geometry
    {
        if ($this->isEmpty()) {
            return new Point();
        }
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->pointOnSurface());
        }
        // help for implementation: http://gis.stackexchange.com/questions/76498/how-is-st-pointonsurface-calculated
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function equalsExact(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->equalsExact($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @param string $pattern
     * @return string|bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function relate(Geometry $geometry, string $pattern = null)
    {
        if ($this->getGeos()) {
            if ($pattern) {
                /** @noinspection PhpUndefinedMethodInspection */
                return $this->getGeos()->relate($geometry->getGeos(), $pattern);
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                return $this->getGeos()->relate($geometry->getGeos());
            }
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @return array
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function checkValidity(): array
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->checkValidity();
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param float $distance
     * @return Geometry
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function buffer(float $distance): Geometry
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->buffer($distance));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return Geometry
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function intersection(Geometry $geometry): Geometry
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->intersection($geometry->getGeos()));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @return Geometry
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function convexHull(): Geometry
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->convexHull());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return Geometry
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function difference(Geometry $geometry): Geometry
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->difference($geometry->getGeos()));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return Geometry
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function symDifference(Geometry $geometry): Geometry
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->symDifference($geometry->getGeos()));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry|Geometry[] $geometry
     * @return Geometry
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function union($geometry): Geometry
    {
        if ($this->getGeos()) {
            if (is_array($geometry)) {
                $geom = $this->getGeos();
                foreach ($geometry as $item) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $geom = $geom->union($item->getGeos());
                }
                return geoPHP::geosToGeometry($geom);
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                return geoPHP::geosToGeometry($this->getGeos()->union($geometry->getGeos()));
            }
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param float      $tolerance
     * @param bool|false $preserveTopology
     * @return Geometry
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function simplify(float $tolerance, bool $preserveTopology = false): Geometry
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return geoPHP::geosToGeometry($this->getGeos()->simplify($tolerance, $preserveTopology));
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @return Geometry|null
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function makeValid()
    {
        if ($this->getGeos()) {
            /** @phpstan-ignore-next-line */
            return geoPHP::geosToGeometry($this->getGeos()->makeValid());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @return Geometry|null
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function buildArea()
    {
        if ($this->getGeos()) {
            /** @phpstan-ignore-next-line */
            return geoPHP::geosToGeometry($this->getGeos()->buildArea());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function disjoint(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->disjoint($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function touches(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->touches($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function intersects(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->intersects($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function crosses(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->crosses($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function within(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->within($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function contains(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->contains($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function overlaps(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->overlaps($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function covers(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->covers($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return bool
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function coveredBy(Geometry $geometry): bool
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->coveredBy($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * @param Geometry $geometry
     * @return float
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function hausdorffDistance(Geometry $geometry): float
    {
        if ($this->getGeos()) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getGeos()->hausdorffDistance($geometry->getGeos());
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }

    /**
     * Returns the distance from the origin of the geometry (LineString or MultiLineString)
     * to the point projected on the geometry (that is to a point of the line the closest to the given point).
     *
     * @param Geometry $point
     * @param bool     $normalized Return the distance as a percentage between 0 (origin) and 1 (endpoint).
     *
     * @return float
     * @throws UnsupportedMethodException
     * @codeCoverageIgnore
     */
    public function project(Geometry $point, bool $normalized = false): float
    {
        if ($this->getGeos()) {
            /** @phpstan-ignore-next-line */
            return $this->getGeos()->project($point->getGeos(), $normalized);
        }
        throw UnsupportedMethodException::geos(__METHOD__);
    }
}
