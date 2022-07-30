<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Geometry;

/**
 * EWKB (Extended Well Known Binary) Adapter
 */
class EWKB extends WKB
{
    public function write(Geometry $geometry, bool $writeAsHex = false, bool $bigEndian = false): string
    {
        $this->SRID = $geometry->getSRID();
        $this->hasSRID = $this->SRID !== null;
        return parent::write($geometry, $writeAsHex, $bigEndian);
    }

    protected function writeType(Geometry $geometry, bool $writeSRID = false): string
    {
        return parent::writeType($geometry, true);
    }
}
