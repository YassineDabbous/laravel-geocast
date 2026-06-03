<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;
use Yaseen\GeoCast\Tests\Models\TestSpatialModel;

beforeEach(function () {
    Schema::dropIfExists('test_spatial_locations');

    DB::statement(<<<'SQL'
        CREATE TABLE test_spatial_locations (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            location geometry(Point, 4326) NOT NULL,
            boundary geometry(Polygon, 4326)
        )
    SQL);

    // Seed data: a point at (0, 0)
    DB::insert(<<<'SQL'
        INSERT INTO test_spatial_locations (name, location, boundary)
        VALUES ('center', ST_GeomFromText('POINT(0 0)', 4326), NULL)
    SQL);

    // A point at (2, 2)
    DB::insert(<<<'SQL'
        INSERT INTO test_spatial_locations (name, location, boundary)
        VALUES ('near-point', ST_GeomFromText('POINT(2 2)', 4326), NULL)
    SQL);

    // A point far away (100, 100)
    DB::insert(<<<'SQL'
        INSERT INTO test_spatial_locations (name, location, boundary)
        VALUES ('far-point', ST_GeomFromText('POINT(100 100)', 4326), NULL)
    SQL);

    // A point inside a polygon zone (rectangle 0-10, 0-10)
    DB::insert(<<<'SQL'
        INSERT INTO test_spatial_locations (name, location, boundary)
        VALUES ('inside-zone', ST_GeomFromText('POINT(5 5)', 4326),
                ST_GeomFromText('POLYGON((0 0, 10 0, 10 10, 0 10, 0 0))', 4326))
    SQL);

    // A point outside with a boundary polygon that does NOT contain (5,5)
    DB::insert(<<<'SQL'
        INSERT INTO test_spatial_locations (name, location, boundary)
        VALUES ('outside-zone', ST_GeomFromText('POINT(15 15)', 4326),
                ST_GeomFromText('POLYGON((20 20, 30 20, 30 30, 20 30, 20 20))', 4326))
    SQL);

});

afterEach(function () {
    Schema::dropIfExists('test_spatial_locations');
});

it('filters points within distance using scopeWithinDistanceTo', function () {
    $center = new Point(0, 0, 4326);

    $results = TestSpatialModel::withinDistanceTo('location', $center, 1000000)->get();

    // center (0,0) and near-point (2,2) are within ~314km, far-point (100,100) is ~15,700km away
    expect($results->pluck('name')->toArray())->toContain('center', 'near-point');
    expect($results->pluck('name')->toArray())->not->toContain('far-point');
});

it('filters points within a very large distance returns all', function () {
    $center = new Point(0, 0, 4326);

    $results = TestSpatialModel::withinDistanceTo('location', $center, 20000000)->get();

    expect($results)->toHaveCount(5);
});

it('orders by distance using scopeOrderByDistanceTo', function () {
    $center = new Point(0, 0, 4326);

    $results = TestSpatialModel::orderByDistanceTo('location', $center, 'asc')->get();

    expect($results->first()->name)->toBe('center');
    expect($results->last()->name)->toBe('far-point');
});

it('orders by distance descending', function () {
    $center = new Point(0, 0, 4326);

    $results = TestSpatialModel::orderByDistanceTo('location', $center, 'desc')->get();

    expect($results->first()->name)->toBe('far-point');
    expect($results->last()->name)->toBe('center');
});

it('finds polygons that contain a point using scopeContainsGeometry', function () {
    $point = new Point(5, 5, 4326);

    // Query: find records where boundary contains the given point
    $results = TestSpatialModel::containsGeometry('boundary', $point)->get();

    expect($results->pluck('name')->toArray())->toBe(['inside-zone']);
});

it('finds points within a polygon using scopeWithinGeometry', function () {
    $polygon = new Polygon([
        [new Point(0, 0), new Point(10, 0), new Point(10, 10), new Point(0, 10), new Point(0, 0)],
    ], 4326);

    // Both near-point (2,2) and inside-zone (5,5) are within this rectangle
    $results = TestSpatialModel::withinGeometry('location', $polygon)->get();

    expect($results->pluck('name')->toArray())->toContain('near-point', 'inside-zone');
    expect($results->pluck('name')->toArray())->not->toContain('far-point');
});

it('finds intersecting geometries using scopeIntersectsWith', function () {
    $searchPolygon = new Polygon([
        [new Point(-10, -10), new Point(150, -10), new Point(150, 150), new Point(-10, 150), new Point(-10, -10)],
    ], 4326);

    $results = TestSpatialModel::intersectsWith('location', $searchPolygon)->get();

    expect($results)->toHaveCount(5);
});

it('adds area column with scopeWithArea', function () {
    $results = TestSpatialModel::withArea('boundary')->whereNotNull('boundary')->get();

    expect($results->first()->boundary_area)->toBeGreaterThan(0);
});

it('throws for invalid column in scope', function () {
    $point = new Point(0, 0, 4326);

    TestSpatialModel::withinDistanceTo('nonexistent_column', $point, 100)->get();
})->throws(InvalidArgumentException::class);

it('throws for column without spatial cast', function () {
    $point = new Point(0, 0, 4326);

    TestSpatialModel::withinDistanceTo('name', $point, 100)->get();
})->throws(InvalidArgumentException::class);
