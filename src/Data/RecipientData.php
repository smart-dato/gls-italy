<?php

namespace SmartDato\GlsItaly\Data;

use Spatie\LaravelData\Data;

/**
 * The delivery side of an AddParcel record. All values are passed raw — the
 * serializer applies the GLS field formatters (alfa/alfaStreetCity/alfaEmail)
 * exactly as the legacy connector did.
 */
final class RecipientData extends Data
{
    public function __construct(
        public string $name,
        public string $street,
        public string $city,
        public string $zipcode,
        public string $province = '',
        public string $email = '',
        public string $phone = '',
    ) {}
}
