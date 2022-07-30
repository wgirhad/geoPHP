<?php

namespace geoPHP\Adapter;

use geoPHP\Exception\InvalidXmlException;
use geoPHP\Geometry\Collection;
use geoPHP\geoPHP;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Polygon;
use geoPHP\Exception\IOException;
use geoPHP\Geometry\MultiGeometry;

/*
 * Copyright (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/GeoRSS encoder/decoder
 */
class GeoRSS implements GeoAdapter
{
    /**
     * @var \DOMDocument
     */
    protected $xmlObject;

    /**
     * @var string Name-space string. eg "georss:"
     */
    private $nss = '';

    /**
     * Read GeoRSS string into geometry objects.
     *
     * @param string $georss An XML feed containing geoRSS.
     *
     * @return Geometry
     */
    public function read(string $georss): Geometry
    {
        // Change to lower-case, strip all CDATA, and de-namespace
        $georss = strtolower($georss);
        $georss = preg_replace('/<!\[cdata\[(.*?)\]\]>/s', '', $georss);

        // Load into DOMDocument
        $this->xmlObject = new \DOMDocument();
        $loadSuccess = @$this->xmlObject->loadXML($georss);

        if (!$loadSuccess) {
            throw new InvalidXmlException();
        }

        $geometries = array_merge(
            $this->parsePoints(),
            $this->parseLines(),
            $this->parsePolygons(),
            $this->parseBoxes(),
            $this->parseCircles()
        );

        return geoPHP::geometryReduce($geometries);
    }

    /**
     * @param string $string
     * @return Point[]
     */
    protected function getPointsFromCoordinates(string $string): array
    {
        $coordinates = [];
        $latitudeAndLongitude = explode(' ', $string);
        $lat = 0;
        foreach ($latitudeAndLongitude as $key => $item) {
            if (!($key % 2)) {
                // It's a latitude
                $lat = is_numeric($item) ? $item : null;
            } else {
                // It's a longitude
                $lon = is_numeric($item) ? $item : null;
                $coordinates[] = new Point($lon, $lat);
            }
        }
        return $coordinates;
    }

    /**
     * @return Point[]
     */
    protected function parsePoints(): array
    {
        $points = [];
        $pointElements = $this->xmlObject->getElementsByTagName('point');
        foreach ($pointElements as $pt) {
            $pointArray = $this->getPointsFromCoordinates(trim($pt->firstChild->nodeValue));
            $points[] = !empty($pointArray) ? $pointArray[0] : new Point();
        }
        return $points;
    }

    /**
     * @return LineString[]
     */
    protected function parseLines(): array
    {
        $lines = [];
        $lineElements = $this->xmlObject->getElementsByTagName('line');
        foreach ($lineElements as $line) {
            $components = $this->getPointsFromCoordinates(trim($line->firstChild->nodeValue));
            $lines[] = new LineString($components);
        }
        return $lines;
    }

    /**
     * @return Polygon[]
     */
    protected function parsePolygons(): array
    {
        $polygons = [];
        $polygonElements = $this->xmlObject->getElementsByTagName('polygon');
        foreach ($polygonElements as $polygon) {
            /** @noinspection PhpUndefinedMethodInspection */
            if ($polygon->hasChildNodes()) {
                $points = $this->getPointsFromCoordinates(trim($polygon->firstChild->nodeValue));
                $exteriorRing = new LineString($points);
                $polygons[] = new Polygon([$exteriorRing]);
            } else {
                // It's an EMPTY polygon
                $polygons[] = new Polygon();
            }
        }
        return $polygons;
    }

    /**
     * Boxes are rendered into polygons
     *
     * @return Polygon[]
     */
    protected function parseBoxes(): array
    {
        $polygons = [];
        $boxElements = $this->xmlObject->getElementsByTagName('box');
        foreach ($boxElements as $box) {
            $parts = explode(' ', trim($box->firstChild->nodeValue));
            $components = [
                    new Point($parts[3], $parts[2]),
                    new Point($parts[3], $parts[0]),
                    new Point($parts[1], $parts[0]),
                    new Point($parts[1], $parts[2]),
                    new Point($parts[3], $parts[2]),
            ];
            $exteriorRing = new LineString($components);
            $polygons[] = new Polygon([$exteriorRing]);
        }
        return $polygons;
    }

    /**
     * Circles are rendered into points.
     *
     * @@TODO: Add good support once we have circular-string geometry support.
     *
     * @return Point[]
     */
    protected function parseCircles(): array
    {
        $points = [];
        $circleElements = $this->xmlObject->getElementsByTagName('circle');
        foreach ($circleElements as $circle) {
            $parts = explode(' ', trim($circle->firstChild->nodeValue));
            $points[] = new Point($parts[1], $parts[0]);
        }
        return $points;
    }

    /**
     * Serialize geometries into a GeoRSS string.
     *
     * @param Geometry $geometry
     * @param boolean|string $namespace
     * @return string The georss string representation of the input geometries
     */
    public function write(Geometry $geometry, $namespace = false): string
    {
        if ($namespace) {
            $this->nss = $namespace . ':';
        }
        return $this->geometryToGeoRSS($geometry) ?: '';
    }

    /**
     * @param Geometry $geometry
     * @return string|null
     */
    protected function geometryToGeoRSS(Geometry $geometry): ?string
    {
        $type = $geometry->geometryType();
        switch ($type) {
            case Geometry::POINT:
                /** @var Point $geometry */
                return $this->pointToGeoRSS($geometry);
            case Geometry::LINE_STRING:
                /** @var LineString $geometry */
                return $this->linestringToGeoRSS($geometry);
            case Geometry::POLYGON:
                /** @var Polygon $geometry */
                return $this->polygonToGeoRSS($geometry);
            case Geometry::MULTI_POINT:
            case Geometry::MULTI_LINE_STRING:
            case Geometry::MULTI_POLYGON:
            case Geometry::GEOMETRY_COLLECTION:
                /** @var MultiGeometry $geometry */
                return $this->collectionToGeoRSS($geometry);
            default:
                return null;
        }
    }

    /**
     * @param Point $geometry
     * @return string
     */
    private function pointToGeoRSS(Point $geometry): string
    {
        return '<' . $this->nss . 'point>' . $geometry->y() . ' ' . $geometry->x() . '</' . $this->nss . 'point>';
    }

    /**
     * @param LineString $geometry
     * @return string
     */
    private function linestringToGeoRSS(LineString $geometry): string
    {
        $output = '<' . $this->nss . 'line>';
        foreach ($geometry->getComponents() as $k => $point) {
            $output .= $point->y() . ' ' . $point->x();
            if ($k < ($geometry->numGeometries() - 1)) {
                $output .= ' ';
            }
        }
        $output .= '</' . $this->nss . 'line>';
        return $output;
    }

    /**
     * @param Polygon $geometry
     * @return string
     */
    private function polygonToGeoRSS(Polygon $geometry): string
    {
        $output = '<' . $this->nss . 'polygon>';
        $exteriorRing = $geometry->exteriorRing();
        foreach ($exteriorRing->getComponents() as $k => $point) {
            $output .= $point->y() . ' ' . $point->x();
            if ($k < ($exteriorRing->numGeometries() - 1)) {
                $output .= ' ';
            }
        }
        $output .= '</' . $this->nss . 'polygon>';
        return $output;
    }

    /**
     * @param MultiGeometry $geometry
     * @return string
     */
    public function collectionToGeoRSS(MultiGeometry $geometry): string
    {
        $georss = '<' . $this->nss . 'where>';
        $components = $geometry->getComponents();
        foreach ($components as $component) {
            $georss .= $this->geometryToGeoRSS($component);
        }

        $georss .= '</' . $this->nss . 'where>';

        return $georss;
    }
}
