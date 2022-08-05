<?php

namespace geoPHP\Tests\Unit\Legacy;

use geoPHP\Geometry\Geometry;
use geoPHP\geoPHP;
use PHPUnit\Framework\TestCase;

class GeosTest extends TestCase
{
    public function testGeos(): void
    {
        if (!geoPHP::isGeosInstalled()) {
            $this->markTestSkipped('GEOS not installed');
        }

        foreach (scandir('tests/input') as $file) {
            $parts = explode('.', $file);
            if ($parts[0]) {
                if ($parts[0] == 'countries_ne_110m') {
                    // Due to a bug in GEOS we have to skip some tests
                    // It drops TopologyException for valid geometries
                    // https://trac.osgeo.org/geos/ticket/737
            //          continue;
                }

                $format = $parts[1];
                $value = file_get_contents('tests/input/' . $file);
              //echo "\nloading: " . $file . " for format: " . $format;
                $geometry = geoPHP::load($value, $format);

                $geosMethods = [
                ['name' => 'getGeos'],
                ['name' => 'flushGeosCache'],
                ['name' => 'pointOnSurface'],
                ['name' => 'equals', 'argument' => $geometry],
                ['name' => 'equalsExact', 'argument' => $geometry],
                ['name' => 'relate', 'argument' => $geometry],
                ['name' => 'checkValidity'],
                ['name' => 'isSimple'],
                ['name' => 'buffer', 'argument' => '10'],
                ['name' => 'intersection', 'argument' => $geometry],
                ['name' => 'convexHull'],
                ['name' => 'difference', 'argument' => $geometry],
                ['name' => 'symDifference', 'argument' => $geometry],
                ['name' => 'union', 'argument' => $geometry],
                ['name' => 'simplify', 'argument' => '0'],
                ['name' => 'disjoint', 'argument' => $geometry],
                ['name' => 'touches', 'argument' => $geometry],
                ['name' => 'intersects', 'argument' => $geometry],
                ['name' => 'crosses', 'argument' => $geometry],
                ['name' => 'within', 'argument' => $geometry],
                ['name' => 'contains', 'argument' => $geometry],
                ['name' => 'overlaps', 'argument' => $geometry],
                ['name' => 'covers', 'argument' => $geometry],
                ['name' => 'coveredBy', 'argument' => $geometry],
                ['name' => 'distance', 'argument' => $geometry],
                ['name' => 'hausdorffDistance', 'argument' => $geometry],
                ];

                foreach ($geosMethods as $method) {
                    $argument = null;
                    $methodName = $method['name'];
                    if (isset($method['argument'])) {
                        $argument = $method['argument'];
                    }
                    $errorMessage = 'Failed on "' . $methodName . '" method with test file "' . $file . '"';

                  // GEOS don't like empty points
                    if ($geometry->geometryType() == 'Point' && $geometry->isEmpty()) {
                        continue;
                    }

                    switch ($methodName) {
                        case 'geos':
                            $this->assertInstanceOf('GEOSGeometry', $geometry->$methodName($argument), $errorMessage);
                            break;
                        case 'equals':
                        case 'equalsExact':
                        case 'disjoint':
                        case 'touches':
                        case 'intersects':
                        case 'crosses':
                        case 'within':
                        case 'contains':
                        case 'overlaps':
                        case 'covers':
                        case 'coveredBy':
                            $this->assertIsBool($geometry->$methodName($argument), $errorMessage);
                            break;
                        case 'pointOnSurface':
                        case 'buffer':
                        case 'intersection':
                        case 'convexHull':
                        case 'difference':
                        case 'symDifference':
                        case 'union':
                        case 'simplify':
                            $this->assertInstanceOf(Geometry::class, $geometry->$methodName($argument), $errorMessage);
                            break;
                        case 'distance':
                        case 'hausdorffDistance':
                            $this->assertIsFloat($geometry->$methodName($argument), $errorMessage);
                            break;
                        case 'relate':
                            $this->assertMatchesRegularExpression(
                                '/[0-9TF]{9}/',
                                $geometry->$methodName($argument),
                                $errorMessage
                            );
                            break;
                        case 'checkValidity':
                            $this->assertArrayHasKey('valid', $geometry->$methodName($argument), $errorMessage);
                            break;
                        case 'isSimple':
                            if ($geometry->geometryType() == 'GeometryCollection') {
                                $this->assertNull($geometry->$methodName($argument), $errorMessage);
                            } else {
                                $this->assertNotNull($geometry->$methodName($argument), $errorMessage);
                            }
                            break;
                        default:
                    }
                }
            }
        }
    }
}
