<?php

namespace geoPHP\Adapter;

use geoPHP\Exception\FileFormatException;
use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Exception\IOException;
use geoPHP\geoPHP;
use geoPHP\Geometry\{
    Collection,
    Geometry,
    GeometryCollection,
    Point,
    MultiPoint,
    LineString,
    MultiLineString,
    Polygon,
    MultiPolygon
};

/**
 * WKT (Well Known Text) Adapter
 */
class WKT implements GeoAdapter
{
    /**
     * @var bool|null
     */
    protected $hasZ;

    /**
     * @var bool|null
     */
    protected $hasM;

    /**
     * Determines if the given typeString is a valid WKT geometry type
     *
     * @param string $typeString Type to find, eg. "Point", or "LineStringZ"
     *
     * @return string|null The geometry type if found or null
     */
    public static function getWktType(string $typeString): ?string
    {
        foreach (geoPHP::getGeometryList() as $geom => $type) {
            if (strtolower((substr($typeString, 0, strlen($geom)))) === $geom) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Read WKT string into geometry objects
     *
     * @param string $wkt A WKT string
     *
     * @throws FileFormatException
     *
     * @return Geometry
     */
    public function read(string $wkt): Geometry
    {
        $this->hasZ = null;
        $this->hasM = null;

        $wkt = trim(strtoupper($wkt));
        $srid = null;
        // If the input starts with "SRID=" then read Spatial Reference ID
        if (preg_match('#^SRID=(\d+);#', $wkt, $m)) {
            $srid = intval($m[1]);
            $wkt = substr($wkt, strlen($m[0]));
        }

        // If geos is installed, then we take a shortcut and let it parse the WKT
        if (geoPHP::isGeosInstalled()) {
            // @codeCoverageIgnoreStart
            $reader = new \GEOSWKTReader();
            $geom = geoPHP::geosToGeometry($reader->read($wkt));
            if ($srid) {
                $geom->setSRID($srid);
            }
            return $geom;
            // @codeCoverageIgnoreEnd
        }

        $geometry = $this->parseTypeAndGetData($wkt);

        if ($srid) {
            $geometry->setSRID($srid);
        }
        return $geometry;
    }

    /**
     * @param string $wkt The WKT input string
     *
     * @throws FileFormatException
     *
     * @return Geometry
     */
    protected function parseTypeAndGetData($wkt): Geometry
    {
        if (
            preg_match(
                '#^(?<type>[A-Z]+?)\s*(?<z>Z*)(?<m>M*)\s*(?:\((?<data>.+)\)|(?<data_empty>EMPTY))$#',
                $wkt,
                $matches
            )
        ) {
            $geometryType = $this->getWktType($matches['type']);
            if (!$geometryType) {
                throw new FileFormatException('Invalid WKT type "' . $matches[1] . '."');
            }

            if ($this->hasZ === null && ($matches['z'] === 'Z' || $matches['m'] === 'M')) {
                $this->hasZ = $matches['z'] === 'Z';
            }
            if ($this->hasM === null && ($matches['z'] === 'Z' || $matches['m'] === 'M')) {
                $this->hasM = $matches['m'] === 'M';
            }

            $dataString = $matches['data'] ?: $matches['data_empty'];

            $method = 'parse' . $geometryType;
            try {
                return self::$method($dataString);
            } catch (InvalidGeometryException $eInvalidGeom) {  // @phpstan-ignore-line
                throw new FileFormatException("Invalid WKT {$matches['type']}", $dataString, 0, $eInvalidGeom);
            }
        }
        throw new FileFormatException('Cannot parse WKT.');
    }

    /**
     * Parses a coordinate sequence and returns a Point.
     *
     * @param string $dataString
     *
     * @throws FileFormatException Throwed if cannot parse the Point data.
     *
     * @return Point
     */
    protected function parseCoordinates(string $dataString): Point
    {
        list($x, $y, $coord3, $coord4) = array_merge(
            explode(' ', (string) preg_replace('#\s+#', ' ', trim($dataString))),
            [null, null, null, null]
        );

        /**
         * Set extended dimensions for "old style" WKT, that has no dimension marked.
         * For example POINT (1 2 3).
         */
        if ($this->hasZ === null && $this->hasM === null) {
            $this->hasZ = isset($coord3);
            $this->hasM = isset($coord4);
        }

        $z = $m = null;
        if (isset($coord3)) {
            if ($this->hasZ) {
                $z = $coord3;
            } elseif ($this->hasM) {
                $m = $coord3;
            } else {
                // Maybe later we can implement a stricter mode wich forbids extra coordinates.
                // throw new FileFormatException(
                //     'Coordinate dimenstion mismatch. Geometry is not 3D but got Z coordinate.'
                // );
            }
        }
        if (isset($coord4)) {
            if ($this->hasM && $this->hasZ) {
                $m = $coord4;
            } else {
                // throw new FileFormatException(
                //     'Coordinate dimenstion mismatch. Geometry is not measured but got M coordinate.'
                // );
            }
        }

        if ($this->hasZ && $z === null || !$this->hasZ && $z !== null) {
            throw new FileFormatException(
                'Coordinate dimenstion mismatch. Geometry is 3D but no Z coordinate.',
                $dataString
            );
        }
        if ($this->hasM && $m === null || !$this->hasM && $m !== null) {
            throw new FileFormatException(
                'Coordinate dimenstion mismatch. Geometry is measured but no M coordinate.',
                $dataString
            );
        }

        return new Point($x, $y, $z, $m);
    }

    /**
     * Parses a WKT POINT and returns a Point geometry.
     *
     * @param string $dataString
     *
     * @throws FileFormatException Throwed if cannot parse the POINT text.
     *
     * @return Point
     */
    protected function parsePoint(string $dataString): Point
    {
        $dataString = trim($dataString);
        if ($dataString === 'EMPTY') {
            return new Point();
        }

        return $this->parseCoordinates($dataString);
    }

    /**
     * Parses coordinate components of a WKT LINESTRING and returns a LineString geometry.
     *
     * @param string $dataString
     *
     * @throws FileFormatException Throwed if cannot parse the LINESTRING text.
     *
     * @return LineString
     */
    protected function parseLineString(string $dataString): LineString
    {
        if ($dataString === 'EMPTY') {
            return new LineString();
        }

        $points = [];
        foreach (explode(',', $dataString) as $part) {
            $points[] = $this->parseCoordinates($part);
        }

        return new LineString($points);
    }

    /**
     * Parses a WKT POLYGON and returns a Polygon geometry.
     *
     * Example WKTs:
     * empty: POLYGON EMPTY
     * one ring: POLYGON ((1 2, 3 4, 5 6, 1 2))
     * two rings: POLYGON ((1 2, 3 4, 5 6, 1 2), (11 12, 13 14, 15 16, 11 12))
     *
     * @param string $dataString
     *
     * @throws FileFormatException Throwed if cannot parse the POLYGON text.
     *
     * @return Polygon
     */
    protected function parsePolygon(string $dataString): Polygon
    {
        if ($dataString === 'EMPTY') {
            return new Polygon();
        }

        $rings = [];
        if (preg_match_all('#\((?<ring>.*?)\)#', $dataString, $matches)) {
            foreach ($matches['ring'] as $part) {
                $rings[] = $this->parseLineString($part);
            }
            return new Polygon($rings);
        } else {
            throw new FileFormatException('Cannot parse WKT POLYGON.', $dataString);
        }
    }

    /**
     * Parses a WKT MULTIPOINT and returns a MultiPoint geometry.
     *
     * Should understand both forms:
     * OGC style:  MULTIPOINT ((1 2), (3 4))
     * GEOS style: MULTIPOINT (1 2, 3 4)
     *
     * @param string $dataString
     *
     * @throws FileFormatException Throwed if cannot parse the MULTIPOINT text.
     *
     * @return MultiPoint
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    protected function parseMultiPoint(string $dataString): MultiPoint
    {
        if ($dataString === 'EMPTY') {
            return new MultiPoint();
        }

        $points = [];
        $hasDoubleBraces = null;
        foreach (explode(',', $dataString) as $part) {
            if (trim($part) === 'EMPTY') {
                $points[] = new Point();
            } else {
                // At the first ireation determines if WKT uses "double braces" form.
                if ($hasDoubleBraces === null) {
                    $hasDoubleBraces = preg_match('#^\(.+\)$#', trim($part));
                }
                // Removes dobule braces. If one of the components uses single brace form, rejects the whole MultiPoint.
                if ($hasDoubleBraces) {
                    preg_match('#^\((.+)\)$#', trim($part), $matches);
                    $part = $matches[1] ?? null;
                }
                if ($part) {
                    $points[] =  $this->parsePoint($part);
                } else {
                    $points = [];
                    break;
                }
            }
        }

        if ($points) {
            return new MultiPoint($points);
        } else {
            throw new FileFormatException('Cannot parse WKT MULTIPOINT.', $dataString);
        }
    }

    /**
     * @param string $dataString
     *
     * @throws FileFormatException Throwed if cannot parse the MULTILINESTRING text.
     *
     * @return MultiLineString
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    protected function parseMultiLineString(string $dataString): MultiLineString
    {
        if ($dataString === 'EMPTY') {
            return new MultiLineString();
        }

        $lines = [];
        if (preg_match_all('#(?<component>\([^)]*?\)|EMPTY)#', $dataString, $matches)) {
            foreach ($matches['component'] as $component) {
                // Removes outer braces if any
                preg_match('#^\((.+)\)$#', $component, $matches2);
                $lines[] = $this->parseLineString($matches2[1] ?? $component);
            }
        }

        if ($lines) {
            return new MultiLineString($lines);
        } else {
            throw new FileFormatException('Cannot parse WKT MULTILINESTRING.', $dataString);
        }
    }

    /**
     * @param string $dataString
     *
     * @throws FileFormatException Throwed if cannot parse the MULTIPOLYGON text.
     *
     * @return MultiPolygon
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    protected function parseMultiPolygon(string $dataString): MultiPolygon
    {
        if ($dataString === 'EMPTY') {
            return new MultiPolygon();
        }

        $polygons = [];
        if (preg_match_all('#(?<component>\((?>[^()]+|(?R))*\)|EMPTY)#', $dataString, $matches)) {
            foreach ($matches['component'] as $component) {
                // Removes outer braces if any
                preg_match('#^\((.+)\)$#', $component, $matches2);
                $polygons[] = $this->parsePolygon($matches2[1] ?? $component);
            }
        }

        return new MultiPolygon($polygons);
    }

    /**
     * Parses a WKT GEOMETRYCOLLECTION and returns a GeometryCollection geometry.
     *
     * Example WKTs:
     * GEOMETRYCOLLECTION EMPTY
     * GEOMETRYCOLLECTION (POINT(1 2))
     * GEOMETRYCOLLECTION (POINT(1 2), LINESTRING(1 2, 3 4))
     *
     * @param string $dataString
     *
     * @throws FileFormatException Throwed if cannot parse the GEOMETRYCOLLECTION text.
     *
     * @return GeometryCollection
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    protected function parseGeometryCollection(string $dataString): GeometryCollection
    {
        if ($dataString === 'EMPTY') {
            return new GeometryCollection();
        }

        $geometries = [];
        $offset = 0;
        while ($offset < strlen($dataString)) {
            // Matches the first balanced parenthesis group (or term EMPTY)
            preg_match(
                '#[A-Z\s]+#',
                $dataString,
                $typeMatches,
                0,
                $offset
            );
            $type = $typeMatches[0] ?? '';
            $offset += strlen($type);
            preg_match(
                '#\((?>[^()]+|(?R))*\)|EMPTY#',
                $dataString,
                $dataMatches,
                0,
                $offset
            );
            $data = $dataMatches[0] ?? '';
            $offset += strlen($data) + 1;

            $geometries[] = $this->parseTypeAndGetData(trim(($type . $data)));
        }

        return new GeometryCollection($geometries);
    }


    /**
     * Serialize geometries into a WKT string.
     *
     * @param Geometry $geometry
     *
     * @throws IOException Throwed if the given Geometry is not supported by the WKT writer.
     *
     * @return string The WKT string representation of the input geometries.
     */
    public function write(Geometry $geometry): string
    {
        // If geos is installed, then we take a shortcut and let it write the WKT
        if (geoPHP::isGeosInstalled()) {
            // @codeCoverageIgnoreStart
            $writer = new \GEOSWKTWriter();
            $writer->setRoundingPrecision(14);
            $writer->setTrim(true);

            return $writer->write($geometry->getGeos());
            // @codeCoverageIgnoreEnd
        }
        $this->hasM = $geometry->isMeasured();
        $this->hasZ = $geometry->is3D();

        if ($geometry->isEmpty()) {
            return strtoupper($geometry->geometryType()) . ' EMPTY';
        }

        $data = $this->extractData($geometry);
        $extension = '';
        if ($this->hasZ) {
            $extension .= 'Z';
        }
        if ($this->hasM) {
            $extension .= 'M';
        }
        return strtoupper($geometry->geometryType()) . ($extension ? ' ' . $extension : '') . ' (' . $data . ')';
    }

    /**
     * Extract geometry to a WKT string
     *
     * @param Geometry|Collection $geometry A Geometry object.
     *
     * @throws IOException Throwed if the given Geometry is not supported by the WKT writer.
     *
     * @return string The WKT text.
     */
    protected function extractData(Geometry $geometry): string
    {
        switch ($geometry->geometryType()) {
            case Geometry::POINT:
                return $this->writePoint($geometry);
            case Geometry::LINE_STRING:
                return $this->writeLineString($geometry);
            case Geometry::POLYGON:
            case Geometry::MULTI_POINT:
            case Geometry::MULTI_LINE_STRING:
            case Geometry::MULTI_POLYGON:
                return $this->writeMulti($geometry);
            case Geometry::GEOMETRY_COLLECTION:
                return $this->writeGeometryCollection($geometry);
            default:
                // @codeCoverageIgnoreStart
                throw new IOException('WKT writer does not support ' . $geometry->geometryType() . ' geometry type.');
                // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param Geometry $geometry
     *
     * @return string
     */
    protected function writePoint(Geometry $geometry): string
    {
        $pointText = $geometry->x() . ' ' . $geometry->y();

        if ($this->hasZ) {
            $pointText .= ' ' . ($geometry->z() ?: 0);
        }
        if ($this->hasM) {
            $pointText .= ' ' . ($geometry->m() ?: 0);
        }

        return trim($pointText);
    }

    /**
     * @param Geometry $geometry
     *
     * @return string
     */
    protected function writeLineString(Geometry $geometry): string
    {
        $parts = [];
        foreach ($geometry->getComponents() as $component) {
            $parts[] = $this->extractData($component);
        }

        return implode(', ', $parts);
    }

    /**
     * @param Geometry $geometry
     *
     * @return string
     */
    protected function writeMulti(Geometry $geometry): string
    {
        $parts = [];
        foreach ($geometry->getComponents() as $component) {
            if ($component->isEmpty()) {
                $parts[] = 'EMPTY';
            } else {
                $parts[] = '(' . $this->extractData($component) . ')';
            }
        }

        return implode(', ', $parts);
    }

    /**
     * @param Geometry $geometry
     *
     * @return string
     */
    protected function writeGeometryCollection(Geometry $geometry): string
    {
        $parts = [];
        foreach ($geometry->getComponents() as $component) {
            $extension = '';
            if ($this->hasZ) {
                $extension .= 'Z';
            }
            if ($this->hasM) {
                $extension .= 'M';
            }
            $data = $this->extractData($component);
            $parts[] = strtoupper($component->geometryType())
                    . ($extension ? ' ' . $extension : '')
                    . ($data ? ' (' . $data . ')' : ' EMPTY');
        }

        return implode(', ', $parts);
    }
}
