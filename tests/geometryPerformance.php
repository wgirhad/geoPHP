<?php

require '../vendor/autoload.php';

/**
 * Some very simple performance test for Geometries.
 * Run before and after you modify geometries.
 * For example adding an array_merge() in a heavily used method can decrease performance dramatically.
 *
 * Please note, that this is not a real CI test, it will not fail on performance drops, just helps spotting them.
 *
 * Feel free to add more test methods.
 */

use geoPHP\Geometry\Point;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Polygon;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\geoPHP;

/** Performance test will fail if running takes longer than MAX_RUN_TIME_SEC. (Yes, it's a bit of a dirty method.) */
const MAX_RUN_TIME_SEC = 10;
const PRINTF_TEMPLATE_HEADER = "%-70s %-11s %-10s %-9s %s\n";
const PRINTF_TEMPLATE        = "\r%-70s %.4f sec %7.3f MB %6.2f MB  %s\n";

printf(PRINTF_TEMPLATE_HEADER, 'Test name', 'Time diff', 'Mem diff', 'Mem peak', 'Result');

function testStart(string $message): void
{
    $GLOBALS['testRunTime'] = microtime(true);
    $GLOBALS['testStartMem'] = memory_get_usage();
    $GLOBALS['testName'] = $message ;
    echo "\e[37m" . $message . " …\e[39m";
}

/**
 * @param mixed|null $result
 * @param bool $ready
 */
function testEnd($result = '', bool $ready = false): void
{
    if ($ready) {
        echo "\n";
        printf(
            PRINTF_TEMPLATE,
            "TOTAL:",
            microtime(true) - $GLOBALS['startTime'],
            (memory_get_usage() - $GLOBALS['startMem']) / 1024 / 1024,
            memory_get_peak_usage() / 1024 / 1024,
            $result
        );
    } else {
        printf(
            PRINTF_TEMPLATE,
            substr($GLOBALS['testName'], 0, 70),
            microtime(true) - $GLOBALS['testRunTime'],
            (memory_get_usage() - $GLOBALS['testStartMem']) / 1024 / 1024,
            memory_get_peak_usage() / 1024 / 1024,
            $result
        );
    }
}

function runPerformanceTests(): void
{

    geoPHP::disableGeos();

    $pointCount = 10000;

    testStart("Creating " . $pointCount . " EMPTY Point");
    /** @var Point[] $points */
    $points = [];
    for ($i = 0; $i < $pointCount; $i++) {
        $points[] = new Point();
    }
    testEnd();

    testStart("Creating " . $pointCount . " Point");
    $points = [];
    for ($i = 0; $i < $pointCount; $i++) {
        $points[] = new Point($i, $i + 1);
    }
    testEnd();

    testStart("Creating " . $pointCount . " Point using ::fromArray()");
    $points = [];
    for ($i = 0; $i < $pointCount; $i++) {
        $points[] = Point::fromArray([$i, $i + 1]);
    }
    testEnd();

    testStart("Creating " . $pointCount . " PointZ");
    $points = [];
    for ($i = 0; $i < $pointCount; $i++) {
        $points[] = new Point($i, $i + 1, $i + 2);
    }
    testEnd();

    testStart("Creating " . $pointCount . " PointZM");
    $points = [];
    for ($i = 0; $i < $pointCount; $i++) {
        $points[] = new Point($i, $i + 1, $i + 2, $i + 3);
    }
    testEnd();

    testStart("Test points Point::is3D()");
    foreach ($points as $point) {
        $point->is3D();
    }
    testEnd();

    testStart("Adding points to LineString");
    $lineString = new LineString($points);
    testEnd();

    testStart("Test LineString::invertXY()");
    $lineString->invertXY();
    testEnd();

    testStart("Test LineString::explode(true)");
    $res = count($lineString->explode(true));
    testEnd($res . ' segment');

    testStart("Test LineString::explode()");
    $res = count($lineString->explode());
    testEnd($res . ' segment');

    testStart("Test LineString::length()");
    $res = $lineString->length();
    testEnd($res);

    testStart("Test LineString::greatCircleLength()");
    $res = $lineString->greatCircleLength();
    testEnd($res);

    testStart("Test LineString::haversineLength()");
    $res = $lineString->haversineLength();
    testEnd($res);

    testStart("Test LineString::vincentyLength()");
    $res = $lineString->vincentyLength();
    testEnd($res);

    $shorterLine = new LineString(array_slice($points, 0, min($pointCount, 300)));
    testStart("Test LineString::isSimple() (300 points long line)");
    $res = $shorterLine->isSimple();
    testEnd($res ? 'simple' : 'not simple');

    $ringPoints = array_slice($points, 0, min($pointCount, 500 - 1));
    $ringPoints[] = $ringPoints[0];
    $shortRing = new LineString($ringPoints);
    $rings = unserialize(serialize(array_fill(0, 50, $shortRing)));

    testStart("Creating Polygon (50 ring, each has 500 point)");
    $polygon = new Polygon($rings);
    testEnd();

    $components = unserialize(
        serialize(
            array_merge(
                $points,
                array_fill(0, 50, $lineString),
                array_fill(0, 50, $polygon)
            )
        )
    );

    testStart("Creating GeometryCollection (10000 point + 50 LineString + 50 polygon)");
    $collection = new GeometryCollection($components);
    testEnd();

    testStart("GeometryCollection::getPoints()");
    $res = $collection->getPoints();
    testEnd(count($res));
}

$startTime = microtime(true);
$startMem = memory_get_usage() / 1024 / 1024;

runPerformanceTests();

testEnd(null, true);

if (microtime(true) - $startTime > MAX_RUN_TIME_SEC) {
    echo "\e[31mTOO SLOW!\e[39m\n" . PHP_EOL;
    exit(1);
} else {
    echo "\e[32mOK\e[39m\n" . PHP_EOL;
    exit(0);
}
