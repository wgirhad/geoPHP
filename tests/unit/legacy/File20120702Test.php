<?php

namespace geoPHP\Tests\Unit\Legacy;

use geoPHP\geoPHP;
use geoPHP\Geometry\Geometry;
use PHPUnit\Framework\TestCase;

// FIXME file 20120702.gpx contains one MultiLineString
// but methodTester() also wants to test Points and LineStrings (ie does nothing)

class File20120702Test extends TestCase
{
    public function testMethods(): void
    {
        $format = 'gpx';
        $value = file_get_contents('tests/input/20120702.gpx');
        $geometry = geoPHP::load($value, $format);

        $methods = [
        ['name' => 'area'],
        ['name' => 'boundary'],
        ['name' => 'getBBox'],
        ['name' => 'centroid'],
        ['name' => 'length'],
        ['name' => 'greatCircleLength', 'argument' => 6378137],
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
            $this->methodsTester($geometry, $methodName, $argument);
        }
    }

  /**
   * @param Geometry $geometry
   * @param string $methodName
   * @param mixed $argument
   */
    private function methodsTester(Geometry $geometry, string $methodName, $argument): void
    {

        if (!method_exists($geometry, $methodName)) {
            $this->fail("Method " . $methodName . '() doesn\'t exists.');
        }

        $failedOnMessage = $geometry->geometryType() . ' failed on ' . $methodName ;

        switch ($methodName) {
            case 'y':
            case 'x':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNull($geometry->$methodName($argument), $failedOnMessage);
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
            case 'setSRID':
              //TODO: The method setSRID() should return TRUE.
                break;
            case 'SRID':
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
            case 'getBBox':
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
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertEquals(
                        $geometry->$methodName($argument),
                        (float) '0.11624637315233',
                        $failedOnMessage
                    );
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
                    $this->assertEquals($geometry->$methodName($argument), 1, $failedOnMessage);
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
                    $this->assertEquals($geometry->$methodName($argument), 0, $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertEquals($geometry->$methodName($argument), 1, $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertEquals($geometry->$methodName($argument), 1, $failedOnMessage);
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
            case 'greatCircleLength':
                if ($geometry->geometryType() == 'Point') {
                    $this->assertEquals($geometry->$methodName($argument), 0, $failedOnMessage);
                }
                if ($geometry->geometryType() == 'LineString') {
                    $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                }
                if ($geometry->geometryType() == 'MultiLineString') {
                    $this->assertNotEquals($geometry->$methodName($argument), '9500.9359867418', $failedOnMessage);
                }
                break;
            case 'haversineLength':
            case 'area':
                $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                break;
            case 'geometryType':
                $this->assertNotNull($geometry->$methodName($argument), $failedOnMessage);
                break;
            default:
                $this->assertTrue($geometry->$methodName($argument), $failedOnMessage);
        }
    }
}
