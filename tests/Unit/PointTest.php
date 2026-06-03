<?php

use Yaseen\GeoCast\Geometries\Point;

it('can be constructed with lat, lng, and srid', function () {
    $point = new Point(20.7, 10.5, 4326);

    expect($point->getLat())->toBe(20.7);
    expect($point->getLng())->toBe(10.5);
    expect($point->getSrid())->toBe(4326);
});

it('defaults srid to 4326', function () {
    $point = new Point(10.0, 20.0);

    expect($point->getSrid())->toBe(4326);
});

it('coerces lng and lat to float', function () {
    $point = new Point('20.7', '10.5');

    expect($point->getLat())->toBeFloat();
    expect($point->getLng())->toBeFloat();
    expect($point->getLat())->toBe(20.7);
    expect($point->getLng())->toBe(10.5);
});

it('generates correct WKT', function () {
    $point = new Point(20.0, 10.0);

    expect($point->toWkt())->toBe('POINT(10 20)');
});

it('generates correct WKT with negative coordinates', function () {
    $point = new Point(40.7484, -73.9857);

    expect($point->toWkt())->toBe('POINT(-73.9857 40.7484)');
});

it('converts to array', function () {
    $point = new Point(20.7, 10.5, 4326);

    expect($point->toArray())->toBe([
        'type' => 'Point',
        'latitude' => 20.7,
        'longitude' => 10.5,
        'srid' => 4326,
    ]);
});
