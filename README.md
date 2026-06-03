# Laravel GeoCast

PostGIS spatial casts and query scopes for Laravel Eloquent models.

```php
$venue = Venue::create([
    'name' => 'Central Park',
    'location' => new Point(-73.9654, 40.7829),
]);

$nearby = Venue::withinDistanceTo('location', $point, 1000)->get();
```

## Requirements

- PHP 8.1+
- Laravel 10+ (illuminate/database, illuminate/contracts, illuminate/support)
- PostgreSQL with PostGIS extension
- `geo-io/wkb-parser` (included automatically)

## Installation

```bash
composer require yassinedabbous/laravel-geocast
```

## Geometry Types

### Point

Represents a spatial point with latitude, longitude, and SRID.

```php
use Yaseen\GeoCast\Geometries\Point;

// Constructor: Point(longitude, latitude, srid = 4326)
$point = new Point(2.3522, 48.8566, 4326);

$point->getLng();    // 2.3522
$point->getLat();    // 48.8566
$point->getSrid();   // 4326
$point->toWkt();     // "POINT(2.3522 48.8566)"
$point->toArray();   // ['type' => 'Point', 'latitude' => 48.8566, 'longitude' => 2.3522, 'srid' => 4326]
```

### Polygon

Represents a polygon with one or more rings (outer ring + holes).

```php
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;

$outer = [
    new Point(0, 0),
    new Point(10, 0),
    new Point(10, 10),
    new Point(0, 10),
    new Point(0, 0),
];

$polygon = new Polygon([$outer], 4326);

$polygon->getRings();    // array of rings
$polygon->getSrid();     // 4326
$polygon->toWkt();       // "POLYGON((0 0, 10 0, 10 10, 0 10, 0 0))"
```

## Eloquent Casts

Register spatial columns using Eloquent's native cast system:

```php
use Yaseen\GeoCast\Casters\PointCast;
use Yaseen\GeoCast\Casters\PolygonCast;

class Venue extends Model
{
    protected $casts = [
        'location' => PointCast::class,
        'boundary' => PolygonCast::class,
    ];
}
```

### Writing spatial data

```php
$venue = Venue::create([
    'name' => 'Times Square',
    'location' => new Point(-73.9855, 40.7580),
]);
```

### Reading spatial data

```php
$venue = Venue::first();
$location = $venue->location; // Yaseen\GeoCast\Geometries\Point
```

Null spatial columns are returned as `null`.

## Query Scopes

Use the `Spatialable` trait to enable PostGIS-powered query scopes on your model:

```php
use Yaseen\GeoCast\Spatialable;

class Venue extends Model
{
    use Spatialable;

    protected $casts = [
        'location' => PointCast::class,
    ];
}
```

### `withinDistanceTo($column, $geometry, $meters)`

Find records within a distance in meters.

```php
$center = new Point(-74.0060, 40.7128);

$venues = Venue::withinDistanceTo('location', $center, 5000)->get();
```

### `orderByDistanceTo($column, $geometry, $direction = 'asc')`

Order records by distance from a geometry.

```php
$venues = Venue::orderByDistanceTo('location', $point)->get();
$venues = Venue::orderByDistanceTo('location', $point, 'desc')->get();
```

### `containsGeometry($column, $geometry)`

Find records where the spatial column contains the given geometry.

```php
$point = new Point(-73.9857, 40.7484);

$zones = Zone::containsGeometry('boundary', $point)->get();
```

### `withinGeometry($column, $geometry)`

Find records where the spatial column is within the given geometry.

```php
$park = new Polygon([$boundary], 4326);

$trees = Tree::withinGeometry('location', $park)->get();
```

### `intersectsWith($column, $geometry)`

Find records whose spatial column intersects the given geometry.

```php
$searchArea = new Polygon([$area], 4326);

$landmarks = Landmark::intersectsWith('location', $searchArea)->get();
```

### `withArea($column)`

Append the area (in square meters) of a polygon column to the result.

```php
$zones = Zone::withArea('boundary')->get();

foreach ($zones as $zone) {
    echo $zone->boundary_area; // square meters
}
```

### Validation

All spatial scopes validate that the given column is registered with a valid
spatial cast. They throw `InvalidArgumentException` if the column is missing
or uses a non-spatial cast.

## Testing

```bash
composer test
```

The test suite uses Pest with Orchestra Testbench against a PostgreSQL/PostGIS
database. See `phpunit.xml.dist` and `tests/bootstrap.php` for configuration.

## License

MIT
