<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;
use Yaseen\GeoCast\Tests\Models\TestZone;

beforeEach(function () {
    Schema::dropIfExists('test_zones');

    DB::statement(<<<'SQL'
        CREATE TABLE test_zones (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            area geometry(Polygon, 4326) NOT NULL
        )
    SQL);
});

afterEach(function () {
    Schema::dropIfExists('test_zones');
});

it('stores and retrieves a Polygon', function () {
    $ring = [
        new Point(0, 0),
        new Point(10, 0),
        new Point(10, 10),
        new Point(0, 0),
    ];
    $polygon = new Polygon([$ring], 4326);

    $model = TestZone::create([
        'name' => 'square-zone',
        'area' => $polygon,
    ]);

    expect($model->id)->toBeGreaterThan(0);
    expect($model->area)->toBeInstanceOf(Polygon::class);
    expect($model->area->getRings())->toHaveCount(1);
    expect($model->area->getSrid())->toBe(4326);
    expect($model->area->toWkt())->toBe('POLYGON((0 0, 10 0, 10 10, 0 0))');
});

it('retrieves a Polygon from the database', function () {
    DB::insert(<<<'SQL'
        INSERT INTO test_zones (name, area)
        VALUES ('db-zone', ST_GeomFromText('POLYGON((0 0, 10 0, 10 10, 0 0))', 4326))
    SQL);

    $model = TestZone::first();

    expect($model->area)->toBeInstanceOf(Polygon::class);
    expect($model->area->getRings())->toHaveCount(1);
});

it('stores a Polygon and verifies with raw PostGIS query', function () {
    $ring = [
        new Point(-10, -10),
        new Point(10, -10),
        new Point(10, 10),
        new Point(-10, -10),
    ];
    $polygon = new Polygon([$ring], 4326);

    $model = TestZone::create([
        'name' => 'big-zone',
        'area' => $polygon,
    ]);

    $result = DB::selectOne(
        'SELECT ST_AsText(area) as wkt, ST_SRID(area) as srid FROM test_zones WHERE id = ?',
        [$model->id]
    );

    expect($result->wkt)->toMatch('/^POLYGON\(/');
    expect((int) $result->srid)->toBe(4326);
});
