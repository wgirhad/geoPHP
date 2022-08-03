<?php

namespace geoPHP\Tests\Unit\Adapter;

use geoPHP\Adapter\EWKT;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for EWKT adapter
 *
 * @coversDefaultClass geoPHP\Adapter\EWKT
 *
 * @uses geoPHP\Geometry\Point
 * @uses geoPHP\Geometry\MultiPoint
 * @uses geoPHP\Geometry\LineString
 * @uses geoPHP\Geometry\MultiLineString
 * @uses geoPHP\Geometry\Polygon
 * @uses geoPHP\Geometry\MultiPolygon
 * @uses geoPHP\Geometry\GeometryCollection
 * @uses geoPHP\Geometry\Collection
 * @uses geoPHP\Geometry\Curve
 * @uses geoPHP\Geometry\Surface
 * @uses geoPHP\Geometry\MultiGeometry
 * @uses geoPHP\Geometry\MultiCurve
 * @uses geoPHP\Geometry\MultiSurface
 * @uses geoPHP\Exception\FileFormatException
 */
class EWKTTest extends TestCase
{
    /**
     * @dataProvider providerReadValidEwkt
     *
     * @covers ::read
     */
    public function testReadingValidEwkt(string $wkt, int $srid): void
    {
        $geometry = (new EWKT())->read($wkt);

        $this->assertInstanceOf(Geometry::class, $geometry);
        $this->assertEquals($srid, $geometry->getSRID());
    }

    /**
     * @return array<array{string, int}>
     */
    public function providerReadValidEwkt(): array
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

    /**
     * @dataProvider providerWriteValidEwkt
     *
     * @covers ::write
     */
    public function testWritingValidEwkt(string $expectedWkt, Geometry $geometry, ?int $srid): void
    {
        $geometry->setSRID($srid);

        $wkt = (new EWKT())->write($geometry);

        $this->assertEquals($expectedWkt, $wkt);
    }

    /**
     * @return array{array{string, Geometry, int}}
     */
    public function providerWriteValidEwkt()
    {
        return [
            [
                'SRID=3857;POINT (1 2)',
                new Point(1, 2),
                3857,
            ],
            [
                'SRID=4326;POINT Z (19.1 47.1 100)',
                new Point(19.1, 47.1, 100),
                4326,
            ],
            [
                'POINT (1 2)',
                new Point(1, 2),
                null,
            ],
            [
                'SRID=23700;POINT EMPTY',
                new Point(),
                23700,
            ],
        ];
    }
}
