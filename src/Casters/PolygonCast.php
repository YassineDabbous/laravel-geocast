<?php

namespace Yaseen\GeoCast\Casters;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Yaseen\GeoCast\Geometries\Polygon;
use Yaseen\GeoCast\MyGeoFactory;

class PolygonCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Polygon
    {
        if (! $value) {
            return null;
        }

        try {
            $wkb = hex2bin($value);
        } catch (\Exception $e) {
            return null;
        }

        if ($wkb === false || $wkb === '') {
            return null;
        }

        try {
            $geom = MyGeoFactory::parser()->parse($wkb);
        } catch (\Exception $e) {
            return null;
        }

        return $geom instanceof Polygon ? $geom : null;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Polygon) {
            throw new InvalidArgumentException("Field {$key} must be an instance of Polygon.");
        }

        return DB::raw("ST_GeomFromText('{$value->toWkt()}', {$value->getSrid()})");
    }
}
