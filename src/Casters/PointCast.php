<?php

namespace Yaseen\GeoCast\Casts\Spatial\Casters;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Yaseen\GeoCast\Casts\Spatial\MyGeoFactory;
use Yaseen\GeoCast\Casts\Spatial\Geometries\Point;

class PointCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Point
    {
        if (!$value || bin2hex($value) == 'e6100000010100000000000000000000000000000000000000') {
            return new Point(0, 0);
        }

        $wkb = substr($value, 4); // Strip SRID
        $geom = MyGeoFactory::parser()->parse($wkb);

        return $geom instanceof Point ? $geom : null;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) return null;

        if (!$value instanceof Point) {
            throw new InvalidArgumentException("Field {$key} must be an instance of Point.");
        }

        return DB::raw("ST_GeomFromText('{$value->toWkt()}', {$value->getSrid()}, 'axis-order=long-lat')");
    }
}