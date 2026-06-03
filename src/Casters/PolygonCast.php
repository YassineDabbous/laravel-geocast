<?php

namespace Yaseen\GeoCast\Casts\Spatial\Casters;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Yaseen\GeoCast\Casts\Spatial\MyGeoFactory;
use Yaseen\GeoCast\Casts\Spatial\Geometries\Polygon;

class PolygonCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Polygon
    {
        if (!$value) return null;

        $wkb = substr($value, 4); // Strip SRID
        $geom = MyGeoFactory::parser()->parse($wkb);

        return $geom instanceof Polygon ? $geom : null;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) return null;

        if (!$value instanceof Polygon) {
            throw new InvalidArgumentException("Field {$key} must be an instance of Polygon.");
        }

        return DB::raw("ST_GeomFromText('{$value->toWkt()}', {$value->getSrid()}, 'axis-order=long-lat')");
    }
}