<?php

namespace geoPHP\Tests\Adapter;

use geoPHP\Adapter\WKT;
use geoPHP\Exception\FileFormatException;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\Polygon;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for EWKT adapter
 *
 * @coversDefaultClass geoPHP\Adapter\EWKT
 * @uses geoPHP\Geometry\Point
 * @uses geoPHP\Geometry\MultiPoint
 * @uses geoPHP\Geometry\LineString
 * @uses geoPHP\Geometry\MultiLineString
 * @uses geoPHP\Geometry\Polygon
 * @uses geoPHP\Geometry\MultiPolygon
 * @uses geoPHP\Geometry\GeometryCollection
 */
class EWKTTest extends TestCase
{
    /**
     * @dataProvider providerReadValidEwkt
     */
    public function testReadingValidEwkt(string $wkt, int $srid): void
    {
        $geometry = (new WKT())->read($wkt);
        $geometry->setGeos(null);

        $this->assertInstanceOf(Geometry::class, $geometry);
        $this->assertEquals($srid, $geometry->getSRID());
    }

    public function providerReadValidEwkt()
    {
        return [
            [
                'SRID=3857;POINT(1 2)',
                3857,
            ],
            [
                'SRID=4326;POINT(19.0 47.0 100)',
                4326,
            ],
        ];
    }
}
