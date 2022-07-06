<?php

/**
 * Some very simple performance test for Geometries.
 * Run before and after you modify geometries.
 * For example adding an array_merge() in a heavily used method can decrease performance dramatically.
 *
 * Please note, that this is not a real CI test, it will not fail on performance drops, just helps spotting them.
 *
 * Feel free to add more test methods.
 */

require '../vendor/autoload.php';

use geoPHP\Geometry\Point;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Polygon;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\geoPHP;

/** Performance test will fail if running takes longer than MAX_RUN_TIME_SEC. (Yes, it's a bit of a dirty method.) */
const MAX_RUN_TIME_SEC = 10;

function testStart($message)
{
    $GLOBALS['runTime'] = microtime(true);
    echo "\e[37m" . $message . "\e[39m\n";
}
function testEnd($result = null, $ready = false)
{
    if ($ready) {
        echo "\nTotal run time: " . round(microtime(true) - $GLOBALS['startTime'], 4) . ' sec,';
    } else {
        echo "\tTime: " . round(microtime(true) - $GLOBALS['runTime'], 4) . ' sec,';
    }
    echo
            "\tMemory: " . round(memory_get_usage() / 1024 / 1024 - $GLOBALS['startMem'], 4) . 'MB' .
            "\tMemory peak: " . round(memory_get_peak_usage() / 1024 / 1024, 4) . 'MB' .
            ($result ? "\tResult: " . $result : '') .
            "\n";
}

GeoPhp::geosInstalled(false);


$startTime = microtime(true);
$startMem = memory_get_usage(true) / 1024 / 1024;
$res = null;


/////////////////////////////////////////////////////////////////////////////////////

$pointCount = 10000;

testStart("Creating " . $pointCount . " EMPTY Point:");
/** @var Point[] $points */
$points = [];
for ($i = 0; $i < $pointCount; $i++) {
    $points[] = new Point();
}
testEnd();

testStart("Creating " . $pointCount . " Point:");
$points = [];
for ($i = 0; $i < $pointCount; $i++) {
    $points[] = new Point($i, $i + 1);
}
testEnd();

testStart("Creating " . $pointCount . " PointZ:");
$points = [];
for ($i = 0; $i < $pointCount; $i++) {
    $points[] = new Point($i, $i + 1, $i + 2);
}
testEnd();

testStart("Creating " . $pointCount . " PointZM:");
$points = [];
for ($i = 0; $i < $pointCount; $i++) {
    $points[] = new Point($i, $i + 1, $i + 2, $i + 3);
}
testEnd();

testStart("Test points Point::is3D():");
foreach ($points as $point) {
    $point->is3D();
}
testEnd();

testStart("Adding points to LineString:");
$lineString = new LineString($points);
testEnd();

testStart("Test LineString::getComponents() points isMeasured():");
foreach ($lineString->getComponents() as $point) {
    $point->isMeasured();
}
testEnd();

testStart("Test LineString::explode(true):");
$res = count($lineString->explode(true));
testEnd($res . ' segment');

testStart("Test LineString::explode():");
$res = count($lineString->explode());
testEnd($res . ' segment');

testStart("Test LineString::length():");
$res = $lineString->length();
testEnd($res);

testStart("Test LineString::greatCircleLength():");
$res = $lineString->greatCircleLength();
testEnd($res);

testStart("Test LineString::haversineLength():");
$res = $lineString->haversineLength();
testEnd($res);

testStart("Test LineString::vincentyLength():");
$res = $lineString->vincentyLength();
testEnd($res);

$shorterLine = new LineString(array_slice($points, 0, min($pointCount, 200)));
testStart("Test LineString::isSimple() (200 points long line):");
$res = $shorterLine->isSimple();
testEnd($res ? 'simple' : 'not simple');

$somePoint = array_slice($points, 0, min($pointCount, 499));
$somePoint[] = $somePoint[0];
$shortClosedLineString = new LineString($somePoint);

$polygon = [];
$rings = [];
testStart("Creating Polygon (50 ring, each has 500 point):");
for ($i = 0; $i < 50; $i++) {
    $rings[] = $shortClosedLineString;
}
$polygon = new Polygon($rings);
testEnd();

$components = [];
testStart("Creating GeometryCollection (50 polygon):");
for ($i = 0; $i < 50; $i++) {
    $components[] = $polygon;
}
$collection = new GeometryCollection($components);
testEnd();

testStart("GeometryCollection::getPoints():");
$res = $collection->getPoints();
testEnd(count($res));




//////////////////////////////////////////////////////////////////////////

testEnd(null, true);

if (microtime(true) - $startTime > MAX_RUN_TIME_SEC) {
    echo "\e[31mTOO SLOW!\e[39m" . PHP_EOL;
    exit(1);
} else {
    echo "\e[32mOK\e[32m" . PHP_EOL;
    exit(0);
}
