# Laravel GeoCast

PostGIS spatial casts and query scopes for Laravel Eloquent models.

```php
$nearby = Venue::withinDistanceTo('location', new Point(-73.9654, 40.7829), 1000)->get();
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

Represents a spatial point with longitude, latitude, and SRID.

```php
use Yaseen\GeoCast\Geometries\Point;

$point = new Point(2.3522, 48.8566, 4326);
// Constructor: Point(longitude, latitude, srid = 4326)

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

### MultiPolygon

Represents a collection of polygons (e.g., service areas with multiple disjoint zones).

```php
use Yaseen\GeoCast\Geometries\MultiPolygon;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;

$zone1 = new Polygon([
    [new Point(0, 0), new Point(10, 0), new Point(10, 10), new Point(0, 10), new Point(0, 0)],
], 4326);

$zone2 = new Polygon([
    [new Point(20, 20), new Point(30, 20), new Point(30, 30), new Point(20, 30), new Point(20, 20)],
], 4326);

$multi = new MultiPolygon([$zone1, $zone2], 4326);

$multi->getPolygons();   // array of Polygon objects
$multi->getSrid();       // 4326
$multi->toWkt();         // "MULTIPOLYGON(POLYGON((0 0, 10 0, 10 10, 0 10, 0 0)), POLYGON((20 20, 30 20, 30 30, 20 30, 20 20)))"
```

## Eloquent Casts

Register spatial columns using Eloquent's native cast system:

```php
use Yaseen\GeoCast\Casters\MultiPolygonCast;
use Yaseen\GeoCast\Casters\PointCast;
use Yaseen\GeoCast\Casters\PolygonCast;

class Venue extends Model
{
    protected $casts = [
        'location' => PointCast::class,
        'boundary' => PolygonCast::class,
        'service_area' => MultiPolygonCast::class,
    ];
}
```

### Geography vs Geometry

By default, casters write PostGIS `geometry` columns via `ST_GeomFromText()`. Pass a cast parameter to write native `geography` columns via `ST_GeogFromText()`:

```php
protected $casts = [
    'location' => PointCast::class . ':geography',
    'pickup_location' => PointCast::class . ':geometry',
    'zone' => PolygonCast::class,
    'area' => MultiPolygonCast::class . ':geography',
];
```

| Cast parameter | PostGIS function | Best for |
|---|---|---|
| `:geometry` (default) | `ST_GeomFromText()` | Columns needing `ST_Contains`, `ST_Within`, `ST_Intersects` |
| `:geography` | `ST_GeogFromText()` | Columns only needing distance + area calculations |

**Recommendation:** Default to geometry columns. Query scopes automatically cast `::geography` inline for distance and area calculations (giving accurate meters), while keeping full topological function support (`ST_Contains`, `ST_Within`, `ST_Intersects`). Reserve `:geography` for native PostGIS geography columns that never need topological queries.

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

Add the `Spatialable` trait to enable PostGIS-powered query scopes on your model:

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

The trait reads each column's spatial type from `$casts` and generates the correct SQL for geography vs geometry columns.

### `withinDistanceTo`

```php
withinDistanceTo(Builder $query, string $column, Geometry $geometry, int $meters, ?string $typeOverride = null): void
```

Filter records within a distance in meters.

```php
$venues = Venue::withinDistanceTo('location', new Point(-74.0060, 40.7128), 5000)->get();
```

### `orderByDistanceTo`

```php
orderByDistanceTo(Builder $query, string $column, Geometry $geometry, string $direction = 'asc', ?string $typeOverride = null): void
```

Order records by distance from a geometry.

```php
$venues = Venue::orderByDistanceTo('location', $point)->get();
$venues = Venue::orderByDistanceTo('location', $point, 'desc')->get();
```

### `containsGeometry`

```php
containsGeometry(Builder $query, string $column, Geometry $geometry, ?string $typeOverride = null): void
```

Find records where the spatial column contains the given geometry.

```php
$zones = Zone::containsGeometry('boundary', new Point(-73.9857, 40.7484))->get();
```

> Throws `RuntimeException` on geography columns — `ST_Contains` is not supported by the PostGIS geography type.

### `withinGeometry`

```php
withinGeometry(Builder $query, string $column, Geometry $geometry, ?string $typeOverride = null): void
```

Find records where the spatial column is within the given geometry.

```php
$trees = Tree::withinGeometry('location', new Polygon([$boundary], 4326))->get();
```

> Throws `RuntimeException` on geography columns — `ST_Within` is not supported by the PostGIS geography type.

### `intersectsWith`

```php
intersectsWith(Builder $query, string $column, Geometry $geometry, ?string $typeOverride = null): void
```

Find records whose spatial column intersects the given geometry.

```php
$landmarks = Landmark::intersectsWith('location', new Polygon([$area], 4326))->get();
```

> Throws `RuntimeException` on geography columns — `ST_Intersects` is not supported by the PostGIS geography type.

### `withArea`

```php
withArea(Builder $query, string $column, ?string $typeOverride = null): void
```

Append the area (in square meters) of a polygon column to the result set.

```php
$zones = Zone::withArea('boundary')->get();

foreach ($zones as $zone) {
    echo $zone->boundary_area; // square meters
}
```

### `$typeOverride`

When querying across a join (the spatial column belongs to a joined table, not the primary model), pass the type explicitly:

```php
Courier::join('zones', 'couriers.zone_id', '=', 'zones.id')
    ->withinDistanceTo('zones.polygon', $point, 1000, typeOverride: 'geometry');
```

This bypasses the model's `$casts` auto-detection and uses the provided type directly.

### Validation

All spatial scopes validate that the given column is registered with a valid spatial cast (`PointCast`, `PolygonCast`, or `MultiPolygonCast`). They throw `InvalidArgumentException` if the column is missing or uses a non-spatial cast.

## Testing

```bash
composer test
```

The test suite uses Pest with Orchestra Testbench against a PostgreSQL/PostGIS database. See `phpunit.xml.dist` and `tests/bootstrap.php` for configuration.

## License

MIT
