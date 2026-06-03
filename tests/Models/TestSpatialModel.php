<?php

namespace Yaseen\GeoCast\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Yaseen\GeoCast\Casters\PointCast;
use Yaseen\GeoCast\Casters\PolygonCast;
use Yaseen\GeoCast\Spatialable;

class TestSpatialModel extends Model
{
    use Spatialable;

    protected $table = 'test_spatial_locations';

    public $timestamps = false;

    protected $casts = [
        'location' => PointCast::class,
        'boundary' => PolygonCast::class,
    ];

    protected $fillable = ['name', 'location', 'boundary'];
}
