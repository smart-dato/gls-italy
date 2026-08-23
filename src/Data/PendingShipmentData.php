<?php

namespace SmartDato\GlsItaly\Data;

use Spatie\LaravelData\Data;

/**
 * One <Parcel> entry of a ListSped response (MU162 §5.14).
 */
final class PendingShipmentData extends Data
{
    public function __construct(
        public ?string $date = null,
        public ?string $shipmentNumber = null,
        public ?string $clientReferences = null,
        public ?string $ddt = null,
        public ?string $recipientName = null,
        public ?string $city = null,
        public ?string $province = null,
        public ?string $street = null,
        public ?string $parcelCount = null,
        public ?string $weight = null,
        public ?string $status = null,
        public ?string $routing = null,
    ) {}
}
