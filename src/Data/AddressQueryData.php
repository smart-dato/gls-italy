<?php

namespace SmartDato\GlsItaly\Data;

use Spatie\LaravelData\Data;

final class AddressQueryData extends Data
{
    public function __construct(
        public string $province,
        public string $zipcode,
        public string $city,
        public string $street,
    ) {}
}
