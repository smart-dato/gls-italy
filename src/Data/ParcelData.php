<?php

namespace SmartDato\GlsItaly\Data;

use Spatie\LaravelData\Data;

final class ParcelData extends Data
{
    public function __construct(
        public float $weight = 0.0,
        public float $volume = 0.0,
    ) {}
}
