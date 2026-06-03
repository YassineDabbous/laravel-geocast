<?php

namespace Yaseen\GeoCast\Geometries;

class Point implements Geometry
{
    protected float $lat;

    protected float $lng;

    protected int $srid;

    public function __construct($lng, $lat, int $srid = 4326)
    {
        $this->lat = (float) $lat;
        $this->lng = (float) $lng;
        $this->srid = $srid;
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getLng(): float
    {
        return $this->lng;
    }

    public function getSrid(): int
    {
        return $this->srid;
    }

    public function toWkt(): string
    {
        return "POINT({$this->lng} {$this->lat})";
    }

    public function toArray(): array
    {
        return [
            'type' => 'Point',
            'latitude' => $this->lat,
            'longitude' => $this->lng,
            'srid' => $this->srid,
        ];
    }
}
