<?php

declare(strict_types=1);

namespace Yaseen\GeoCast;

use GeoIO\Factory;
use Yaseen\GeoCast\Geometries\Point;
use Yaseen\GeoCast\Geometries\Polygon;

class MyGeoFactory implements Factory
{
    public static function parser() : \GeoIO\WKB\Parser\Parser
    {
        return new \GeoIO\WKB\Parser\Parser(new self());
    }

    public function createPoint($dimension, array $coordinates, $srid = null)
    {
        return new Point(lng: $coordinates['x'], lat: $coordinates['y'], srid: $srid ?? 4326);
    }

    public function createLinearRing($dimension, array $points, $srid = null)
    {
        // A LinearRing is just an array of Points parsed by GeoIO
        return $points;
    }

    public function createPolygon($dimension, array $lineStrings, $srid = null)
    {
        // GeoIO passes an array of rings (which we set up as arrays of Points above)
        return new Polygon(rings: $lineStrings, srid: $srid ?? 4326);
    }

    // Keep your other stubs intact...
    public function createLineString($dimension, array $points, $srid = null){}
    public function createMultiPoint($dimension, array $points, $srid = null){}
    public function createMultiLineString($dimension, array $lineStrings, $srid = null){}
    public function createMultiPolygon($dimension, array $polygons, $srid = null){}
    public function createGeometryCollection($dimension, array $geometries, $srid = null){}
}