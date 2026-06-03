<?php

use GeoIO\WKB\Parser\Parser;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;
use Yaseen\GeoCast\MyGeoFactory;

it('returns a parser instance', function () {
    $parser = MyGeoFactory::parser();

    expect($parser)->toBeInstanceOf(Parser::class);
});

it('creates a point from coordinates', function () {
    $factory = new MyGeoFactory;

    $point = $factory->createPoint(2, ['x' => 10.5, 'y' => 20.7], 4326);

    expect($point)->toBeInstanceOf(Point::class);
    expect($point->getLng())->toBe(10.5);
    expect($point->getLat())->toBe(20.7);
    expect($point->getSrid())->toBe(4326);
});

it('defaults to srid 4326 for point', function () {
    $factory = new MyGeoFactory;

    $point = $factory->createPoint(2, ['x' => 10.0, 'y' => 20.0]);

    expect($point->getSrid())->toBe(4326);
});

it('creates a polygon from linestrings', function () {
    $factory = new MyGeoFactory;

    $ring = [
        $factory->createPoint(2, ['x' => 0, 'y' => 0]),
        $factory->createPoint(2, ['x' => 10, 'y' => 0]),
        $factory->createPoint(2, ['x' => 10, 'y' => 10]),
        $factory->createPoint(2, ['x' => 0, 'y' => 0]),
    ];

    $polygon = $factory->createPolygon(2, [$ring], 4326);

    expect($polygon)->toBeInstanceOf(Polygon::class);
    expect($polygon->getRings())->toHaveCount(1);
    expect($polygon->getSrid())->toBe(4326);
});

it('parses WKB point without SRID', function () {
    $wkb = pack('C', 1)               // byte order = little endian
        .pack('V', 1)                // type = Point (no SRID flag)
        .pack('e', 10.5)             // X
        .pack('e', 20.7);            // Y

    $parser = MyGeoFactory::parser();
    $result = $parser->parse($wkb);

    expect($result)->toBeInstanceOf(Point::class);
    expect($result->getLng())->toBe(10.5);
    expect($result->getLat())->toBe(20.7);
    expect($result->getSrid())->toBe(4326); // default fallback
});

it('parses EWKB point with embedded SRID', function () {
    $wkb = pack('C', 1)               // byte order = little endian
        .pack('V', 0x20000001)       // type = Point | SRID flag
        .pack('V', 2154)             // SRID = 2154
        .pack('e', 10.5)             // X
        .pack('e', 20.7);            // Y

    $parser = MyGeoFactory::parser();
    $result = $parser->parse($wkb);

    expect($result)->toBeInstanceOf(Point::class);
    expect($result->getSrid())->toBe(2154);
});

it('parses WKB polygon', function () {
    $wkb = pack('C', 1)               // byte order = little endian
        .pack('V', 3)                // type = Polygon
        .pack('V', 1)                // numRings = 1
        .pack('V', 4)                // numPoints = 4
        .pack('e', 0.0).pack('e', 0.0)     // (0, 0)
        .pack('e', 10.0).pack('e', 0.0)     // (10, 0)
        .pack('e', 10.0).pack('e', 10.0)    // (10, 10)
        .pack('e', 0.0).pack('e', 0.0);    // (0, 0)

    $parser = MyGeoFactory::parser();
    $result = $parser->parse($wkb);

    expect($result)->toBeInstanceOf(Polygon::class);
    expect($result->getRings())->toHaveCount(1);
    expect($result->toWkt())->toBe('POLYGON((0 0, 10 0, 10 10, 0 0))');
});

it('creates a linear ring from points', function () {
    $factory = new MyGeoFactory;

    $points = [
        $factory->createPoint(2, ['x' => 0, 'y' => 0]),
        $factory->createPoint(2, ['x' => 10, 'y' => 0]),
    ];

    $ring = $factory->createLinearRing(2, $points);

    expect($ring)->toBe($points);
    expect($ring)->toHaveCount(2);
});
