<?php

declare(strict_types=1);

namespace geoPHP\Geometry;

use geoPHP\Exception\Exception;
use geoPHP\Exception\InvalidGeometryException;
use geoPHP\geoPHP;

use function array_merge;

/**
 * GeometryCollection: A heterogeneous collection of geometries
 */
class GeometryCollection extends MultiGeometry
{
    /**
     * Checks and stores geometry components.
     *
     * @param Geometry[] $components Array of geometries. Components of GeometryCollection can be
     *                               any of valid Geometry types, including empty geometry.
     *
     * @throws InvalidGeometryException
     */
    public function __construct(array $components = [])
    {
        parent::__construct($components, Geometry::class);
    }

    public function geometryType(): string
    {
        return Geometry::GEOMETRY_COLLECTION;
    }

    /**
     * @return int Returns the highest spatial dimension of components
     */
    public function dimension(): int
    {
        $dimension = 0;
        foreach ($this->getComponents() as $component) {
            if ($component->dimension() > $dimension) {
                $dimension = $component->dimension();
            }
        }
        return $dimension;
    }

    /**
     * Not valid for this geometry type
     * @return null
     */
    public function isSimple(): ?bool
    {
        return null;
    }

    /**
     * In a GeometryCollection, the centroid is equal to the centroid of
     * the set of component Geometries of highest dimension
     * (since the lower-dimension geometries contribute zero "weight" to the centroid).
     *
     * @return Point
     * @throws Exception
     */
    public function centroid(): Point
    {
        if ($this->isEmpty()) {
            return new Point();
        }

        if ($this->getGeos()) {
            // @codeCoverageIgnoreStart
            return geoPHP::geosToGeometry($this->getGeos()->centroid());
            // @codeCoverageIgnoreEnd
        }

        $geometries = $this->explodeGeometries();

        $highestDimension = 0;
        foreach ($geometries as $geometry) {
            if ($geometry->dimension() > $highestDimension) {
                $highestDimension = $geometry->dimension();
            }
            if ($highestDimension === 2) {
                break;
            }
        }

        $highestDimensionGeometries = [];
        foreach ($geometries as $geometry) {
            if ($geometry->dimension() === $highestDimension) {
                $highestDimensionGeometries[] = $geometry;
            }
        }

        $reducedGeometry = geoPHP::geometryReduce($highestDimensionGeometries);
        if ($reducedGeometry->geometryType() === Geometry::GEOMETRY_COLLECTION) {
            throw new \Exception('Internal error: GeometryCollection->centroid() calculation failed.');
        }
        return $reducedGeometry->centroid();
    }

    /**
     * Returns every sub-geometry as a multidimensional array
     *
     * Because geometryCollections are heterogeneous we need to specify which type of geometries they contain.
     * We need to do this because, for example, there would be no way to tell the difference between a
     * MultiPoint or a LineString, since they share the same structure (collection
     * of points). So we need to call out the type explicitly.
     *
     * @return array<array{type: string, components: array<mixed>}>
     */
    public function asArray(): array
    {
        $array = [];
        foreach ($this->getComponents() as $component) {
            $array[] = [
                    'type'       => $component->geometryType(),
                    'components' => $component->asArray(),
            ];
        }
        return $array;
    }

    /**
     * @return Geometry[]
     */
    public function explodeGeometries(): array
    {
        $geometries = [];
        foreach ($this->components as $component) {
            if ($component instanceof GeometryCollection) {
                foreach ($component->explodeGeometries() as $subComponent) {
                    $geometries[] = $subComponent;
                }
            } else {
                $geometries[] = $component;
            }
        }
        return $geometries;
    }

    // Not valid for this geometry
    public function boundary(): ?Geometry
    {
        return null;
    }
}
