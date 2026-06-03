<?php

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Yaseen\GeoCast\Casters\PointCast;
use Yaseen\GeoCast\Geometries\Point;

it('returns a Point from a valid hex-encoded EWKB string', function () {
    // Hex-encoded EWKB for POINT(10.5 20.7) with SRID 4326
    $hex = bin2hex(
        pack('C', 1)                    // byte order = little endian
        .pack('V', 0x20000001)         // type = Point | SRID flag
        .pack('V', 4326)               // SRID
        .pack('e', 10.5)              // X
        .pack('e', 20.7)              // Y
    );

    $cast = new PointCast;
    $point = $cast->get(null, 'location', $hex, []);

    expect($point)->toBeInstanceOf(Point::class);
    expect($point->getLng())->toBe(10.5);
    expect($point->getLat())->toBe(20.7);
    expect($point->getSrid())->toBe(4326);
});

it('returns null when value is null', function () {
    $cast = new PointCast;
    $result = $cast->get(null, 'location', null, []);

    expect($result)->toBeNull();
});

it('parses POINT(0 0) EWKB correctly', function () {
    // Hex-encoded EWKB for POINT(0 0) with SRID 4326
    $hex = bin2hex(
        pack('C', 1)
        .pack('V', 0x20000001)
        .pack('V', 4326)
        .pack('e', 0.0)
        .pack('e', 0.0)
    );

    $cast = new PointCast;
    $point = $cast->get(null, 'location', $hex, []);

    expect($point)->toBeInstanceOf(Point::class);
    expect($point->getLng())->toBe(0.0);
    expect($point->getLat())->toBe(0.0);
});

it('returns null when parsed geometry is not a Point', function () {
    // Hex-encoded EWKB for a Polygon with SRID 4326
    $hex = bin2hex(
        pack('C', 1)
        .pack('V', 0x20000003)         // type = Polygon | SRID flag
        .pack('V', 4326)
        .pack('V', 1)                  // numRings
        .pack('V', 3)                  // numPoints
        .pack('e', 0.0).pack('e', 0.0)
        .pack('e', 1.0).pack('e', 0.0)
        .pack('e', 0.0).pack('e', 1.0)
    );

    $cast = new PointCast;
    $result = $cast->get(null, 'location', $hex, []);

    expect($result)->toBeNull();
});

it('creates ST_GeomFromText expression from a Point', function () {
    $point = new Point(20.7, 10.5, 4326);

    $cast = new PointCast;
    $result = $cast->set(null, 'location', $point, []);

    expect($result)->toBeInstanceOf(Expression::class);
    expect($result->getValue(DB::connection()->getQueryGrammar()))->toBe("ST_GeomFromText('POINT(10.5 20.7)', 4326)");
});

it('returns null when setting null', function () {
    $cast = new PointCast;
    $result = $cast->set(null, 'location', null, []);

    expect($result)->toBeNull();
});

it('throws when setting non-Point value', function () {
    $cast = new PointCast;

    $cast->set(null, 'location', 'not-a-point', []);
})->throws(InvalidArgumentException::class, 'Field location must be an instance of Point.');

it('handles empty hex string gracefully', function () {
    $cast = new PointCast;
    $result = $cast->get(null, 'location', '', []);

    expect($result)->toBeNull();
});

it('handles invalid hex string gracefully', function () {
    $cast = new PointCast;
    $result = $cast->get(null, 'location', 'xx', []);

    expect($result)->toBeNull();
});
