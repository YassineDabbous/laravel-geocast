<?php

namespace Yaseen\GeoCast\Geometries;

class MultiPolygon implements Geometry
{
    protected array $polygons;

    protected int $srid;

    public function __construct(array $polygons, int $srid = 4326)
    {
        $this->polygons = $polygons;
        $this->srid = $srid;
    }

    public function getPolygons(): array
    {
        return $this->polygons;
    }

    public function getSrid(): int
    {
        return $this->srid;
    }

    public function toWkt(): string
    {
        $polygonStrings = [];
        foreach ($this->polygons as $polygon) {
            $polygonStrings[] = $polygon->toWkt();
        }

        return 'MULTIPOLYGON('.implode(', ', $polygonStrings).')';
    }

    public function toArray(): array
    {
        return [
            'type' => 'MultiPolygon',
            'coordinates' => array_map(fn ($polygon) => $polygon->toArray()['coordinates'], $this->polygons),
            'srid' => $this->srid,
        ];
    }
}
