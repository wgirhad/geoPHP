<?php

require '../vendor/autoload.php';

use geoPHP\Geometry\Geometry;
use geoPHP\geoPHP;

function runTest(): void
{
    set_time_limit(0);

    set_error_handler("FailOnError");

    header("Content-type: text");

    if (geoPHP::isGeosInstalled()) {
        echo "GEOS is installed.\n";
    } else {
        echo "GEOS is not installed.\n";
    }
    $isVerbose = getenv("VERBOSE") == 1 || getopt('v');

    $start = microtime(true);
    foreach (scandir('./input') as $file) {
        $parts = explode('.', $file);
        if ($parts[0]) {
            $startFile = microtime(true);
            $format = $parts[1];
            $value = file_get_contents('./input/' . $file);
            echo '-- Testing ' . $file . "\n";

            $geometry = geoPHP::load($value, $format);

            echo $isVerbose ? "---- Methods\n" : '';
            testGeometryMethods($geometry);

            echo $isVerbose ? "---- Adapters\n" : '';
            testAdapters($geometry, $format, $value);

            echo $isVerbose ? "---- Compare methods normal ←→ Geos\n" : '';
            testGeosMethods($geometry);

            echo $isVerbose ? "---- Detection\n" : '';
            testDetection($value, $format);

            echo '   ' . sprintf('%.3f', microtime(true) - $startFile) . " s\n";
        }
    }

    echo "\nSuccessfully completed under " . sprintf('%.3f', microtime(true) - $start)
        . " seconds, using maximum " . sprintf('%.3f', memory_get_peak_usage() / 1024 / 1024) . " MB\n";
    echo "\e[32mPASS\e[39m\n";
}

/**
 * @param Geometry $geometry
 */
function testGeometryMethods(Geometry $geometry): void
{
    // Test common functions
    $geometry->area();
    $geometry->boundary();
    $geometry->envelope();
    $geometry->getBBox();
    $geometry->centroid();
    $geometry->length();
    $geometry->greatCircleLength();
    $geometry->haversineLength();
    $geometry->x();
    $geometry->y();
    $geometry->numGeometries();
    $geometry->geometryN(1);
    $geometry->startPoint();
    $geometry->endPoint();
    $geometry->isRing();
    $geometry->isClosed();
    $geometry->numPoints();
    $geometry->pointN(1);
    $geometry->exteriorRing();
    $geometry->numInteriorRings();
    $geometry->interiorRingN(1);
    $geometry->coordinateDimension();
    $geometry->geometryType();
    $geometry->getSRID();
    $geometry->setSRID(4326);
    $geometry->is3D();
    $geometry->isMeasured();
    $geometry->isEmpty();
    $geometry->coordinateDimension();
    $geometry->isSimple();
    $geometry->equals($geometry);
    $geometry->asText();
    $geometry->asBinary();

    // Aliases
    $geometry->getCentroid();
    $geometry->getArea();
    $geometry->getX();
    $geometry->getY();
    $geometry->geos();
    $geometry->SRID();

    // GEOS only functions
    try {
        $geometry->flushGeosCache();
        $geometry->getGeos();
        $geometry->contains($geometry);
        $geometry->overlaps($geometry);
        $geometry->pointOnSurface();
        $geometry->equalsExact($geometry);
        $geometry->relate($geometry);
        $geometry->checkValidity();
        $geometry->buffer(10);
        $geometry->intersection($geometry);
        $geometry->convexHull();
        $geometry->difference($geometry);
        $geometry->symDifference($geometry);
        $geometry->union($geometry);
        $geometry->simplify(0);
        $geometry->simplify(10);
        $geometry->simplify(100);
        $geometry->disjoint($geometry);
        $geometry->touches($geometry);
        $geometry->intersects($geometry);
        $geometry->crosses($geometry);
        $geometry->within($geometry);
        $geometry->covers($geometry);
        $geometry->coveredBy($geometry);
        $geometry->hausdorffDistance($geometry);
        // distance() is supported by geoPHP but too slow to test with each input
        $geometry->distance($geometry);
    } catch (\Exception $e) {
        if (getenv("VERBOSE") == 1 || getopt('v')) {
            echo "\e[33m\t" . $e->getMessage() . "\e[39m\n";
        }
    }
}

/**
 * @param Geometry $geometry
 * @param string $format
 * @param string $input
 */
function testAdapters(Geometry $geometry, string $format, string $input): void
{
    // Test adapter output and input. Do a round-trip and re-test
    foreach (geoPHP::getAdapterMap() as $adapterKey => $adapterClass) {
        if ($adapterKey == 'google_geocode') {
            //Don't test google geocoder regularily. Uncomment to test
            continue;
        }
        if (getenv("VERBOSE") == 1 || getopt('v')) {
            echo "\t {$adapterKey}\n";
        }
        $output = $geometry->out($adapterKey);
        if ($output) {
            $adapterName = 'geoPHP\\Adapter\\' . $adapterClass;

            /** @var \geoPHP\Adapter\GeoAdapter */
            $adapterLoader = new $adapterName();

            try {
                $testGeom1 = $adapterLoader->read($output);
                $testGeom2 = $adapterLoader->read($testGeom1->out($adapterKey));
            } catch (Exception $e) {
                echo "\e[31m\tException when reading output of " . $adapterClass . " adapter:\n";
                echo $e->getMessage() . "\n Input: \n" . $input  . "\n Output: \n" . $output . "\n";
                echo "\e[39m\n";
                exit(1);
            }

            if ($testGeom1->out('wkt') != $testGeom2->out('wkt')) {
                echo "\e[33m\tMismatched adapter output in " . $adapterClass . "\e[39m\n";
            }
        } else {
            echo "\e[33m\tEmpty output on "  . $adapterKey . "\e[39m\n";
        }
    }

    // Test to make sure adapter work the same wether GEOS is ON or OFF
    // Cannot test methods if GEOS is not intstalled
    if (!geoPHP::isGeosInstalled()) {
        return;
    }
    if (getenv("VERBOSE") == 1 || getopt('v')) {
        echo "Testing with GEOS\n";
    }
    foreach (geoPHP::getAdapterMap() as $adapterKey => $adapterClass) {
        if ($adapterKey == 'google_geocode') {
            //Don't test google geocoder regularily. Uncomment to test
            continue;
        }

        if (getenv("VERBOSE") == 1 || getopt('v')) {
            echo ' ' . $adapterClass . "\n";
        }
        // Turn GEOS on
        geoPHP::enableGeos();

        try {
            $output = $geometry->out($adapterKey);
            if ($output) {
                $adapterName = 'geoPHP\\Adapter\\' . $adapterClass;
                /** @var \geoPHP\Adapter\GeoAdapter */
                $adapterLoader = new $adapterName();

                $testGeomGeos = $adapterLoader->read($output);

                // Turn GEOS off
                geoPHP::disableGeos();

                $testGeomNorm = $adapterLoader->read($output);

                // Check to make sure a both are the same with geos and without
                if ($testGeomGeos->out('wkt') != $testGeomNorm->out('wkt')) {
                    $f = fopen('testGeomgeos.wkt', 'w+');
                    fwrite($f, $testGeomGeos->out('wkt'));
                    fclose($f);
                    $f = fopen('testGeomnorm.wkt', 'w+');
                    fwrite($f, $testGeomNorm->out('wkt'));
                    fclose($f);
                    $f = fopen('testGeomdump.geometry', 'w+');
                    fwrite($f, print_r($testGeomNorm, true));
                    fclose($f);
                    echo "\e[31m\tMismatched adapter output between GEOS and NORM in {$adapterClass}. ";
                    echo "Output written to files.\e[39m\n";
                    exit(1);
                }

                // Turn GEOS back on
                geoPHP::enableGeos();
            }
        } catch (\geoPHP\Exception\UnsupportedMethodException $e) {
            if (getenv("VERBOSE") == 1 || getopt('v')) {
                echo "\e[33m\t" . $e->getMessage() . "\e[39m\n";
            }
        }
    }
}


function testGeosMethods(Geometry $geometry): void
{
    // Cannot test methods if GEOS is not intstalled
    if (!geoPHP::isGeosInstalled()) {
        return;
    }

    $methods = [
        'boundary',
        'envelope',
        'getBoundingBox',
        'x',
        'y',
        'z',
        'm',
        'startPoint',
        'endPoint',
        'isRing',
        'isClosed',
        'numPoints',
        'centroid',
        'length',
        'isEmpty',
        'isSimple'
    ];

    foreach ($methods as $method) {
        echo getenv("VERBOSE") == 1 ? ("\t" . $method . "\n") : '';
        try {
            // Turn GEOS on
            geoPHP::enableGeos();

            /** @var \geoPHP\Geometry\Geometry */
            $geosResult = $geometry->$method();

            // Turn GEOS off
            geoPHP::disableGeos();

            /** @var \geoPHP\Geometry\Geometry */
            $normResult = $geometry->$method();

            // Turn GEOS back On
            geoPHP::enableGeos();

            $geosType = gettype($geosResult);
            $normType = gettype($normResult);

            if ($geosType != $normType) {
                echo "\e[33mType mismatch on " . $method . "\e[39m\n";
                continue;
            }

            // Now check base on type
            if ($geosType == 'object' && !$normResult->isEmpty()) {
                $hausDist = $geosResult->hausdorffDistance(geoPHP::load($normResult->out('wkt'), 'wkt'));

                // Get the length of the diagonal of the bbox - this is used to scale the haustorff distance
                // Using Pythagorean theorem
                $bBox = $geosResult->getBBox();
                $scale = sqrt(pow($bBox['maxy'] - $bBox['miny'], 2) + pow($bBox['maxx'] - $bBox['minx'], 2));

                // The difference in the output of GEOS and native-PHP methods
                // should be less than 0.5 scaled haustorff units
                if ($scale !== 0.0 && $hausDist / $scale > 0.5) {
                    echo "\e[31mOutput mismatch on " . $method . "\e[39m\n";
                    echo 'GEOS : ' . $geosResult->out('wkt') . "\n";
                    echo 'NORM : ' . $normResult->out('wkt') . "\n";
                    exit(1);
                }
            }

            if (($geosType == 'boolean' || $geosType == 'string') && $geosResult !== $normResult) {
                echo "\e[31mOutput mismatch on " . $method . "\e[39m\n";
                echo 'GEOS : ' . (string) $geosResult . "\n";
                echo 'NORM : ' . (string) $normResult . "\n";
                exit(1);
            }
        } catch (\geoPHP\Exception\UnsupportedMethodException $e) {
            if (getenv("VERBOSE") == 1 || getopt('v')) {
                echo "\e[33m\t" . $e->getMessage() . "\e[39m\n";
            }
        }

        //@@TODO: Run tests for output of types arrays and float
        //@@TODO: centroid function is non-compliant for collections and strings
    }
}

function testDetection(string $value, string $format): void
{
    $detected = geoPHP::detectFormat($value);
    if ($detected != $format) {
        if ($detected) {
            echo 'detected as ' . $detected . "\n";
        } else {
            echo "format not detected\n";
        }
    }
    // Make sure it loads using auto-detect
    geoPHP::load($value);
}

function FailOnError(int $errorLevel, string $errorMessage, string $errorFile, int $errorLine): ?bool
{
    echo "$errorLevel: $errorMessage in $errorFile on line $errorLine\n";
    echo "\e[31mFAIL\e[39m\n";
    exit(1);
}

runTest();
