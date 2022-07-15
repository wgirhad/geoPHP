<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace geoPHP;

use geoPHP\Adapter\GeoHash;
use geoPHP\Exception\IOException;
use geoPHP\Geometry\Collection;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;

/**
 * Porides constants and static methods for loading geometries in any supported format.
 */
// @codingStandardsIgnoreLine
class geoPHP
{
    // Earth radius constants in meters

    /** WGS84 semi-major axis (a), aka equatorial radius */
    const EARTH_WGS84_SEMI_MAJOR_AXIS = 6378137.0;
    /** WGS84 semi-minor axis (b), aka polar radius */
    const EARTH_WGS84_SEMI_MINOR_AXIS = 6356752.314245;
    /** WGS84 inverse flattening */
    const EARTH_WGS84_FLATTENING      = 298.257223563;

    /** WGS84 semi-major axis (a), aka equatorial radius */
    const EARTH_GRS80_SEMI_MAJOR_AXIS = 6378137.0;
    /** GRS80 semi-minor axis */
    const EARTH_GRS80_SEMI_MINOR_AXIS = 6356752.314140;
    /** GRS80 inverse flattening */
    const EARTH_GRS80_FLATTENING      = 298.257222100882711;

    /** IUGG mean radius R1 = (2a + b) / 3 */
    const EARTH_MEAN_RADIUS           = 6371008.8;
    /** IUGG R2: Earth's authalic ("equal area") radius is the radius of a hypothetical perfect sphere
     * which has the same surface area as the reference ellipsoid. */
    const EARTH_AUTHALIC_RADIUS       = 6371007.2;

    /**
     * @var array<string, string>
     */
    private static $adapterMap = [
        'wkt'            => 'WKT',
        'ewkt'           => 'EWKT',
        'wkb'            => 'WKB',
        'ewkb'           => 'EWKB',
        'json'           => 'GeoJSON',
        'geojson'        => 'GeoJSON',
        'kml'            => 'KML',
        'gpx'            => 'GPX',
        'georss'         => 'GeoRSS',
        'google_geocode' => 'GoogleGeocode',
        'geohash'        => 'GeoHash',
        'twkb'           => 'TWKB',
        'osm'            => 'OSM',
    ];

    /**
     * @var array<string, string>
     */
    private static $geometryList = [
        'point'              => 'Point',
        'linestring'         => 'LineString',
        'polygon'            => 'Polygon',
        'multipoint'         => 'MultiPoint',
        'multilinestring'    => 'MultiLineString',
        'multipolygon'       => 'MultiPolygon',
        'geometrycollection' => 'GeometryCollection',
    ];

    /**
     * @var bool|null
     */
    private static $geosInstalled;

    /**
     * @return array<string, string> Returns the map of supported adapters and formats
     */
    public static function getAdapterMap(): array
    {
        return self::$adapterMap;
    }


    /**
     * @return array<string, string>
     */
    public static function getGeometryList(): array
    {
        return self::$geometryList;
    }

    /**
     * Converts data to Geometry using geo adapters
     *
     * If $data is an array, all passed in values will be combined into a single geometry
     *
     * @param string|string[]|Geometry $data The data in any supported format, including geoPHP Geometry
     * @param array $args Further arguments will be passed to the geo adapter
     *
     * @throws \Exception|IOException
     *
     * @return Collection|Geometry
     */
    public static function load($data, ...$args): Geometry
    {
        /** @var null|string $type Data type. Tries to detect if omitted */
        $type = count($args) && @array_key_exists($args[0], self::$adapterMap)
            ? strtolower(array_shift($args))
            : null;

        // Auto-detect type if needed
        if (!$type) {
            // If the user is trying to load a Geometry from a Geometry... Just pass it back
            if (is_object($data) && $data instanceof Geometry) {
                return $data;
            }

            $detected = geoPHP::detectFormat($data);
            if (!$detected) {
                throw new \Exception("Can not detect format");
            }
            $format = explode(':', $detected);
            $type = array_shift($format);
            $args = $format ?: $args;
        }

        if (!array_key_exists($type, self::$adapterMap)) {
            throw new \Exception('geoPHP could not find an adapter of type ' . htmlentities($type));
        }
        $adapterType = 'geoPHP\\Adapter\\' . self::$adapterMap[$type];

        $adapter = new $adapterType();

        // Data is not an array, just pass it normally
        if (!is_array($data)) {
            $result = call_user_func_array([$adapter, "read"], array_merge([$data], $args));
        } else { // Data is an array, combine all passed in items into a single geometry
            $geometries = [];
            foreach ($data as $item) {
                $geometries[] = call_user_func_array([$adapter, "read"], array_merge($item, $args));
            }
            $result = geoPHP::buildGeometry($geometries);
        }

        return $result;
    }

    /**
     * Sets and/or returns static geosInstalled property.
     *
     * @param bool $force
     *
     * @return bool
     *
     * @deprecated 2.1 Use instead isGeosInstalled(), enableGeos() or disableGeos().
     */
    public static function geosInstalled(bool $force = null): bool
    {
        geoPHP::$geosInstalled = null;
        if ($force !== null) {
            geoPHP::$geosInstalled = $force;
        }
        if (getenv('GEOS_DISABLED') == 1) {
            geoPHP::$geosInstalled = false;
        }
        if (geoPHP::$geosInstalled !== null) {
            return geoPHP::$geosInstalled;
        }
        geoPHP::$geosInstalled = class_exists('GEOSGeometry', false);

        return geoPHP::$geosInstalled;
    }

    /**
     * Returns if Geos support is installed and enabled.
     *
     * Checks availability of Geos library.
     * Geos support can be forced to disable by setting the environment variable "GEOS_DISABLED = 1".
     *
     * @return boolean
     */
    public static function isGeosInstalled(): bool
    {
        if (getenv('GEOS_DISABLED') === '1') {
            return geoPHP::$geosInstalled = false;
        } elseif (geoPHP::$geosInstalled === null) {
            geoPHP::$geosInstalled = class_exists('GEOSGeometry', false);
        }

        return geoPHP::$geosInstalled;
    }

    /**
     * Attempts to enable Geos support, and returns its status.
     *
     * @return boolean Returns status of Geos support.
     */
    public static function enableGeos(): bool
    {
        geoPHP::$geosInstalled = null;

        return geoPHP::isGeosInstalled();
    }

    /**
     * Disables Geos support.
     *
     * Useful for development.
     *
     * @return void
     */
    public static function disableGeos(): void
    {
        geoPHP::$geosInstalled = false;
    }

    /**
     * @param \GEOSGeometry $geos
     *
     * @throws \Exception
     *
     * @return Geometry|null
     *
     * @codeCoverageIgnore
     */
    public static function geosToGeometry(\GEOSGeometry $geos): ?Geometry
    {
        if (!geoPHP::isGeosInstalled()) {
            return null;
        }
        /** @noinspection PhpUndefinedClassInspection */
        $wkbWriter = new \GEOSWKBWriter();
        /** @noinspection PhpUndefinedMethodInspection */
        $wkb = $wkbWriter->writeHEX($geos);
        $geometry = geoPHP::load($wkb, 'wkb', true);
        if ($geometry) {
            $geometry->setGeos($geos);
            return $geometry;
        }

        return null;
    }

    /**
     * Reduce a geometry, or an array of geometries, into their 'lowest' available common geometry.
     * For example a GeometryCollection of only points will become a MultiPoint
     * A multi-point containing a single point will return a point.
     * An array of geometries can be passed and they will be compiled into a single geometry
     *
     * @param Geometry|Geometry[]|GeometryCollection|GeometryCollection[] $geometries
     * @return Geometry|bool
     */
    public static function geometryReduce($geometries)
    {
        if ($geometries === null) {
            return false;
        }
        /*
         * If it is a single geometry
         */
        if ($geometries instanceof Geometry) {
            // If the geometry cannot even theoretically be reduced more, then pass it back
            $singleGeometries = ['Point', 'LineString', 'Polygon'];
            if (in_array($geometries->geometryType(), $singleGeometries)) {
                return $geometries;
            }

            // If it is a multi-geometry, check to see if it just has one member
            // If it does, then pass the member, if not, then just pass back the geometry
            if (strpos($geometries->geometryType(), 'Multi') === 0) {
                $components = $geometries->getComponents();
                if (count($components) == 1) {
                    return $components[0];
                } else {
                    return $geometries;
                }
            }
        } elseif (is_array($geometries) && count($geometries) == 1) {
            // If it's an array of one, then just parse the one
            return geoPHP::geometryReduce(array_shift($geometries));
        }

        if (!is_array($geometries)) {
            $geometries = [$geometries];
        }
        /**
         * So now we either have an array of geometries
         * @var Geometry[]|GeometryCollection[] $geometries
         */

        $reducedGeometries = [];
        $geometryTypes = [];
        self::explodeCollections($geometries, $reducedGeometries, $geometryTypes);

        $geometryTypes = array_unique($geometryTypes);
        if (empty($geometryTypes)) {
            return false;
        }
        if (count($geometryTypes) == 1) {
            if (count($reducedGeometries) == 1) {
                return $reducedGeometries[0];
            } else {
                $class = 'geoPHP\\Geometry\\' .
                    (strstr($geometryTypes[0], 'Multi') ? '' : 'Multi') .
                    $geometryTypes[0];
                return new $class($reducedGeometries);
            }
        } else {
            return new GeometryCollection($reducedGeometries);
        }
    }

    /**
     * @param Geometry[]|GeometryCollection[] $unreduced
     * @param Geometry[]|GeometryCollection[] $reduced
     * @param array<string>                   $types
     *
     * @return void
     */
    private static function explodeCollections(array $unreduced, array &$reduced, array &$types): void
    {
        foreach ($unreduced as $item) {
            if ($item->geometryType() == 'GeometryCollection' || strpos($item->geometryType(), 'Multi') === 0) {
                self::explodeCollections($item->getComponents(), $reduced, $types);
            } else {
                $reduced[] = $item;
                $types[] = $item->geometryType();
            }
        }
    }

    /**
     * Build an appropriate Geometry, MultiGeometry, or GeometryCollection to contain the Geometries in it.
     *
     * @see geos::geom::GeometryFactory::buildGeometry
     *
     * @param Geometry|Geometry[] $geometries
     *
     * @throws \Exception
     *
     * @return Geometry A Geometry of the "smallest", "most type-specific" class that can contain the elements.
     */
    public static function buildGeometry($geometries): Geometry
    {
        if (empty($geometries)) {
            return new GeometryCollection();
        }

        /* If it is a single geometry */
        if ($geometries instanceof Geometry) {
            return $geometries;
        } elseif (!is_array($geometries)) {
            throw new \InvalidArgumentException('Input of buildGeometry() must be Geometry or array of Geometries');
        } elseif (count($geometries) == 1) {
            // If it's an array of one, then just parse the one
            return geoPHP::buildGeometry(array_shift($geometries));
        }

        /**
         * So now we either have an array of geometries
         * @var Geometry[] $geometries
         */

        $geometryTypes = [];
        $hasData = false;
        foreach ($geometries as $item) {
            if ($item) {
                $geometryTypes[] = $item->geometryType();
                if ($item->getData() !== null) {
                    $hasData = true;
                }
            }
        }
        $geometryTypes = array_unique($geometryTypes);

        if (count($geometryTypes) == 1 && !$hasData) {
            if ($geometryTypes[0] === Geometry::GEOMETRY_COLLECTION) {
                return new GeometryCollection($geometries);
            }
            if (count($geometries) == 1) {
                return $geometries[0];
            } else {
                $newType = (strpos($geometryTypes[0], 'Multi') !== false ? '' : 'Multi') . $geometryTypes[0];
                foreach ($geometries as $geometry) {
                    if ($geometry->isEmpty()) {
                        return new GeometryCollection($geometries);
                    }
                }
                $class = 'geoPHP\\Geometry\\' . $newType;
                return new $class($geometries);
            }
        } else {
            return new GeometryCollection($geometries);
        }
    }

    /**
     * Detects format of the given input.
     *
     * This function is meant to be SPEEDY.
     * It could make a mistake in XML detection if you are mixing or using namespaces in weird ways
     * (ie, KML inside an RSS feed).
     *
     * @param string $input
     *
     * @return string|null Returns the name of input's format (e.g. 'gpx') or null if fails to detect.
     */
    public static function detectFormat(string &$input): ?string
    {
        $mem = fopen('php://memory', 'x+');
        fwrite($mem, $input, 11); // Write 11 bytes - we can detect the vast majority of formats in the first 11 bytes
        fseek($mem, 0);

        $bin = fread($mem, 11);
        $bytes = unpack("c*", $bin);

        // If bytes is empty, then we were passed empty input
        if (empty($bytes)) {
            return null;
        }

        // First char is a tab, space or carriage-return. trim it and try again
        if ($bytes[1] == 9 || $bytes[1] == 10 || $bytes[1] == 32) {
            $input = ltrim($input);
            return geoPHP::detectFormat($input);
        }

        // Detect WKB or EWKB -- first byte is 1 (little endian indicator)
        if ($bytes[1] == 1 || $bytes[1] == 0) {
            $wkbType = current(unpack($bytes[1] == 1 ? 'V' : 'N', substr($bin, 1, 4)));
            if (array_search($wkbType & 0xF, Adapter\WKB::$typeMap)) {
                // If SRID byte is TRUE (1), it's EWKB
                if (($wkbType & Adapter\WKB::SRID_MASK) === Adapter\WKB::SRID_MASK) {
                    return 'ewkb';
                } else {
                    return 'wkb';
                }
            }
        }

        /* Detect HEX encoded WKB or EWKB (PostGIS format)
         * first byte is 48, second byte is 49 (hex '01' => first-byte = 1)
         * The shortest possible WKB string (LINESTRING EMPTY) is 18 hex-chars (9 encoded bytes) long
         * This differentiates it from a geohash, which is always shorter than 13 characters.
         */
        if ($bytes[1] == 48 && ($bytes[2] == 49 || $bytes[2] == 48) && strlen($input) > 12) {
            if (
                (current(unpack($bytes[2] == 49 ? 'V' : 'N', hex2bin(substr($bin, 2, 8)))) & Adapter\WKB::SRID_MASK)
                == Adapter\WKB::SRID_MASK
            ) {
                return 'ewkb:true';
            } else {
                return 'wkb:true';
            }
        }

        // Detect GeoJSON - first char starts with {
        if ($bytes[1] == 123) {
            return 'json';
        }

        // Detect EWKT - strats with "SRID=number;"
        if (substr($input, 0, 5) === 'SRID=') {
            return 'ewkt';
        }

        // Detect WKT - starts with a geometry type name
        if (Adapter\WKT::isWktType(strstr($input, ' ', true))) {
            return 'wkt';
        }

        // Detect XML -- first char is <
        if ($bytes[1] == 60) {
            // grab the first 1024 characters
            $string = substr($input, 0, 1024);
            if (strpos($string, '<kml') !== false) {
                return 'kml';
            }
            if (strpos($string, '<coordinate') !== false) {
                return 'kml';
            }
            if (strpos($string, '<gpx') !== false) {
                return 'gpx';
            }
            if (strpos($string, '<osm ') !== false) {
                return 'osm';
            }
            if (preg_match('/<[a-z]{3,20}>/', $string) !== false) {
                return 'georss';
            }
        }

        // We need an 8 byte string for geohash and unpacked WKB / WKT
        fseek($mem, 0);
        $string = trim(fread($mem, 8));

        // Detect geohash - geohash ONLY contains lowercase chars and numerics
        preg_match('/[' . GeoHash::$characterTable . ']+/', $string, $matches);
        if (isset($matches[0]) && $matches[0] == $string && strlen($input) <= 13) {
            return 'geohash';
        }

        preg_match('/^[a-f0-9]+$/', $string, $matches);
        if (isset($matches[0])) {
            return 'twkb:true';
        } else {
            return 'twkb';
        }
    }
}
