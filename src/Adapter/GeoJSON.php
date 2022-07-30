<?php

namespace geoPHP\Adapter;

use geoPHP\Exception\IOException;
use geoPHP\geoPHP;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiLineString;
use geoPHP\Geometry\Polygon;
use geoPHP\Geometry\MultiPolygon;
use stdClass;

/**
 * GeoJSON class : a geoJSON reader/writer.
 *
 * Note that it will always return a GeoJSON geometry. This
 * means that if you pass it a feature, it will return the
 * geometry of that feature strip everything else.
 */
class GeoJSON implements GeoAdapter
{
    /**
     * Given an object or a string, return a Geometry
     *
     * @param string $input The GeoJSON string or object
     * @return Geometry
     * @throws IOException
     */
    public function read(string $input): Geometry
    {
        $input = json_decode($input);

        if (!is_object($input)) {
            throw new IOException('Malformed JSON');
        }
        if (!isset($input->type) || !is_string($input->type)) {
            throw new IOException('Invalid GeoJSON');
        }

        return $this->processGeoJSON($input);
    }

    /**
     * @param stdClass $input
     * @return Geometry
     * @throws IOException
     */
    public function processGeoJSON(stdClass $input): Geometry
    {
        // Check to see if it's a FeatureCollection
        if ($input->type == 'FeatureCollection' && isset($input->features)) {
            $geometries = [];
            foreach ($input->features as $feature) {
                $geometries[] = $this->processGeoJSON($feature);
            }
            return geoPHP::buildGeometry($geometries);
        }

        // Check to see if it's a Feature
        if ($input->type == 'Feature') {
            return $this->geoJSONFeatureToGeometry($input);
        }

        // It's a geometry - process it
        return $this->geoJSONObjectToGeometry($input);
    }

    /**
     * @param stdClass $input
     * @return int|null
     */
    private function getSRID(stdClass $input): ?int
    {
        if (isset($input->crs->properties->name)) {
            // parse CRS codes in forms "EPSG:1234" and "urn:ogc:def:crs:EPSG::1234"
            preg_match('#EPSG[:]+(\d+)#', $input->crs->properties->name, $m);
            return isset($m[1]) ? (int) $m[1] : null;
        }
        return null;
    }

    /**
     * @param stdClass $obj
     * @return Geometry
     * @throws IOException
     */
    private function geoJSONFeatureToGeometry(stdClass $obj): Geometry
    {
        $geometry = $this->processGeoJSON($obj->geometry);
        if (isset($obj->properties)) {
            foreach ($obj->properties as $property => $value) {
                $geometry->setData($property, $value);
            }
        }

        return $geometry;
    }

    /**
     * @param stdClass $obj
     * @return Geometry
     * @throws \Exception
     */
    private function geoJSONObjectToGeometry(stdClass $obj): Geometry
    {
        $type = $obj->type;

        if ($type == 'GeometryCollection') {
            return $this->geoJSONObjectToGeometryCollection($obj);
        }
        $method = 'arrayTo' . $type;
        /** @var GeometryCollection $geometry */
        $geometry = $this->$method($obj->coordinates);
        $geometry->setSRID($this->getSRID($obj));
        return $geometry;
    }

    /**
     * @param array<float|int> $coordinates Array of coordinates
     * @return Point
     */
    private function arrayToPoint(array $coordinates): Point
    {
        switch (count($coordinates)) {
            case 2:
                return new Point($coordinates[0], $coordinates[1]);
            case 3:
                return new Point($coordinates[0], $coordinates[1], $coordinates[2]);
            case 4:
                return new Point($coordinates[0], $coordinates[1], $coordinates[2], $coordinates[3]);
            default:
                return new Point();
        }
    }

    /**
     * @param array<?array<float|int>> $components
     * @return LineString
     */
    private function arrayToLineString(array $components): LineString
    {
        $points = [];
        foreach ($components as $componentArray) {
            $points[] = $this->arrayToPoint($componentArray);
        }
        return new LineString($points);
    }

    /**
     * @param array<?array<array<float|int>>> $components
     * @return Polygon
     */
    private function arrayToPolygon(array $components): Polygon
    {
        $lines = [];
        foreach ($components as $componentArray) {
            $lines[] = $this->arrayToLineString($componentArray);
        }
        return new Polygon($lines);
    }

    /**
     * @param array<?array<float|int|null>> $components
     * @return MultiPoint
     */
    private function arrayToMultiPoint(array $components): MultiPoint
    {
        $points = [];
        foreach ($components as $componentArray) {
            $points[] = $this->arrayToPoint($componentArray);
        }
        return new MultiPoint($points);
    }

    /**
     * @param array<?array<array<float|int|null>>> $components
     * @return MultiLineString
     */
    private function arrayToMultiLineString(array $components): MultiLineString
    {
        $lines = [];
        foreach ($components as $componentArray) {
            $lines[] = $this->arrayToLineString($componentArray);
        }
        return new MultiLineString($lines);
    }

    /**
     * @param array<?array<array<array<float|int|null>>>> $components
     * @return MultiPolygon
     */
    private function arrayToMultiPolygon(array $components): MultiPolygon
    {
        $polygons = [];
        foreach ($components as $componentArray) {
            $polygons[] = $this->arrayToPolygon($componentArray);
        }
        return new MultiPolygon($polygons);
    }

    /**
     * @param stdClass $obj
     * @throws IOException
     * @return GeometryCollection
     */
    private function geoJSONObjectToGeometryCollection(stdClass $obj): Geometry
    {
        $geometries = [];
        if (!property_exists($obj, 'geometries')) {
            throw new IOException('Invalid GeoJSON: GeometryCollection without geometry components');
        }
        foreach ($obj->geometries ?: [] as $componentObject) {
            $geometries[] = $this->geoJSONObjectToGeometry($componentObject);
        }
        $collection = new GeometryCollection($geometries);
        $collection->setSRID($this->getSRID($obj));
        return $collection;
    }

    /**
     * Serializes an object into a geojson string
     *
     *
     * @param Geometry $geometry The object to serialize
     *
     * @return string The GeoJSON string
     */
    public function write(Geometry $geometry): string
    {
        return json_encode($this->geometryToGeoJsonArray($geometry));
    }



    /**
     * Creates a geoJSON array.
     *
     * If the root geometry is a GeometryCollection, and any of its geometries has data,
     * the root element will be a FeatureCollection with Feature elements (with the data).
     * If the root geometry has data, it will be included in a Feature object that contains the data.
     *
     * The geometry should have geographical coordinates since CRS support has been removed from geoJSON
     * specification (RFC 7946).
     * The geometry should'nt be measured, since geoJSON specification (RFC 7946) only supports the dimensional
     * positions.
     *
     * @param Geometry $geometry
     * @param bool|null $isRoot Is geometry the root geometry?
     * @return array{type: string, geometries: array<mixed>
     *          }|array{type: string, geometry: array<mixed>, properties: array<mixed>
     *          }|array{type: string, features: array<mixed>
     *          }|array{type: string, coordinates: array<float>}
     */
    public function geometryToGeoJsonArray(Geometry $geometry, ?bool $isRoot = true): array
    {
        if ($geometry->geometryType() === Geometry::GEOMETRY_COLLECTION) {
            $components = [];
            $isFeatureCollection = false;
            foreach ($geometry->getComponents() as $component) {
                if ($component->getData() !== null) {
                    $isFeatureCollection = true;
                }
                $components[] = $this->geometryToGeoJsonArray($component, false);
            }
            if (!$isFeatureCollection || !$isRoot) {
                return [
                        'type'       => 'GeometryCollection',
                        'geometries' => $components
                ];
            } else {
                $features = [];
                foreach ($geometry->getComponents() as $i => $component) {
                    $features[] = [
                            'type'       => 'Feature',
                            'properties' => $component->getData(),
                            'geometry'   => $components[$i],
                    ];
                }
                return [
                        'type'     => 'FeatureCollection',
                        'features' => $features
                ];
            }
        }

        if ($isRoot && $geometry->getData() !== null) {
            return [
                    'type'       => 'Feature',
                    'properties' => $geometry->getData(),
                    'geometry'   => [
                            'type'        => $geometry->geometryType(),
                            'coordinates' => $geometry->isEmpty() ? [] : $geometry->asArray()
                    ]
            ];
        }
        $object = [
                'type'        => $geometry->geometryType(),
                'coordinates' => $geometry->isEmpty() ? [] : $geometry->asArray()
        ];
        return $object;
    }
}
