<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yaseen\GeoCast\Casters\PointCast;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;
use Yaseen\GeoCast\Spatialable;

beforeEach(function () {
    Schema::dropIfExists('test_geo_locations');

    DB::statement(<<<'SQL'
        CREATE TABLE test_geo_locations (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            location geography(Point, 4326) NOT NULL,
            boundary geography(Polygon, 4326)
        )
    SQL);

    DB::insert(<<<'SQL'
        INSERT INTO test_geo_locations (name, location, boundary)
        VALUES ('center', ST_GeogFromText('SRID=4326;POINT(0 0)'), NULL)
    SQL);

    DB::insert(<<<'SQL'
        INSERT INTO test_geo_locations (name, location, boundary)
        VALUES ('near-point', ST_GeogFromText('SRID=4326;POINT(2 2)'), NULL)
    SQL);

    DB::insert(<<<'SQL'
        INSERT INTO test_geo_locations (name, location, boundary)
        VALUES ('far-point', ST_GeogFromText('SRID=4326;POINT(100 100)'), NULL)
    SQL);

    DB::insert(<<<'SQL'
        INSERT INTO test_geo_locations (name, location, boundary)
        VALUES ('inside-zone', ST_GeogFromText('SRID=4326;POINT(5 5)'),
                ST_GeogFromText('SRID=4326;POLYGON((0 0, 10 0, 10 10, 0 10, 0 0))'))
    SQL);

    DB::insert(<<<'SQL'
        INSERT INTO test_geo_locations (name, location, boundary)
        VALUES ('outside-zone', ST_GeogFromText('SRID=4326;POINT(15 15)'),
                ST_GeogFromText('SRID=4326;POLYGON((20 20, 30 20, 30 30, 20 30, 20 20))'))
    SQL);
});

afterEach(function () {
    Schema::dropIfExists('test_geo_locations');
});

function geographyModel(): Model
{
    return new class extends Model
    {
        use Spatialable;

        protected $table = 'test_geo_locations';

        public $timestamps = false;

        protected $casts = [
            'location' => PointCast::class.':geography',
            'boundary' => PointCast::class.':geography',
        ];
    };
}

it('filters geography points within distance using scopeWithinDistanceTo', function () {
    $center = new Point(0, 0, 4326);

    $results = geographyModel()->withinDistanceTo('location', $center, 1000000)->get();

    expect($results->pluck('name')->toArray())->toContain('center', 'near-point');
    expect($results->pluck('name')->toArray())->not->toContain('far-point');
});

it('orders geography points by distance using scopeOrderByDistanceTo', function () {
    $center = new Point(0, 0, 4326);

    $results = geographyModel()->orderByDistanceTo('location', $center, 'asc')->get();

    expect($results->first()->name)->toBe('center');
    expect($results->last()->name)->toBe('far-point');
});

it('computes area on geography column', function () {
    $model = geographyModel();
    $row = $model->whereNotNull('boundary')->withArea('boundary')->first();

    expect($row->boundary_area)->toBeGreaterThan(0);
});

it('throws on ST_Contains for geography column', function () {
    $point = new Point(5, 5, 4326);

    geographyModel()->containsGeometry('boundary', $point)->get();
})->throws(RuntimeException::class, 'ST_Contains is only supported for geometry columns.');

it('throws on ST_Within for geography column', function () {
    $polygon = new Polygon([
        [new Point(0, 0), new Point(10, 0), new Point(10, 10), new Point(0, 10), new Point(0, 0)],
    ], 4326);

    geographyModel()->withinGeometry('location', $polygon)->get();
})->throws(RuntimeException::class, 'ST_Within is only supported for geometry columns.');

it('throws on ST_Intersects for geography column', function () {
    $searchPolygon = new Polygon([
        [new Point(-10, -10), new Point(150, -10), new Point(150, 150), new Point(-10, 150), new Point(-10, -10)],
    ], 4326);

    geographyModel()->intersectsWith('location', $searchPolygon)->get();
})->throws(RuntimeException::class, 'ST_Intersects is only supported for geometry columns.');

it('accepts typeOverride parameter for scopes', function () {
    $center = new Point(0, 0, 4326);

    $results = geographyModel()->withinDistanceTo('location', $center, 1000000, typeOverride: 'geometry')->get();

    expect($results->pluck('name')->toArray())->toContain('center', 'near-point');
});

it('validates geography spatial attribute correctly', function () {
    $point = new Point(0, 0, 4326);

    geographyModel()->withinDistanceTo('nonexistent_column', $point, 100)->get();
})->throws(InvalidArgumentException::class);
