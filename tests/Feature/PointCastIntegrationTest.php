<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Tests\Models\TestLocation;

beforeEach(function () {
    Schema::dropIfExists('test_locations');

    DB::statement(<<<'SQL'
        CREATE TABLE test_locations (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            location geometry(Point, 4326) NOT NULL
        )
    SQL);
});

afterEach(function () {
    Schema::dropIfExists('test_locations');
});

it('stores and retrieves a Point', function () {
    $point = new Point(20.7, 10.5, 4326);

    $model = TestLocation::create([
        'name' => 'test-point',
        'location' => $point,
    ]);

    expect($model->id)->toBeGreaterThan(0);
    expect($model->location)->toBeInstanceOf(Point::class);
    expect($model->location->getLng())->toBe(10.5);
    expect($model->location->getLat())->toBe(20.7);
    expect($model->location->getSrid())->toBe(4326);
});

it('retrieves a Point from the database', function () {
    DB::insert(<<<'SQL'
        INSERT INTO test_locations (name, location)
        VALUES ('db-point', ST_GeomFromText('POINT(-73.9857 40.7484)', 4326))
    SQL);

    $model = TestLocation::first();

    expect($model->location)->toBeInstanceOf(Point::class);
    expect($model->location->getLng())->toBe(-73.9857);
    expect($model->location->getLat())->toBe(40.7484);
});

it('stores a Point and verifies with raw PostGIS query', function () {
    $point = new Point(48.8566, 2.3522, 4326);

    $model = TestLocation::create([
        'name' => 'paris',
        'location' => $point,
    ]);

    $result = DB::selectOne(
        'SELECT ST_AsText(location) as wkt, ST_SRID(location) as srid FROM test_locations WHERE id = ?',
        [$model->id]
    );

    expect($result->wkt)->toMatch('/^POINT\(/');
    expect((int) $result->srid)->toBe(4326);
});
