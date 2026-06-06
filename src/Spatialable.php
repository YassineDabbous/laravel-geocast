<?php

namespace Yaseen\GeoCast;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Yaseen\GeoCast\Casters\MultiPolygonCast;
use Yaseen\GeoCast\Casters\PointCast;
use Yaseen\GeoCast\Casters\PolygonCast;
use Yaseen\GeoCast\Geometries\Geometry;

trait Spatialable
{
    public function scopeWithinDistanceTo(Builder $query, string $column, Geometry $geometry, int $distance, ?string $typeOverride = null): void
    {
        $this->validateSpatialAttribute($column);

        $type = $typeOverride ?? $this->getSpatialType($column);

        if ($type === 'geography') {
            $query->whereRaw(
                "ST_DWithin({$column}, ST_GeogFromText(?), ?)",
                [
                    "SRID={$geometry->getSrid()};".$geometry->toWkt(),
                    $distance,
                ]
            );

            return;
        }

        $query->whereRaw(
            "ST_DWithin({$column}::geography, ST_GeomFromText(?, ?)::geography, ?)",
            [
                $geometry->toWkt(),
                $geometry->getSrid(),
                $distance,
            ]
        );
    }

    public function scopeOrderByDistanceTo(Builder $query, string $column, Geometry $geometry, string $direction = 'asc', ?string $typeOverride = null): void
    {
        $this->validateSpatialAttribute($column);

        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $type = $typeOverride ?? $this->getSpatialType($column);

        if ($type === 'geography') {
            $query->orderByRaw(
                "ST_Distance({$column}, ST_GeogFromText(?)) {$direction}",
                [
                    "SRID={$geometry->getSrid()};".$geometry->toWkt(),
                ]
            );

            return;
        }

        $query->orderByRaw(
            "ST_Distance({$column}::geography, ST_GeomFromText(?, ?)::geography) {$direction}",
            [
                $geometry->toWkt(),
                $geometry->getSrid(),
            ]
        );
    }

    public function scopeContainsGeometry(Builder $query, string $column, Geometry $geometry, ?string $typeOverride = null): void
    {
        $this->validateSpatialAttribute($column);

        $type = $typeOverride ?? $this->getSpatialType($column);

        if ($type === 'geography') {
            throw new RuntimeException('ST_Contains is only supported for geometry columns.');
        }

        $query->whereRaw(
            "ST_Contains({$column}, ST_GeomFromText(?, ?))",
            [
                $geometry->toWkt(),
                $geometry->getSrid(),
            ]
        );
    }

    public function scopeWithinGeometry(Builder $query, string $column, Geometry $geometry, ?string $typeOverride = null): void
    {
        $this->validateSpatialAttribute($column);

        $type = $typeOverride ?? $this->getSpatialType($column);

        if ($type === 'geography') {
            throw new RuntimeException('ST_Within is only supported for geometry columns.');
        }

        $query->whereRaw(
            "ST_Within({$column}, ST_GeomFromText(?, ?))",
            [
                $geometry->toWkt(),
                $geometry->getSrid(),
            ]
        );
    }

    public function scopeIntersectsWith(Builder $query, string $column, Geometry $geometry, ?string $typeOverride = null): void
    {
        $this->validateSpatialAttribute($column);

        $type = $typeOverride ?? $this->getSpatialType($column);

        if ($type === 'geography') {
            throw new RuntimeException('ST_Intersects is only supported for geometry columns.');
        }

        $query->whereRaw(
            "ST_Intersects({$column}, ST_GeomFromText(?, ?))",
            [
                $geometry->toWkt(),
                $geometry->getSrid(),
            ]
        );
    }

    public function scopeWithArea(Builder $query, string $column, ?string $typeOverride = null): void
    {
        $this->validateSpatialAttribute($column);

        $type = $typeOverride ?? $this->getSpatialType($column);

        if ($type === 'geography') {
            $query->addSelect(DB::raw("ST_Area({$column}) as {$column}_area"));

            return;
        }

        $query->addSelect(DB::raw("ST_Area({$column}::geography) as {$column}_area"));
    }

    private function getSpatialType(string $column): string
    {
        $casts = $this->getCasts();

        $cast = $casts[$column] ?? null;

        if (! $cast) {
            return 'geometry';
        }

        $parts = explode(':', $cast);

        return strtolower($parts[1] ?? 'geometry');
    }

    private function validateSpatialAttribute(string $column): void
    {
        $casts = $this->getCasts();

        $validCasts = [
            PointCast::class,
            PolygonCast::class,
            MultiPolygonCast::class,
        ];

        if (! array_key_exists($column, $casts)) {
            throw new InvalidArgumentException("The column '{$column}' is not registered with a valid Spatial Cast class.");
        }

        $castClass = explode(':', $casts[$column])[0];

        if (! in_array($castClass, $validCasts, true)) {
            throw new InvalidArgumentException("The column '{$column}' is not registered with a valid Spatial Cast class.");
        }
    }
}
