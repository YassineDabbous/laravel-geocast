<?php

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Yaseen\GeoCast\Casters\PolygonCast;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;

it('returns a Polygon from a valid hex-encoded EWKB string', function () {
    // Hex-encoded EWKB for POLYGON((0 0, 10 0, 10 10, 0 0)) with SRID 4326
    $hex = bin2hex(
        pack('C', 1)                   // byte order = little endian
        .pack('V', 0x20000003)        // type = Polygon | SRID flag
        .pack('V', 4326)              // SRID
        .pack('V', 1)                 // numRings
        .pack('V', 4)                 // numPoints
        .pack('e', 0.0).pack('e', 0.0)
        .pack('e', 10.0).pack('e', 0.0)
        .pack('e', 10.0).pack('e', 10.0)
        .pack('e', 0.0).pack('e', 0.0)
    );

    $cast = new PolygonCast;
    $polygon = $cast->get(null, 'area', $hex, []);

    expect($polygon)->toBeInstanceOf(Polygon::class);
    expect($polygon->getRings())->toHaveCount(1);
    expect($polygon->toWkt())->toBe('POLYGON((0 0, 10 0, 10 10, 0 0))');
    expect($polygon->getSrid())->toBe(4326);
});

it('returns null when value is null', function () {
    $cast = new PolygonCast;
    $result = $cast->get(null, 'area', null, []);

    expect($result)->toBeNull();
});

it('returns null when parsed geometry is not a Polygon', function () {
    // Hex-encoded EWKB for POINT(10 20) with SRID 4326
    $hex = bin2hex(
        pack('C', 1)
        .pack('V', 0x20000001)        // type = Point | SRID flag
        .pack('V', 4326)
        .pack('e', 10.0)
        .pack('e', 20.0)
    );

    $cast = new PolygonCast;
    $result = $cast->get(null, 'area', $hex, []);

    expect($result)->toBeNull();
});

it('creates ST_GeomFromText expression from a Polygon', function () {
    $ring = [
        new Point(0, 0),
        new Point(10, 0),
        new Point(10, 10),
        new Point(0, 0),
    ];
    $polygon = new Polygon([$ring], 4326);

    $cast = new PolygonCast;
    $result = $cast->set(null, 'area', $polygon, []);

    expect($result)->toBeInstanceOf(Expression::class);
    expect($result->getValue(DB::connection()->getQueryGrammar()))->toBe("ST_GeomFromText('POLYGON((0 0, 10 0, 10 10, 0 0))', 4326)");
});

it('creates ST_GeogFromText expression from a Polygon with geography type', function () {
    $ring = [
        new Point(0, 0),
        new Point(10, 0),
        new Point(10, 10),
        new Point(0, 0),
    ];
    $polygon = new Polygon([$ring], 4326);

    $cast = new PolygonCast('geography');
    $result = $cast->set(null, 'area', $polygon, []);

    expect($result)->toBeInstanceOf(Expression::class);
    expect($result->getValue(DB::connection()->getQueryGrammar()))->toBe("ST_GeogFromText('SRID=4326;POLYGON((0 0, 10 0, 10 10, 0 0))')");
});



it('returns null when setting null', function () {
    $cast = new PolygonCast;
    $result = $cast->set(null, 'area', null, []);

    expect($result)->toBeNull();
});

it('throws when setting non-Polygon value', function () {
    $cast = new PolygonCast;

    $cast->set(null, 'area', 'not-a-polygon', []);
})->throws(InvalidArgumentException::class, 'Field area must be an instance of Polygon.');

it('handles empty hex string gracefully', function () {
    $cast = new PolygonCast;
    $result = $cast->get(null, 'area', '', []);

    expect($result)->toBeNull();
});
