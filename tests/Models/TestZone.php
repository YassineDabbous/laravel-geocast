<?php

namespace Yaseen\GeoCast\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Yaseen\GeoCast\Casters\PolygonCast;

class TestZone extends Model
{
    protected $table = 'test_zones';

    public $timestamps = false;

    protected $casts = [
        'area' => PolygonCast::class,
    ];

    protected $fillable = ['name', 'area'];
}
