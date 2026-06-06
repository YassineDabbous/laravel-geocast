<?php

namespace Yaseen\GeoCast\Casters;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Yaseen\GeoCast\Geometries\MultiPolygon;
use Yaseen\GeoCast\MyGeoFactory;

class MultiPolygonCast implements CastsAttributes
{
    protected string $type;

    public function __construct(string $type = 'geometry')
    {
        $this->type = strtolower($type);
    }

    public function get($model, string $key, $value, array $attributes): ?MultiPolygon
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

        return $geom instanceof MultiPolygon ? $geom : null;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof MultiPolygon) {
            throw new InvalidArgumentException("Field {$key} must be an instance of MultiPolygon.");
        }

        $wkt = str_replace("'", "''", $value->toWkt());
        $srid = (int) $value->getSrid();

        if ($this->type === 'geography') {
            return DB::raw("ST_GeogFromText('SRID={$srid};{$wkt}')");
        }

        return DB::raw("ST_GeomFromText('{$wkt}', {$srid})");
    }
}
