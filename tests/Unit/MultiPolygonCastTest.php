<?php

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Yaseen\GeoCast\Casters\MultiPolygonCast;
use Yaseen\GeoCast\Geometries\MultiPolygon;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;

it('returns a MultiPolygon from a valid hex-encoded EWKB string', function () {
    // Hex-encoded EWKB for MULTIPOLYGON(((0 0, 10 0, 10 10, 0 0))) with SRID 4326
    // Outer geometry has SRID flag set; inner geometries (Polygon) do NOT repeat SRID flag
    $hex = bin2hex(
        pack('C', 1)                    // byte order = little endian
        .pack('V', 0x20000006)         // type = MultiPolygon | SRID flag
        .pack('V', 4326)               // SRID
        .pack('V', 1)                  // numPolygons
        // Inner polygon — no SRID flag (0x00000003)
        .pack('C', 1)                  // byte order = little endian
        .pack('V', 3)                  // type = Polygon (no SRID flag)
        .pack('V', 1)                  // numRings
        .pack('V', 4)                  // numPoints
        .pack('e', 0.0).pack('e', 0.0)
        .pack('e', 10.0).pack('e', 0.0)
        .pack('e', 10.0).pack('e', 10.0)
        .pack('e', 0.0).pack('e', 0.0)
    );

    $cast = new MultiPolygonCast;
    $multiPolygon = $cast->get(null, 'area', $hex, []);

    expect($multiPolygon)->toBeInstanceOf(MultiPolygon::class);
    expect($multiPolygon->getPolygons())->toHaveCount(1);
    expect($multiPolygon->getSrid())->toBe(4326);
});

it('returns null when value is null', function () {
    $cast = new MultiPolygonCast;
    $result = $cast->get(null, 'area', null, []);

    expect($result)->toBeNull();
});

it('returns null when parsed geometry is not a MultiPolygon', function () {
    // Hex-encoded EWKB for POINT(10 20) with SRID 4326
    $hex = bin2hex(
        pack('C', 1)
        .pack('V', 0x20000001)        // type = Point | SRID flag
        .pack('V', 4326)
        .pack('e', 10.0)
        .pack('e', 20.0)
    );

    $cast = new MultiPolygonCast;
    $result = $cast->get(null, 'area', $hex, []);

    expect($result)->toBeNull();
});

it('creates ST_GeomFromText expression from a MultiPolygon', function () {
    $ring = [
        new Point(0, 0),
        new Point(10, 0),
        new Point(10, 10),
        new Point(0, 0),
    ];
    $polygon = new Polygon([$ring], 4326);
    $multiPolygon = new MultiPolygon([$polygon], 4326);

    $cast = new MultiPolygonCast;
    $result = $cast->set(null, 'area', $multiPolygon, []);

    expect($result)->toBeInstanceOf(Expression::class);
    expect($result->getValue(DB::connection()->getQueryGrammar()))->toBe("ST_GeomFromText('MULTIPOLYGON(POLYGON((0 0, 10 0, 10 10, 0 0)))', 4326)");
});

it('creates ST_GeogFromText expression from a MultiPolygon with geography type', function () {
    $ring = [
        new Point(0, 0),
        new Point(10, 0),
        new Point(10, 10),
        new Point(0, 0),
    ];
    $polygon = new Polygon([$ring], 4326);
    $multiPolygon = new MultiPolygon([$polygon], 4326);

    $cast = new MultiPolygonCast('geography');
    $result = $cast->set(null, 'area', $multiPolygon, []);

    expect($result)->toBeInstanceOf(Expression::class);
    expect($result->getValue(DB::connection()->getQueryGrammar()))->toBe("ST_GeogFromText('SRID=4326;MULTIPOLYGON(POLYGON((0 0, 10 0, 10 10, 0 0)))')");
});

it('returns null when setting null', function () {
    $cast = new MultiPolygonCast;
    $result = $cast->set(null, 'area', null, []);

    expect($result)->toBeNull();
});

it('throws when setting non-MultiPolygon value', function () {
    $cast = new MultiPolygonCast;

    $cast->set(null, 'area', 'not-a-multipolygon', []);
})->throws(InvalidArgumentException::class, 'Field area must be an instance of MultiPolygon.');

it('handles empty hex string gracefully', function () {
    $cast = new MultiPolygonCast;
    $result = $cast->get(null, 'area', '', []);

    expect($result)->toBeNull();
});

it('handles invalid hex string gracefully', function () {
    $cast = new MultiPolygonCast;
    $result = $cast->get(null, 'area', 'xx', []);

    expect($result)->toBeNull();
});
