<?php

require_once dirname(__FILE__) . '../vendor/autoload.php';

use geoPHP\Geometry\Geometry;
use geoPHP\geoPHP;

function run_test(): void
{

    header("Content-type: text");

    // Your database test table should contain 3 columns: name (text), type (text), geom (geometry)
    // CREATE EXTENSION postgis;
    // CREATE TABLE geophp (id serial, name TEXT, type TEXT, geom Geometry, PRIMARY KEY (id));

    $host =     'localhost';
    $database = 'test';
    $table =    'geophp';
    $user =     '';
    $pass =     '';

    $connection = pg_connect("host=$host dbname=$database user=$user password=$pass");

    if (!$connection) {
        die("Failed to connect to database. Test has been aborted.\n");
    }

    if (@pg_query("SELECT '" . $table . "'::regclass") === false) {
        die("Table \"" . $table . "\" doesn't exists.\n");
    }

    // Truncate
    pg_query($connection, "TRUNCATE TABLE $table");

    // Working with PostGIS and EWKB
    // ----------------------------

    foreach (scandir('./input') as $file) {
        $parts = explode('.', $file);
        if ($parts[0]) {
            $name = $file;
            $format = $parts[1];
            $value = file_get_contents('./input/' . $file);
            echo '---- Testing ' . $file . "\n";
            flush();
            $geometry = geoPHP::load($value, $format);
            test_postgis($table, $name, $format, $geometry, $connection, 'wkb');
            $geometry->setSRID(4326);
            test_postgis($table, $name, $format, $geometry, $connection, 'ewkb');
        }
    }
    echo "Testing Done!\n";
}

/**
 * @param string $table
 * @param string $name
 * @param string $type
 * @param Geometry $geom
 * @param resource|\PgSql\Connection $connection
 * @param string $format
 * @throws Exception
 */
function test_postgis(string $table, string $name, string $type, Geometry $geom, $connection, string $format): void
{

    // Let's insert into the database using GeomFromWKB
    $insertString = pg_escape_bytea($geom->out($format));

    pg_query(
        $connection,
        "INSERT INTO $table (name, type, geom) values ('$name', '$type', ST_GeomFromWKB('$insertString'))"
    );

    // SELECT using asBinary PostGIS
    $result = pg_fetch_all(
        pg_query(
            $connection,
            "SELECT ST_AsBinary(geom) as geom FROM $table WHERE name='$name'"
        )
    ) ?: [];

    foreach ($result as $item) {
        $wkb = pg_unescape_bytea($item['geom']); // Make sure to unescape the hex blob
        $geom = geoPHP::load($wkb, $format); // We now a full geoPHP Geometry object
    }

    // SELECT and INSERT directly, with no wrapping functions
    $result = pg_fetch_all(pg_query($connection, "SELECT geom as geom FROM $table WHERE name='$name'")) ?: [];
    foreach ($result as $item) {
        $geom = geoPHP::load($item['geom'], $format, true); // We now have a geoPHP Geometry

        // Let's re-insert directly into postGIS
        $insertString = $geom->out($format, true);
        pg_query($connection, "INSERT INTO $table (name, type, geom) values ('$name', '$type', '$insertString')");
    }

    // SELECT and INSERT using as EWKT (ST_GeomFromEWKT and ST_AsEWKT)
    $result = pg_fetch_all(
        pg_query(
            $connection,
            "SELECT ST_AsEWKT(geom) as geom FROM $table WHERE name='$name'"
        )
    ) ?: [];

    foreach ($result as $item) {
        $wkt = $item['geom']; // Make sure to unescape the hex blob
        $geom = geoPHP::load($item['geom'], 'ewkt'); // We now a full geoPHP Geometry object

        // Let's re-insert directly into postGIS
        $insertString = $geom->out('ewkt');
        pg_query(
            $connection,
            "INSERT INTO $table (name, type, geom) values ('$name', '$type', ST_GeomFromEWKT('$insertString'))"
        );
    }
}

run_test();
