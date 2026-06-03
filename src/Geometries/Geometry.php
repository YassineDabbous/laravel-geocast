<?php

namespace Yaseen\GeoCast\Geometries;

interface Geometry
{
    public function getSrid(): int;
    public function toWkt(): string;
    public function toArray(): array;
}