<?php

namespace Yaseen\GeoCast\Casters;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\MyGeoFactory;

class PointCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Point
    {
        if (! $value) {
            return null;
        }

        try {
            $wkb = hex2bin($value);
        } catch (\Throwable $e) {
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

        return $geom instanceof Point ? $geom : null;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Point) {
            throw new InvalidArgumentException("Field {$key} must be an instance of Point.");
        }

        $wkt = str_replace("'", "''", $value->toWkt());
        $srid = (int) $value->getSrid();

        return DB::raw("ST_GeomFromText('{$wkt}', {$srid})");
    }
}
