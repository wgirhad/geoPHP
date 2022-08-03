<?php

namespace geoPHP\Tests\Unit\Adapter;

use geoPHP\Adapter\WKT;
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
 * Test cases for reading capabilities of WKT adapter.
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
class WKTWriterTest extends TestCase
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
     * @dataProvider providerPoint
     * @dataProvider providerLineString
     * @dataProvider providerPolygon
     * @dataProvider providerMultiPoint
     * @dataProvider providerMultiLineString
     * @dataProvider providerMultiPolygon
     * @dataProvider providerGeometryCollection
     *
     * @covers ::write
     * @covers ::extractData
     * @covers ::writePoint
     * @covers ::writeLineString
     * @covers ::writeMulti
     * @covers ::writeGeometryCollection
     *
     * @param string $expectedWkt
     * @param Geometry $geometry
     * @return void
     */
    public function testValidWkt(string $expectedWkt, Geometry $geometry): void
    {
        $wkt = self::$wktAdapter->write($geometry);
        $this->assertEquals($expectedWkt, $wkt);
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerPoint(): array
    {
        return [
            [
                'POINT EMPTY',
                new Point(),
            ],
            [
                'POINT (1 2)',
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
                'POINT M (1 2 3)',
                new Point(1, 2, null, 3),
            ],
            [
                'POINT ZM (1 2 3 4)',
                new Point(1, 2, 3, 4),
            ],
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerLineString(): array
    {
        return [
            [
                'LINESTRING EMPTY',
                new LineString(),
            ],
            [
                'LINESTRING (1 2, 5 6)',
                LineString::fromArray([[1, 2], [5, 6]]),
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
                'LINESTRING M (1 2 3, 5 6 7)',
                LineString::fromArray([[1, 2, null, 3], [5, 6, null, 7]]),
            ],
            [
                'LINESTRING ZM (1 2 3 4, 5 6 7 8)',
                LineString::fromArray([[1, 2, 3, 4], [5, 6, 7, 8]]),
            ],
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerPolygon(): array
    {
        return [
            [
                'POLYGON EMPTY',
                new Polygon(),
            ],
            [
                'POLYGON ((11 12, 21 22, 31 32, 11 12))',
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
                'POLYGON Z ((11 12 13, 21 22 23, 31 32 33, 11 12 43))',
                Polygon::fromArray([[[11, 12, 13], [21, 22, 23], [31, 32, 33], [11, 12, 43]]]),
            ],
            [
                'POLYGON Z ((11 12 13, 21 22 23, 31 32 33, 11 12 43), ' .
                '(111 112 113, 121 122 123, 131 132 133, 111 112 143))',
                Polygon::fromArray(
                    [
                        [[11, 12, 13], [21, 22, 23], [31, 32, 33], [11, 12, 43]],
                        [[111, 112, 113], [121, 122, 123], [131, 132, 133], [111, 112, 143]]
                    ]
                ),
            ],
            [
                'POLYGON M ((11 12 13, 21 22 23, 31 32 33, 11 12 43))',
                Polygon::fromArray([[[11, 12, null, 13], [21, 22, null, 23], [31, 32, null, 33], [11, 12, null, 43]]]),
            ],
            [
                'POLYGON ZM ((11 12 13 14, 21 22 23 24, 31 32 33 34, 11 12 43 44))',
                Polygon::fromArray([[[11, 12, 13, 14], [21, 22, 23, 24], [31, 32, 33, 34], [11, 12, 43, 44]]]),
            ],
            [
                'POLYGON ZM ((11 12 13 14, 21 22 23 24, 31 32 33 34, 11 12 43 44), ' .
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
     * @return array<array{string, Geometry}>
     */
    public function providerMultiPoint(): array
    {
        return [
            [
                'MULTIPOINT EMPTY',
                new MultiPoint(),
            ],
            [
                'MULTIPOINT ((1 2))',
                MultiPoint::fromArray([[1, 2]]),
            ],
            [
                'MULTIPOINT ((1 2), (5 6))',
                MultiPoint::fromArray([[1, 2], [5, 6]]),
            ],
            [
                'MULTIPOINT ((1 2), (5 6), (10 11), (15 16))',
                MultiPoint::fromArray([[1, 2], [5, 6], [10, 11], [15, 16]]),
            ],
            [
                'MULTIPOINT Z ((1 2 3), (5 6 7))',
                MultiPoint::fromArray([[1, 2, 3], [5, 6, 7]]),
            ],
            [
                'MULTIPOINT M ((1 2 3), (5 6 7))',
                MultiPoint::fromArray([[1, 2, null, 3], [5, 6, null, 7]]),
            ],
            [
                'MULTIPOINT ZM ((1 2 3 4), (5 6 7 8))',
                MultiPoint::fromArray([[1, 2, 3, 4], [5, 6, 7, 8]]),
            ],
            [
                'MULTIPOINT (EMPTY, (1 2))',
                MultiPoint::fromArray([[], [1, 2]]),
            ],
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerMultiLineString(): array
    {
        return [
            [
                'MULTILINESTRING EMPTY',
                new MultiLineString(),
            ],
            [
                'MULTILINESTRING ((11 12, 21 22, 31 32, 11 12))',
                MultiLineString::fromArray([[[11, 12], [21, 22], [31, 32], [11, 12]]]),
            ],
            [
                'MULTILINESTRING ((11 12, 21 22, 31 32, 11 12), (111 112, 121 122, 131 132, 111 112))',
                MultiLineString::fromArray(
                    [
                        [[11, 12], [21, 22], [31, 32], [11, 12]],
                        [[111, 112], [121, 122], [131, 132], [111, 112]]
                    ]
                ),
            ],
            [
                'MULTILINESTRING Z ((11 12 13, 21 22 23), (31 32 33, 11 12 43))',
                MultiLineString::fromArray([[[11, 12, 13], [21, 22, 23]], [[31, 32, 33], [11, 12, 43]]]),
            ],
            [
                'MULTILINESTRING M ((11 12 13, 21 22 23), (31 32 33, 11 12 43))',
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
        ];
    }

    /**
     * @return array<array{string, Geometry}>
     */
    public function providerMultiPolygon(): array
    {
        return [
            'MultiPolygon empty' => [
                'MULTIPOLYGON EMPTY',
                new MultiPolygon(),
            ],
            'MultiPolygon one single' => [
                'MULTIPOLYGON (((11 12, 21 22, 31 32, 11 12)))',
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
            'MultiPolygon two with two rings' => [
                'MULTIPOLYGON (' .
                    '((11 12, 21 22, 31 32, 11 12), (111 112, 121 122, 131 132, 111 112)), ' .
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
     * @return array<array{string, Geometry}>
     */
    public function providerGeometryCollection(): array
    {
        return [
            'GeometryCollection empty' => [
                'GEOMETRYCOLLECTION EMPTY',
                new GeometryCollection(),
            ],
            'GeometryCollection one point' => [
                'GEOMETRYCOLLECTION (POINT (1 2))',
                new GeometryCollection(
                    [new Point(1, 2)]
                ),
            ],
            'GeometryCollection point, ls' => [
                'GEOMETRYCOLLECTION (POINT (1 2), LINESTRING (1 2, 3 4))',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        LineString::fromArray([[1, 2], [3, 4]])
                    ]
                ),
            ],
            'GeometryCollection multipoly' => [
                'GEOMETRYCOLLECTION (' .
                'MULTIPOLYGON (((1 2, 3 4, 5 6, 1 2), (7 8, 9 10, 11 12, 7 8)))' .
                ')',
                new GeometryCollection(
                    [
                        MultiPolygon::fromArray(
                            [[[[1, 2], [3, 4], [5, 6], [1, 2]], [[7, 8], [9, 10], [11, 12], [7, 8]]]]
                        )
                    ]
                ),
            ],
            'GeometryCollection point, ls empty' => [
                'GEOMETRYCOLLECTION (POINT (1 2), LINESTRING EMPTY)',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new LineString()
                    ]
                ),
            ],
            'GeometryCollection point, multipoly empty' => [
                'GEOMETRYCOLLECTION (POINT (1 2), MULTIPOLYGON EMPTY)',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new MultiPolygon()
                    ]
                ),
            ],
            'GeometryCollection all types' => [
                'GEOMETRYCOLLECTION (' .
                'POINT (1 2), ' .
                'LINESTRING (1 2, 3 4), ' .
                'POLYGON ((1 2, 3 4, 5 6, 1 2), (7 8, 9 10, 11 12, 7 8)), ' .
                'MULTIPOINT ((1 2), (3 4), (5 6)), ' .
                'MULTILINESTRING ((1 2, 3 4, 5 6, 1 2), (7 8, 9 10, 11 12, 7 8)), ' .
                'MULTIPOLYGON (((1 2, 3 4, 5 6, 1 2), (7 8, 9 10, 11 12, 7 8)))' .
                ')',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        LineString::fromArray([[1, 2], [3, 4]]),
                        Polygon::fromArray([[[1, 2], [3, 4], [5, 6], [1, 2]], [[7, 8], [9, 10], [11, 12], [7, 8]]]),
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
            'GeometryCollection point, geometrycollection(ls)' => [
                'GEOMETRYCOLLECTION (POINT (1 2), GEOMETRYCOLLECTION (LINESTRING (1 2, 3 4)))',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new GeometryCollection([LineString::fromArray([[1, 2], [3, 4]])])
                    ]
                ),
            ],
            'GeometryCollection (point, geometrycollection(ls))' => [
                'GEOMETRYCOLLECTION (POINT (1 2), GEOMETRYCOLLECTION (LINESTRING (1 2, 3 4)))',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new GeometryCollection([LineString::fromArray([[1, 2], [3, 4]])])
                    ]
                ),
            ],
            'GeometryCollection (point, geometrycollection(empty point))' => [
                'GEOMETRYCOLLECTION (POINT (1 2), GEOMETRYCOLLECTION (POINT EMPTY))',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new GeometryCollection([new Point()])
                    ]
                ),
            ],
            'GeometryCollection (point, geometrycollection(multipoint(point, empty)))' => [
                'GEOMETRYCOLLECTION (POINT (1 2), GEOMETRYCOLLECTION (MULTIPOINT ((1 2), EMPTY)))',
                new GeometryCollection(
                    [
                        new Point(1, 2),
                        new GeometryCollection([MultiPoint::fromArray([[1, 2], []])])
                    ]
                ),
            ],
            'GeometryCollection Z point Z, ls Z' => [
                'GEOMETRYCOLLECTION Z (POINT Z (1 2 10), LINESTRING Z (1 2 20, 3 4 30))',
                new GeometryCollection(
                    [
                        new Point(1, 2, 10),
                        LineString::fromArray([[1, 2, 20], [3, 4, 30]])
                    ]
                ),
            ],
            'GeometryCollection Z multipoly Z' => [
                'GEOMETRYCOLLECTION Z (' .
                'MULTIPOLYGON Z (((1 2 10, 3 4 20, 5 6 30, 1 2 10), (7 8 40, 9 10 50, 11 12 60, 7 8 40)))' .
                ')',
                new GeometryCollection(
                    [
                        MultiPolygon::fromArray(
                            [[
                                [[1, 2, 10], [3, 4, 20], [5, 6, 30], [1, 2, 10]],
                                [[7, 8, 40], [9, 10, 50], [11, 12, 60], [7, 8, 40]]
                            ]]
                        )
                    ]
                ),
            ],
            'GeometryCollection mixed Z point Z, ls' => [
                'GEOMETRYCOLLECTION Z (POINT Z (1 2 10), LINESTRING Z (1 2 0, 3 4 0))',
                new GeometryCollection(
                    [
                        new Point(1, 2, 10),
                        LineString::fromArray([[1, 2], [3, 4]])
                    ]
                ),
            ],
            'GeometryCollection M point M, ls M' => [
                'GEOMETRYCOLLECTION M (POINT M (1 2 10), LINESTRING M (1 2 20, 3 4 30))',
                new GeometryCollection(
                    [
                        new Point(1, 2, null, 10),
                        LineString::fromArray([[1, 2, null, 20], [3, 4, null, 30]])
                    ]
                ),
            ],
            'GeometryCollection ZM point ZM, ls ZM' => [
                'GEOMETRYCOLLECTION ZM (POINT ZM (1 2 10 11), LINESTRING ZM (1 2 20 21, 3 4 30 31))',
                new GeometryCollection(
                    [
                        new Point(1, 2, 10, 11),
                        LineString::fromArray([[1, 2, 20, 21], [3, 4, 30, 31]])
                    ]
                ),
            ],
        ];
    }
}
