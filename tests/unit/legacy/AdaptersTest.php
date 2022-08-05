<?php

namespace geoPHP\Tests\Unit\Legacy;

use geoPHP\geoPHP;
use PHPUnit\Framework\TestCase;

class AdaptersTest extends TestCase
{
    public function testAdapters(): void
    {
        foreach (scandir('tests/input') as $file) {
            $parts = explode('.', $file);
            if ($parts[0]) {
                $format = $parts[1];
                $input = file_get_contents('tests/input/' . $file);
                //echo "\nloading: " . $file . " for format: " . $format;
                $geometry = geoPHP::load($input, $format);

                // Test adapter output and input. Do a round-trip and re-test
                foreach (geoPHP::getAdapterMap() as $adapterKey => $adapterClass) {
                    if ($adapterKey == 'google_geocode') {
                        //Don't test google geocoder regularly. Comment to test
                        continue;
                    }
                    $output = $geometry->out($adapterKey);
                    $this->assertNotNull($output, "Empty output on "  . $adapterKey);
                    if ($output) {
                        $adapterName = 'geoPHP\\Adapter\\' . $adapterClass;
                      /** @var \geoPHP\Adapter\GeoAdapter $adapterLoader */
                        $adapterLoader = new $adapterName();
                        $testGeom1 = $adapterLoader->read($output);
                        $testGeom2 = $adapterLoader->read($testGeom1->out($adapterKey));
                        $this->assertEquals(
                            $testGeom1->out('wkt'),
                            $testGeom2->out('wkt'),
                            "Mismatched adapter output in " . $adapterClass  . ' (test file: ' . $file . ')'
                        );
                    }
                }

                // Test to make sure adapter work the same wether GEOS is ON or OFF
                // Cannot test methods if GEOS is not intstalled
                if (!geoPHP::isGeosInstalled()) {
                    return;
                }

                foreach (geoPHP::getAdapterMap() as $adapterKey => $adapterClass) {
                    if ($adapterKey === 'google_geocode') {
                      //Don't test google geocoder regularly. Comment to test
                        continue;
                    }
                    // Turn GEOS on
                    geoPHP::enableGeos();

                    $output = $geometry->out($adapterKey);
                    if ($output) {
                        $adapterName = 'geoPHP\\Adapter\\' . $adapterClass;
                        $adapterLoader = new $adapterName();

                        $testGeom1 = $adapterLoader->read($output);

                        // Turn GEOS off
                        geoPHP::disableGeos();

                        $testGeom2 = $adapterLoader->read($output);

                        // Turn GEOS back On
                        geoPHP::enableGeos();

                        // Check to make sure a both are the same with geos and without
                        $msg = "Mismatched adapter output between GEOS and NORM in " . $adapterClass
                            . ' (test file: ' . $file . ')';
                        $this->assertEquals($testGeom1->out('wkt'), $testGeom2->out('wkt'), $msg);
                    }
                }
            }
        }
    }
}
