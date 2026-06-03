<?php

namespace Yaseen\GeoCast;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Yaseen\GeoCast\Geometries\Geometry;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;
use Yaseen\GeoCast\Casters\PointCast;
use Yaseen\GeoCast\Casters\PolygonCast;
use InvalidArgumentException;

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
            ST_SRID({$column}, ?),
            ST_GeomFromText(?, ?)
        ) <= ?", [$geometry->getSrid(), $geometry->toWkt(), $geometry->getSrid(), $distance]);
    }

    /**
     * Scope: Order records by distance to a specific target geometry.
     */
    public function scopeOrderByDistanceTo(Builder $query, string $column, Geometry $geometry, string $direction = 'asc'): void
    {
        $this->validateSpatialAttribute($column);
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $query->orderByRaw("ST_Distance(
            ST_SRID({$column}, ?),
            ST_GeomFromText(?, ?)
        ) {$direction}", [$geometry->getSrid(), $geometry->toWkt(), $geometry->getSrid()]);
    }

    /**
     * Scope: Find polygons in this table that completely contain the given geometry (e.g., a Point).
     */
    public function scopeContainsGeometry(Builder $query, string $column, Geometry $geometry): void
    {
        $this->validateSpatialAttribute($column);

        $query->whereRaw("ST_Contains(
            ST_SRID({$column}, ?),
            ST_GeomFromText(?, ?)
        )", [$geometry->getSrid(), $geometry->toWkt(), $geometry->getSrid()]);
    }

    /**
     * Scope: Find records in this table that sit completely inside a target polygon geometry.
     */
    public function scopeWithinGeometry(Builder $query, string $column, Geometry $geometry): void
    {
        $this->validateSpatialAttribute($column);

        $query->whereRaw("ST_Within(
            ST_SRID({$column}, ?),
            ST_GeomFromText(?, ?)
        )", [$geometry->getSrid(), $geometry->toWkt(), $geometry->getSrid()]);
    }

    /**
     * Scope: Find shapes that intersect, overlap, or touch a target polygon.
     */
    public function scopeIntersectsWith(Builder $query, string $column, Geometry $geometry): void
    {
        $this->validateSpatialAttribute($column);

        $query->whereRaw("ST_Intersects(
            ST_SRID({$column}, ?),
            ST_GeomFromText(?, ?)
        )", [$geometry->getSrid(), $geometry->toWkt(), $geometry->getSrid()]);
    }

    /**
     * Scope: Appends a `{column}_area` attribute to your model query in square meters.
     */
    public function scopeWithArea(Builder $query, string $column): void
    {
        $this->validateSpatialAttribute($column);

        $query->addSelect([
            "{$column}_area" => DB::raw("ST_Area({$column}::geography)")
        ]);
    }

    /**
     * Private helper to enforce that the column being queried is actually 
     * registered as a spatial cast in the Eloquent model.
     */
    private function validateSpatialAttribute(string $column): void
    {
        $casts = $this->getCasts();
        
        $validCasts = [PointCast::class, PolygonCast::class];

        if (!array_key_exists($column, $casts) || !in_array($casts[$column], $validCasts)) {
            throw new InvalidArgumentException("The column '{$column}' is not registered with a valid Spatial Cast class.");
        }
    }
}