<?php

namespace geoPHP\Exception;

use RuntimeException;

/**
 * Exception thrown if a geometry doesn't meet the basic requirements of validity
 * Eg. a LineString with only one point
 */
class InvalidGeometryException extends RuntimeException implements Exception
{
}
