<?php

namespace Yaseen\GeoCast\Casts\Spatial\Geometries;

interface Geometry
{
    public function getSrid(): int;
    public function toWkt(): string;
    public function toArray(): array;
}