<?php

namespace geoPHP\Tests\Unit\Adapter;

use geoPHP\Adapter\WKT;
use geoPHP\Exception\FileFormatException;
use geoPHP\Geometry\{
    Geometry,
    GeometryCollection,
    Point,
    MultiPoint,
    LineString,
    MultiLineString,
    Polygon,
    MultiPolygon
};
use PHPUnit\Framework\TestCase;

/**
 * Test cases for reading capabilities of WKT adapter
 *
 * @coversDefaultClass geoPHP\Adapter\WKT
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
class WKTReaderTest extends TestCase
{
    /**
     * @var WKT
     */
    private static $wktAdapter;

    /**
     * Instantiate WKT adapter
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        self::$wktAdapter = new WKT();
    }

    /**
     * @dataProvider providerValidPoint
     * @dataProvider providerValidLineString
     * @dataProvider providerValidPolygon
     * @dataProvider providerValidMultiPoint
     * @dataProvider providerValidMultiLineString
     * @dataProvider providerValidMultiPolygon
     * @dataProvider providerValidGeometryCollection
     *
     * @covers ::read
     * @covers ::getWktType
     * @covers ::parseTypeAndGetData
     * @covers ::parseCoordinates
     * @covers ::parsePoint
     * @covers ::parseLineString
     * @covers ::parsePolygon
     * @covers ::parseMultiPoint
     * @covers ::parseMultiLineString
     * @covers ::parseMultiPolygon
     * @covers ::parseGeometryCollection
     */
    public function testValidWkt(string $wkt, Geometry $expectedGeometry): void
    {
        $geometry = self::$wktAdapter->read($wkt);
        $this->assertEquals($expectedGeometry, $geometry);
    }

    /**
     * @dataProvider providerInvalidWkt
     * @dataProvider providerInvalidPoint
     * @dataProvider providerInvalidLineString
     * @dataProvider providerInvalidPolygon
     * @dataProvider providerInvalidMultiPoint
     * @dataProvider providerInvalidMultiLineString
     * @dataProvider providerInvalidMultiPolygon
     * @dataProvider providerInvalidGeometryCollection
     *
     * @covers ::read
     * @covers ::getWktType
     * @covers ::parseTypeAndGetData
     * @covers ::parseCoordinates
     * @covers ::parsePoint
     * @covers ::parseLineString
     * @covers ::parsePolygon
     * @covers ::parseMultiPoint
     * @covers ::parseMultiLineString
     * @covers ::parseMultiPolygon
     * @covers ::parseGeometryCollection
     */
    public function testInvalidWktThrowsException(string $wkt): void
    {
        $this->expectException(FileFormatException::class);

        print_r(self::$wktAdapter->read($wkt));
    }

    /**
     * @return array<string, array<string>>
     */
    public function providerInvalidWkt()
    {
        return [
            'empty string' => [''],
            'non wkt string' => ['lorem ipsum'],
            'invalid geometry name' => ['FOO (1 2)'],
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerValidPoint()
    {
        return [
            [
                'POINT EMPTY',
                new Point(),
            ],
            [
                'POINT(1 2)',
                new Point(1, 2),
            ],
            [
                'POINT ( 1 2 )',
                new Point(1, 2),
            ],
            [
                'POINT ( 1    2 )',
                new Point(1, 2),
            ],
            [
                'POINT (1.0123456789 2.0123456789)',
                new Point(1.0123456789, 2.0123456789),
            ],
            [
                'POINT Z (1 2 3)',
                new Point(1, 2, 3),
            ],
            [
                'POINTZ(1 2 3)',
                new Point(1, 2, 3),
            ],
            [
                'POINT (1 2 3)',
                new Point(1, 2, 3),
            ],
            [
                'POINT M (1 2 3)',
                new Point(1, 2, null, 3),
            ],
            [
                'POINTM(1 2 3)',
                new Point(1, 2, null, 3),
            ],
            [
                'POINT (1 2 3 4)',
                new Point(1, 2, 3, 4),
            ],
            [
                'POINT ZM (1 2 3 4)',
                new Point(1, 2, 3, 4),
            ],
            [
                'POINTZM (1 2 3 4)',
                new Point(1, 2, 3, 4),
            ],

            // Mismatched coordinate dimension but we are following Geos's tolerant reader.
            [
                'POINT Z (1 2 3 4)',
                new Point(1, 2, 3),
            ],
            [
                'POINT M (1 2 3 4)',
                new Point(1, 2, null, 3),
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public function providerInvalidPoint()
    {
        return [
            [
                'POINT',
            ],
            [
                'POINT ()',
            ],
            [
                'POINT foo',
            ],
            [
                'POINT (1)',
            ],
            [
                'POINT (1 2',
            ],
            [
                'POINT (a b)',
            ],
            [
                'POINT (null null)',
            ],
            [
                'POINT (1 2 z)',
            ],

            // Coordinate dimension mismatch
            [
                'POINT Z (1 2)',
            ],
            [
                'POINT M (1 2)',
            ],
            [
                'POINT ZM (1 2)',
            ],
            [
                'POINT ZM (1 2 3)',
            ],
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerValidLineString()
    {
        return [
            [
                'LINESTRING EMPTY',
                new LineString(),
            ],
            [
                'LINESTRING(1 2,5 6)',
                LineString::fromArray([[1, 2], [5, 6]]),
            ],
            [
                'LINESTRING ( 1 2, 5 6 )',
                LineString::fromArray([[1, 2], [5, 6]]),
            ],
            [
                'LINESTRING (1 2, 5 6, 10 11, 15 16)',
                LineString::fromArray([[1, 2], [5, 6], [10, 11], [15, 16]]),
            ],
            [
                'LINESTRING (1.0123456789 2.0123456789, 5.987654321 6.987654321)',
                LineString::fromArray([[1.0123456789, 2.0123456789], [5.987654321, 6.987654321]]),
            ],
            [
                'LINESTRING Z (1 2 3, 5 6 7)',
                LineString::fromArray([[1, 2, 3], [5, 6, 7]]),
            ],
            [
                'LINESTRINGZ(1 2 3,5 6 7)',
                LineString::fromArray([[1, 2, 3], [5, 6, 7]]),
            ],
            [
                'LINESTRING (1 2 3, 5 6 7)',
                LineString::fromArray([[1, 2, 3], [5, 6, 7]]),
            ],
            [
                'LINESTRING M (1 2 3, 5 6 7)',
                LineString::fromArray([[1, 2, null, 3], [5, 6, null, 7]]),
            ],
            [
                'LINESTRINGM(1 2 3,5 6 7)',
                LineString::fromArray([[1, 2, null, 3], [5, 6, null, 7]]),
            ],
            [
                'LINESTRING ZM (1 2 3 4, 5 6 7 8)',
                LineString::fromArray([[1, 2, 3, 4], [5, 6, 7, 8]]),
            ],
            [
                'LINESTRINGZM(1 2 3 4,5 6 7 8)',
                LineString::fromArray([[1, 2, 3, 4], [5, 6, 7, 8]]),
            ],
            [
                'LINESTRING (1 2 3 4, 5 6 7 8)',
                LineString::fromArray([[1, 2, 3, 4], [5, 6, 7, 8]]),
            ],

            // Mismatched coordinate dimension but we are following Geos's tolerant reader.
            [
                'LINESTRING Z (1 2 3 4, 5 6 7 8)',
                LineString::fromArray([[1, 2, 3], [5, 6, 7]]),
            ],
            [
                'LINESTRING M (1 2 3 4, 5 6 7 8)',
                LineString::fromArray([[1, 2, null, 3], [5, 6, null, 7]]),
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public function providerInvalidLineString()
    {
        return [
            [
                'LINESTRING',
            ],
            [
                'LINESTRING ()',
            ],
            [
                'LINESTRING (1)',
            ],
            [
                'LINESTRING (1 2)',
            ],
            [
                'LINESTRING (a b, c d)',
            ],
            [
                'LINESTRING (null null, null null)',
            ],
            [
                'LINESTRING (1 2 z, 5 6 z)',
            ],

            // Coordinate dimension mismatch
            [
                'LINESTRING Z (1 2, 5 6)',
            ],
            [
                'LINESTRING M (1 2, 5 6)',
            ],
            [
                'LINESTRING ZM (1 2, 5 6)',
            ],
            [
                'LINESTRING ZM (1 2 3, 5 6 7)',
            ],
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerValidPolygon()
    {
        return [
            [
                'POLYGON EMPTY',
                new Polygon(),
            ],
            [
                'POLYGON((11 12,21 22,31 32,11 12))',
                Polygon::fromArray([[[11, 12], [21, 22], [31, 32], [11, 12]]]),
            ],
            [
                'POLYGON ( ( 11 12, 21 22, 31 32, 11 12 ) )',
                Polygon::fromArray([[[11, 12], [21, 22], [31, 32], [11, 12]]]),
            ],
            [
                'POLYGON ((11 12, 21 22, 31 32, 11 12), (111 112, 121 122, 131 132, 111 112))',
                Polygon::fromArray(
                    [
                        [[11, 12], [21, 22], [31, 32], [11, 12]],
                        [[111, 112], [121, 122], [131, 132], [111, 112]]
                    ]
                ),
            ],
            [
                'POLYGONZ((11 12 13,21 22 23,31 32 33,11 12 43))',
                Polygon::fromArray([[[11, 12, 13], [21, 22, 23], [31, 32, 33], [11, 12, 43]]]),
            ],
            [
                'POLYGON Z ( ( 11 12 13, 21 22 23, 31 32 33, 11 12 43 ) )',
                Polygon::fromArray([[[11, 12, 13], [21, 22, 23], [31, 32, 33], [11, 12, 43]]]),
            ],
            [
                'POLYGON ((11 12 13, 21 22 23, 31 32 33, 11 12 43), ' .
                '(111 112 113, 121 122 123, 131 132 133, 111 112 143))',
                Polygon::fromArray(
                    [
                        [[11, 12, 13], [21, 22, 23], [31, 32, 33], [11, 12, 43]],
                        [[111, 112, 113], [121, 122, 123], [131, 132, 133], [111, 112, 143]]
                    ]
                ),
            ],
            [
                'POLYGONM((11 12 13,21 22 23,31 32 33,11 12 43))',
                Polygon::fromArray([[[11, 12, null, 13], [21, 22, null, 23], [31, 32, null, 33], [11, 12, null, 43]]]),
            ],
            [
                'POLYGON M ( ( 11 12 13, 21 22 23, 31 32 33, 11 12 43 ) )',
                Polygon::fromArray([[[11, 12, null, 13], [21, 22, null, 23], [31, 32, null, 33], [11, 12, null, 43]]]),
            ],
            [
                'POLYGONZM((11 12 13 14,21 22 23 24,31 32 33 34,11 12 43 44))',
                Polygon::fromArray([[[11, 12, 13, 14], [21, 22, 23, 24], [31, 32, 33, 34], [11, 12, 43, 44]]]),
            ],
            [
                'POLYGON ZM ( ( 11 12 13 14, 21 22 23 24, 31 32 33 34, 11 12 43 44 ) )',
                Polygon::fromArray([[[11, 12, 13, 14], [21, 22, 23, 24], [31, 32, 33, 34], [11, 12, 43, 44]]]),
            ],
            [
                'POLYGON ((11 12 13 14, 21 22 23 24, 31 32 33 34, 11 12 43 44), ' .
                '(111 112 113 114, 121 122 123 124, 131 132 133 134, 111 112 143 144))',
                Polygon::fromArray(
                    [
                        [[11, 12, 13, 14], [21, 22, 23, 24], [31, 32, 33, 34], [11, 12, 43, 44]],
                        [[111, 112, 113, 114], [121, 122, 123, 124], [131, 132, 133, 134], [111, 112, 143, 144]]
                    ]
                ),
            ],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public function providerInvalidPolygon()
    {
        return [
            'Polygon no data text' => [
                'POLYGON',
            ],
            'Polygon empty components' => [
                'POLYGON ()',
            ],
            'Polygon empty ring' => [
                'POLYGON (())',
            ],
            'Polygon non closed ring' => [
                'POLYGON ((11 12, 21 22, 31 32, 41 42))',
            ],
            'Polygon missing )' => [
                'POLYGON ((11 12, 21 22, 31 32, 11 12)',
            ],
            'Polygon missing , between coordinates' => [
                'POLYGON ((11 12 21 22, 31 32, 11 12))',
            ],
            'Polygon empty second ring' => [
                'POLYGON ((11 12, 21 22, 31 32, 11 12), ())',
            ],
        ];
    }

    /**
     * Test cases for MultiPoint.
     *
     * There are two common interpretation and geoPHP should support both:
     * MULTIPOINT ((1 2), (3 4))
     * MULTIPOINT (1 2, 3 4)
     *
     * @return array<array{string, Geometry}>
     */
    public function providerValidMultiPoint(): array
    {
        return [
            [
                'MULTIPOINT EMPTY',
                new MultiPoint(),
            ],
            [
                'MULTIPOINT(1 2)',
                MultiPoint::fromArray([[1, 2]]),
            ],
            [
                'MULTIPOINT((1 2))',
                MultiPoint::fromArray([[1, 2]]),
            ],
            [
                'MULTIPOINT (EMPTY, 1 2)',
                MultiPoint::fromArray([[], [1, 2]]),
            ],
            [
                'MULTIPOINT ((1 2), EMPTY)',
                MultiPoint::fromArray([[1, 2], []]),
            ],
            [
                'MULTIPOINT(1 2,5 6)',
                MultiPoint::fromArray([[1, 2], [5, 6]]),
            ],
            [
                'MULTIPOINT((1 2),(5 6))',
                MultiPoint::fromArray([[1, 2], [5, 6]]),
            ],
            [
                'MULTIPOINT ( 1 2, 5 6 )',
                MultiPoint::fromArray([[1, 2], [5, 6]]),
            ],
            [
                'MULTIPOINT ( ( 1 2 ), ( 5 6 ) )',
                MultiPoint::fromArray([[1, 2], [5, 6]]),
            ],
            [
                'MULTIPOINT (1 2, 5 6, 10 11, 15 16)',
                MultiPoint::fromArray([[1, 2], [5, 6], [10, 11], [15, 16]]),
            ],
            [
                'MULTIPOINT ((1 2), (5 6), (10 11), (15 16))',
                MultiPoint::fromArray([[1, 2], [5, 6], [10, 11], [15, 16]]),
            ],
            [
                'MULTIPOINT Z (1 2 3, 5 6 7)',
                MultiPoint::fromArray([[1, 2, 3], [5, 6, 7]]),
            ],
            [
                'MULTIPOINTZ(1 2 3,5 6 7)',
                MultiPoint::fromArray([[1, 2, 3], [5, 6, 7]]),
            ],
            [
                'MULTIPOINT (1 2 3, 5 6 7)',
                MultiPoint::fromArray([[1, 2, 3], [5, 6, 7]]),
            ],
            [
                'MULTIPOINT M (1 2 3, 5 6 7)',
                MultiPoint::fromArray([[1, 2, null, 3], [5, 6, null, 7]]),
            ],
            [
                'MULTIPOINTM(1 2 3, 5 6 7)',
                MultiPoint::fromArray([[1, 2, null, 3], [5, 6, null, 7]]),
            ],
            [
                'MULTIPOINT ZM (1 2 3 4, 5 6 7 8)',
                MultiPoint::fromArray([[1, 2, 3, 4], [5, 6, 7, 8]]),
            ],
            [
                'MULTIPOINT ZM (1 2 3 4, 5 6 7 8)',
                MultiPoint::fromArray([[1, 2, 3, 4], [5, 6, 7, 8]]),
            ],
            [
                'MULTIPOINT (1 2 3 4, 5 6 7 8)',
                MultiPoint::fromArray([[1, 2, 3, 4], [5, 6, 7, 8]]),
            ],

            // Mismatched coordinate dimension but we are following Geos's tolerant reader.
            [
                'MULTIPOINT Z (1 2 3 4, 5 6 7 8)',
                MultiPoint::fromArray([[1, 2, 3], [5, 6, 7]]),
            ],
            [
                'MULTIPOINT M (1 2 3 4, 5 6 7 8)',
                MultiPoint::fromArray([[1, 2, null, 3], [5, 6, null, 7]]),
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public function providerInvalidMultiPoint()
    {
        return [
            [
                'MULTIPOINT',
            ],
            [
                'MULTIPOINT ()',
            ],
            [
                'MULTIPOINT (1)',
            ],
            [
                'MULTIPOINT ((1 2, 3 4))',
            ],
            [
                'MULTIPOINT ((1 2), 3 4)',
            ],
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerValidMultiLineString()
    {
        return [
            [
                'MULTILINESTRING EMPTY',
                new MultiLineString(),
            ],
            [
                'MULTILINESTRING ( ( 11 12, 21 22, 31 32, 11 12 ) )',
                MultiLineString::fromArray([[[11, 12], [21, 22], [31, 32], [11, 12]]]),
            ],
            [
                'MULTILINESTRING((11 12,21 22),(31 32,11 12))',
                MultiLineString::fromArray([[[11, 12], [21, 22]], [[31, 32], [11, 12]]]),
            ],
            [
                'MULTILINESTRING (EMPTY, (31 32, 11 12))',
                MultiLineString::fromArray([[], [[31, 32], [11, 12]]]),
            ],
            [
                'MULTILINESTRING ((11 12, 21 22), EMPTY)',
                MultiLineString::fromArray([[[11, 12], [21, 22]], []]),
            ],
            [
                'MULTILINESTRING ( (11 12, 21 22, 31 32, 11 12), (111 112, 121 122, 131 132, 111 112) )',
                MultiLineString::fromArray(
                    [
                        [[11, 12], [21, 22], [31, 32], [11, 12]],
                        [[111, 112], [121, 122], [131, 132], [111, 112]]
                    ]
                ),
            ],
            [
                'MULTILINESTRING Z ((11 12 13,21 22 23),(31 32 33,11 12 43))',
                MultiLineString::fromArray([[[11, 12, 13], [21, 22, 23]], [[31, 32, 33], [11, 12, 43]]]),
            ],
            [
                'MULTILINESTRING ((11 12 13, 21 22 23), (31 32 33, 11 12 43))',
                MultiLineString::fromArray([[[11, 12, 13], [21, 22, 23]], [[31, 32, 33], [11, 12, 43]]]),
            ],
            [
                'MULTILINESTRING M ((11 12 13,21 22 23), (31 32 33,11 12 43))',
                MultiLineString::fromArray(
                    [[[11, 12, null, 13], [21, 22, null, 23]], [[31, 32, null, 33], [11, 12, null, 43]]]
                ),
            ],
            [
                'MULTILINESTRING ZM ((11 12 13 14, 21 22 23 24), (31 32 33 34, 11 12 43 44))',
                MultiLineString::fromArray(
                    [[[11, 12, 13, 14], [21, 22, 23, 24]], [[31, 32, 33, 34], [11, 12, 43, 44]]]
                ),
            ],
            [
                'MULTILINESTRING ((11 12 13 14, 21 22 23 24), (31 32 33 34, 11 12 43 44))',
                MultiLineString::fromArray(
                    [[[11, 12, 13, 14], [21, 22, 23, 24]], [[31, 32, 33, 34], [11, 12, 43, 44]]]
                ),
            ],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public function providerInvalidMultiLineString()
    {
        return [
            'MultiLineString no data text' => [
                'MULTILINESTRING',
            ],
            'MultiLineString empty components' => [
                'MULTILINESTRING ()',
            ],
            'MultiLineString empty line' => [
                'MULTILINESTRING (())',
            ],
            'MultiLineString missing )' => [
                'MULTILINESTRING ((11 12, 21 22)',
            ],
            'MultiLineString missing , between coordinates' => [
                'MULTILINESTRING ((11 12 21 22, 31 32, 11 12))',
            ],
            'MultiLineString empty second line' => [
                'MULTILINESTRING ((11 12, 21 22, 31 32, 11 12), ())',
            ],
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerValidMultiPolygon()
    {
        return [
            'MultiPolygon empty' => [
                'MULTIPOLYGON EMPTY',
                new MultiPolygon(),
            ],
            'MultiPolygon one single' => [
                'MULTIPOLYGON(((11 12, 21 22, 31 32, 11 12)))',
                MultiPolygon::fromArray([[[[11, 12], [21, 22], [31, 32], [11, 12]]]]),
            ],
            'MultiPolygon one single 2' => [
                'MULTIPOLYGON ( ( ( 11 12, 21 22, 31 32, 11 12 ) ) )',
                MultiPolygon::fromArray([[[[11, 12], [21, 22], [31, 32], [11, 12]]]]),
            ],
            'MultiPolygon one with two rings' => [
                'MULTIPOLYGON (((11 12, 21 22, 31 32, 11 12), (111 112, 121 122, 131 132, 111 112)))',
                MultiPolygon::fromArray(
                    [[
                        [[11, 12], [21, 22], [31, 32], [11, 12]],
                        [[111, 112], [121, 122], [131, 132], [111, 112]]
                    ]]
                ),
            ],
            'MultiPolygon two sinle' => [
                'MULTIPOLYGON (((11 12, 21 22, 31 32, 11 12)), ((111 112, 121 122, 131 132, 111 112)))',
                MultiPolygon::fromArray(
                    [
                        [[[11, 12], [21, 22], [31, 32], [11, 12]]],
                        [[[111, 112], [121, 122], [131, 132], [111, 112]]]
                    ]
                ),
            ],
            'MultiPolygon two, first is empty' => [
                'MULTIPOLYGON (EMPTY, ((111 112, 121 122, 131 132, 111 112)))',
                MultiPolygon::fromArray(
                    [
                        [],
                        [[[111, 112], [121, 122], [131, 132], [111, 112]]]
                    ]
                ),
            ],
            'MultiPolygon two with two rings' => [
                'MULTIPOLYGON (' .
                    '((11 12, 21 22, 31 32, 11 12), (111 112, 121 122, 131 132, 111 112)),' .
                    '((211 212, 221 222, 231 232, 211 212), (311 312, 321 322, 331 332, 311 312))' .
                    ')',
                MultiPolygon::fromArray(
                    [
                        [
                            [[11, 12], [21, 22], [31, 32], [11, 12]],
                            [[111, 112], [121, 122], [131, 132], [111, 112]]
                        ],
                        [
                            [[211, 212], [221, 222], [231, 232], [211, 212]],
                            [[311, 312], [321, 322], [331, 332], [311, 312]]
                        ],
                    ]
                ),
            ],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public function providerInvalidMultiPolygon()
    {
        return [
            'MultiPolygon no data text' => [
                'MULTIPOLYGON',
            ],
            'MultiPolygon empty components' => [
                'MULTIPOLYGON ()',
            ],
            'MultiPolygon empty polygon' => [
                'MULTIPOLYGON (())',
            ],
            'MultiPolygon empty ring' => [
                'MULTIPOLYGON ((()))',
            ],
            'MultiPolygon non closed ring' => [
                'MULTIPOLYGON (((11 12, 21 22, 31 32, 41 42)))',
            ],
            'MultiPolygon missing )' => [
                'MULTIPOLYGON (((11 12, 21 22, 31 32, 11 12))',
            ],
            'MultiPolygon missing , between coordinates' => [
                'MULTIPOLYGON (((11 12 21 22, 31 32, 11 12)))',
            ],
            'MultiPolygon empty second polygon' => [
                'MULTIPOLYGON (((11 12, 21 22, 31 32, 11 12), ()))',
            ],
            'MultiPolygon missing ) between polygons middle' => [
                'MULTIPOLYGON (((11 12, 21 22, 31 32, 11 12), ((111 112, 121 122, 131 132, 111 112)))',
            ],
            'MultiPolygon missing ) between polygons end' => [
                'MULTIPOLYGON (((11 12, 21 22, 31 32, 11 12)), ((111 112, 121 122, 131 132, 111 112',
            ],
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerValidGeometryCollection()
    {
        return [
            'GeometryCollection empty' => [
                'GEOMETRYCOLLECTION EMPTY',
                new GeometryCollection(),
            ],
            'GeometryCollection one point' => [
                'GEOMETRYCOLLECTION (POINT(1 2))',
                new GeometryCollection(
                    [new Point(1, 2)]
                ),
            ],
            'GeometryCollection point, ls' => [
                'GEOMETRYCOLLECTION (POINT(1 2), LINESTRING(1 2, 3 4))',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        LineString::fromArray([[1, 2], [3, 4]])
                    ]
                ),
            ],
            'GeometryCollection point, ls empty' => [
                'GEOMETRYCOLLECTION (POINT(1 2), LINESTRING EMPTY)',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new LineString()
                    ]
                ),
            ],
            'GeometryCollection multipoly' => [
                'GEOMETRYCOLLECTION (' .
                ' MULTIPOLYGON (((1 2, 3 4, 5 6, 1 2), (7 8, 9 10, 11 12, 7 8)))' .
                ')',
                new GeometryCollection(
                    [
                        MultiPolygon::fromArray(
                            [[[[1, 2], [3, 4], [5, 6], [1, 2]], [[7, 8], [9, 10], [11, 12], [7, 8]]]]
                        )
                    ]
                ),
            ],
            'GeometryCollection all types' => [
                'GEOMETRYCOLLECTION (' .
                ' POINT(1 2),' .
                ' LINESTRING(1 2, 3 4),' .
                ' POLYGON((1 2, 3 4, 5 6, 1 2), (7 8, 9 10, 11 12, 7 8))' .
                ' MULTIPOINT(1 2, 3 4, 5 6),' .
                ' MULTIPOINT((1 2), (3 4), (5 6)),' .
                ' MULTILINESTRING((1 2, 3 4, 5 6, 1 2), (7 8, 9 10, 11 12, 7 8)),' .
                ' MULTIPOLYGON (((1 2, 3 4, 5 6, 1 2), (7 8, 9 10, 11 12, 7 8)))' .
                ')',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        LineString::fromArray([[1, 2], [3, 4]]),
                        Polygon::fromArray([[[1, 2], [3, 4], [5, 6], [1, 2]], [[7, 8], [9, 10], [11, 12], [7, 8]]]),
                        MultiPoint::fromArray([[1, 2], [3, 4], [5, 6]]),
                        MultiPoint::fromArray([[1, 2], [3, 4], [5, 6]]),
                        MultiLineString::fromArray(
                            [[[1, 2], [3, 4], [5, 6], [1, 2]], [[7, 8], [9, 10], [11, 12], [7, 8]]]
                        ),
                        MultiPolygon::fromArray(
                            [[[[1, 2], [3, 4], [5, 6], [1, 2]], [[7, 8], [9, 10], [11, 12], [7, 8]]]]
                        ),
                    ]
                ),
            ],
            'GeometryCollection (point, geometrycollection(ls))' => [
                'GEOMETRYCOLLECTION (POINT(1 2), GEOMETRYCOLLECTION (LINESTRING(1 2, 3 4)))',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new GeometryCollection([LineString::fromArray([[1, 2], [3, 4]])])
                    ]
                ),
            ],
            'GeometryCollection (point, geometrycollection(empty point))' => [
                'GEOMETRYCOLLECTION (POINT(1 2), GEOMETRYCOLLECTION (POINT EMPTY))',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new GeometryCollection([new Point()])
                    ]
                ),
            ],
            'GeometryCollection (point, geometrycollection(multipoint(point, empty)))' => [
                'GEOMETRYCOLLECTION (POINT(1 2), GEOMETRYCOLLECTION (MULTIPOINT ((1 2), EMPTY) ) )',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new GeometryCollection([MultiPoint::fromArray([[1, 2], []])])
                    ]
                ),
            ],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public function providerInvalidGeometryCollection()
    {
        return [
            'GeometryCollection no data text' => [
                'GEOMETRYCOLLECTION',
            ],
            'GeometryCollection no geometry type' => [
                'GEOMETRYCOLLECTION ((1 2))',
            ],
            'GeometryCollection wrong parenthesis' => [
                'GEOMETRYCOLLECTION (POINT(1 2, LINESTRING(1 2, 3 4))',
            ],
            'GeometryCollection wrong parenthesis 2' => [
                'GEOMETRYCOLLECTION (POINT(1 2), LINESTRING(1 2, 3 4)',
            ],
        ];
    }
}
