<?php

namespace Yaseen\GeoCast\Geometries;

class Polygon implements Geometry
{
    protected array $rings; // Array of arrays of Point objects

    protected int $srid;

    public function __construct(array $rings, int $srid = 4326)
    {
        $this->rings = $rings;
        $this->srid = $srid;
    }

    public function getRings(): array
    {
        return $this->rings;
    }

    public function getSrid(): int
    {
        return $this->srid;
    }

    public function toWkt(): string
    {
        $ringStrings = [];
        foreach ($this->rings as $ring) {
            $pointStrings = [];
            foreach ($ring as $point) {
                if ($point instanceof Point) {
                    $pointStrings[] = "{$point->getLng()} {$point->getLat()}";
                }
            }
            $ringStrings[] = '('.implode(', ', $pointStrings).')';
        }

        return 'POLYGON('.implode(', ', $ringStrings).')';
    }

    public function toArray(): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => array_map(function ($ring) {
                return array_map(fn ($point) => $point instanceof Point
                    ? [$point->getLng(), $point->getLat()]
                    : null, $ring);
            }, $this->rings),
            'srid' => $this->srid,
        ];
    }
}
