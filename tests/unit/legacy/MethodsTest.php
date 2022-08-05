<?php

namespace geoPHP\Tests\Unit\Legacy;

use geoPHP\Geometry\Geometry;
use geoPHP\geoPHP;
use PHPUnit\Framework\TestCase;

class MethodsTest extends TestCase
{
    public function testMethods(): void
    {
        foreach (scandir('tests/input') as $file) {
            $parts = explode('.', $file);
            if ($parts[0]) {
                $format = $parts[1];
                $value = file_get_contents('tests/input/' . $file);
                //echo "\nloading: " . $file . " for format: " . $format;
                $geometry = geoPHP::load($value, $format);

                $methods = [
                ['name' => 'area', 'argument' => false ],
                ['name' => 'boundary'],
                ['name' => 'getBBox'],
                ['name' => 'centroid'],
                ['name' => 'length'],
                ['name' => 'greatCircleLength', 'argument' => '1'],
                ['name' => 'haversineLength'],
                ['name' => 'y'],
                ['name' => 'x'],
                ['name' => 'numGeometries'],
                ['name' => 'geometryN', 'argument' => '1'],
                ['name' => 'startPoint'],
                ['name' => 'endPoint'],
                ['name' => 'isRing'],
                ['name' => 'isClosed'],
                ['name' => 'numPoints'],
                ['name' => 'pointN', 'argument' => '1'],
                ['name' => 'exteriorRing'],
                ['name' => 'numInteriorRings'],
                ['name' => 'interiorRingN', 'argument' => '1'],
                ['name' => 'dimension'],
                ['name' => 'geometryType'],
                ['name' => 'SRID'],
                ['name' => 'setSRID', 'argument' => '4326'],
                ];

                foreach ($methods as $method) {
                    $argument = null;
                    $methodName = $method['name'];
                    if (isset($method['argument'])) {
                        $argument = $method['argument'];
                    }

                    $this->methodsTester($geometry, $methodName, $argument, $file);
                }

                $this->methodsTesterWithGeos($geometry);
            }
        }
    }

  /**
   * @param Geometry $geometry
   * @param string $methodName
   * @param string|array<mixed> $argument
   * @param string $file
   */
    private function methodsTester(Geometry $geometry, string $methodName, $argument, string $file): void
    {

        if (!method_exists($geometry, $methodName)) {
            $this->fail("Method " . $methodName . '() doesn\'t exists.');
        }

        $failedOnMessage = 'Failed on ' . $methodName
            . ' (test file: ' . $file . ', geometry type: ' . $geometry->geometryType() . ')';
        switch ($methodName) {
            case 'y':
            case 'x':
                if (!$geometry->isEmpty()) {
                    if ($geometry->geometryType() == 'Point') {
                        $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                    }
                    if ($geometry->geometryType() == 'LineString') {
                        $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                    }
                    if ($geometry->geometryType() == 'MultiLineString') {
                            $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                    }
                }
                break;
            case 'geometryN':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'startPoint':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'endPoint':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'isRing':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'isClosed':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'pointN':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'exteriorRing':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'numInteriorRings':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'interiorRingN':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'SRID':
                break;
            case 'getBBox':
                if (!$geometry->isEmpty()) {
                    if ($geometry->geometryType() == 'Point') {
                          $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                    }
                    if ($geometry->geometryType() == 'LineString') {
                        $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                    }
                    if ($geometry->geometryType() == 'MultiLineString') {
                        $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                    }
                }
                break;
            case 'centroid':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'length':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertEquals($geometry->$methodName($argument), 0, $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotEquals($geometry->$methodName($argument), 0, $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotEquals($geometry->$methodName($argument), 0, $failedOnMessage);
                }
                break;
            case 'numGeometries':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'numPoints':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'dimension':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'boundary':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                break;
            case 'haversineLength':
              //TODO: Check if output is a float >= 0.
              //TODO: Sometimes haversineLength() returns NAN, needs to check why.
                break;
            case 'greatCircleLength':
                $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                break;
            case 'area':
                $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                break;
            case 'geometryType':
                $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                break;
            case 'setSRID':
              //TODO: The method setSRID() should return TRUE.
                break;
            default:
                $this->assertTrue($geometry->$methodName($argument), $failedOnMessage);
        }
    }

  /**
   * @param \geoPHP\Geometry\Geometry $geometry
   * @throws \Exception
   */
    private function methodsTesterWithGeos($geometry): void
    {
      // Cannot test methods if GEOS is not intstalled
        if (!geoPHP::isGeosInstalled()) {
            return;
        }

        $methods = [
        'boundary',
        'envelope',
        'getBBox',
        'x',
        'y',
        'startPoint',
        'endPoint',
        'isRing',
        'isClosed',
        'numPoints',
        ];

        foreach ($methods as $method) {
          // Turn GEOS on
            geoPHP::enableGeos();
            $geosResult = $geometry->$method();

          // Turn GEOS off
            geoPHP::disableGeos();
            $normResult = $geometry->$method();

          // Turn GEOS back On
            geoPHP::enableGeos();

            $geosType = gettype($geosResult);
            $normType = gettype($normResult);

            if ($geosType != $normType) {
                //var_dump($geosType, $normType);
                $this->fail('Type mismatch on ' . $method);
            }

          // Now check base on type
            if ($geosType == 'object') {
              /** @var Geometry $geosResult */
              /** @var Geometry $normResult */
                $hausDist = $geosResult->hausdorffDistance(geoPHP::load($normResult->out('wkt'), 'wkt'));

              // Get the length of the diagonal of the bbox - this is used to scale the haustorff distance
              // Using Pythagorean theorem
                $bb = $geosResult->getBBox();
                $scale = $bb ? sqrt((($bb['maxy'] - $bb['miny']) ^ 2) + (($bb['maxx'] - $bb['minx']) ^ 2)) : 1;

              // The difference in the output of GEOS and native-PHP methods
              // should be less than 0.5 scaled haustorff units
                if ($hausDist / $scale > 0.5) {
                    //var_dump('GEOS : ', $geosResult->out('wkt'), 'NORM : ', $normResult->out('wkt'));
                    $this->fail('Output mismatch on ' . $method);
                }
            }

            if ($geosType == 'boolean' || $geosType == 'string') {
                if ($geosResult !== $normResult) {
                    //var_dump('GEOS : ', $geosResult->out('wkt'), 'NORM : ', $normResult->out('wkt'));
                    $this->fail('Output mismatch on ' . $method);
                }
            }

          //@@TODO: Run tests for output of types arrays and float
          //@@TODO: centroid function is non-compliant for collections and strings
        }
    }
}
