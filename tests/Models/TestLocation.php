<?php

namespace Yaseen\GeoCast\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Yaseen\GeoCast\Casters\PointCast;

class TestLocation extends Model
{
    protected $table = 'test_locations';

    public $timestamps = false;

    protected $casts = [
        'location' => PointCast::class,
    ];

    protected $fillable = ['name', 'location'];
}
