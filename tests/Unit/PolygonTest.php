<?php

use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;

it('can be constructed with a single ring', function () {
    $ring = [
        new Point(0, 0),
        new Point(0, 10),
        new Point(10, 10),
        new Point(0, 0),
    ];

    $polygon = new Polygon([$ring]);

    expect($polygon->getRings())->toHaveCount(1);
    expect($polygon->getSrid())->toBe(4326);
});

it('defaults srid to 4326', function () {
    $ring = [new Point(0, 0), new Point(0, 1), new Point(1, 0), new Point(0, 0)];
    $polygon = new Polygon([$ring]);

    expect($polygon->getSrid())->toBe(4326);
});

it('generates correct WKT for single ring polygon', function () {
    $ring = [
        new Point(0, 0),
        new Point(0, 10),
        new Point(10, 10),
        new Point(0, 0),
    ];

    $polygon = new Polygon([$ring], 4326);

    expect($polygon->toWkt())->toBe('POLYGON((0 0, 10 0, 10 10, 0 0))');
});

it('generates correct WKT for polygon with hole', function () {
    $outerRing = [
        new Point(0, 0),
        new Point(0, 20),
        new Point(20, 20),
        new Point(0, 0),
    ];
    $innerRing = [
        new Point(5, 5),
        new Point(5, 15),
        new Point(15, 15),
        new Point(5, 5),
    ];

    $polygon = new Polygon([$outerRing, $innerRing], 4326);

    expect($polygon->toWkt())->toBe('POLYGON((0 0, 20 0, 20 20, 0 0), (5 5, 15 5, 15 15, 5 5))');
});

it('converts to array', function () {
    $ring = [
        new Point(0, 0),
        new Point(0, 10),
        new Point(10, 10),
        new Point(0, 0),
    ];

    $polygon = new Polygon([$ring], 4326);

    expect($polygon->toArray())->toBe([
        'type' => 'Polygon',
        'coordinates' => [
            [[0.0, 0.0], [10.0, 0.0], [10.0, 10.0], [0.0, 0.0]],
        ],
        'srid' => 4326,
    ]);
});

it('handles non-Point items in toArray without fatal', function () {
    $ring = [
        new Point(0, 0),
        new Point(0, 10),
        null,
        new Point(10, 10),
        new Point(0, 0),
    ];

    $polygon = new Polygon([$ring], 4326);
    $result = $polygon->toArray();

    expect($result['coordinates'][0][0])->toBe([0.0, 0.0]);
    expect($result['coordinates'][0][1])->toBe([10.0, 0.0]);
    expect($result['coordinates'][0][2])->toBe([10.0, 10.0]);
    expect($result['coordinates'][0][3])->toBe([0.0, 0.0]);
    expect($result['coordinates'][0])->toHaveCount(4);
});
