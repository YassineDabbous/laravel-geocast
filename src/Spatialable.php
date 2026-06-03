<?php

namespace Yaseen\GeoCast;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Yaseen\GeoCast\Casters\PointCast;
use Yaseen\GeoCast\Casters\PolygonCast;
use Yaseen\GeoCast\Geometries\Geometry;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;

trait Spatialable
{
    /**
     * Scope: Filter records within a specific distance of a target geometry.
     * Works uniformly whether the column is a Point or Polygon.
     */
    public function scopeWithinDistanceTo(Builder $query, string $column, Geometry $geometry, int $distance): void
    {
        $this->validateSpatialAttribute($column);

        $query->whereRaw("ST_Distance(
            {$column}::geography,
            ST_GeomFromText(?, ?)::geography
        ) <= ?", [$geometry->toWkt(), $geometry->getSrid(), $distance]);
    }

    public function scopeOrderByDistanceTo(Builder $query, string $column, Geometry $geometry, string $direction = 'asc'): void
    {
        $this->validateSpatialAttribute($column);
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $query->orderByRaw("ST_Distance(
            {$column}::geography,
            ST_GeomFromText(?, ?)::geography
        ) {$direction}", [$geometry->toWkt(), $geometry->getSrid()]);
    }

    public function scopeContainsGeometry(Builder $query, string $column, Geometry $geometry): void
    {
        $this->validateSpatialAttribute($column);

        $query->whereRaw("ST_Contains(
            {$column},
            ST_GeomFromText(?, ?)
        )", [$geometry->toWkt(), $geometry->getSrid()]);
    }

    public function scopeWithinGeometry(Builder $query, string $column, Geometry $geometry): void
    {
        $this->validateSpatialAttribute($column);

        $query->whereRaw("ST_Within(
            {$column},
            ST_GeomFromText(?, ?)
        )", [$geometry->toWkt(), $geometry->getSrid()]);
    }

    public function scopeIntersectsWith(Builder $query, string $column, Geometry $geometry): void
    {
        $this->validateSpatialAttribute($column);

        $query->whereRaw("ST_Intersects(
            {$column},
            ST_GeomFromText(?, ?)
        )", [$geometry->toWkt(), $geometry->getSrid()]);
    }

    /**
     * Scope: Appends a `{column}_area` attribute to your model query in square meters.
     */
    public function scopeWithArea(Builder $query, string $column): void
    {
        $this->validateSpatialAttribute($column);

        $query->addSelect(DB::raw("ST_Area({$column}::geography) as {$column}_area"));
    }

    /**
     * Private helper to enforce that the column being queried is actually
     * registered as a spatial cast in the Eloquent model.
     */
    private function validateSpatialAttribute(string $column): void
    {
        $casts = $this->getCasts();

        $validCasts = [PointCast::class, PolygonCast::class];

        if (! array_key_exists($column, $casts) || ! in_array($casts[$column], $validCasts)) {
            throw new InvalidArgumentException("The column '{$column}' is not registered with a valid Spatial Cast class.");
        }
    }
}
