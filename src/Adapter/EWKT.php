<?php

namespace geoPHP\Adapter;

use geoPHP\Exception\IOException;
use geoPHP\Geometry\Geometry;

/**
 * EWKT (Extended Well Known Text) Adapter
 */
class EWKT extends WKT
{
    /**
     * Serialize geometries into an EWKT string.
     *
     * @param Geometry $geometry
     *
     * @throws IOException Throwed if the given Geometry is not supported by the EWKT writer.
     *
     * @return string The Extended-WKT string representation of the input geometries.
     */
    public function write(Geometry $geometry): string
    {
        $srid = $geometry->getSRID();
        if ($srid) {
            return 'SRID=' . $srid . ';' . $geometry->out('wkt');
        } else {
            return $geometry->out('wkt');
        }
    }
}
