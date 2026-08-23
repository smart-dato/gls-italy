<?php

namespace SmartDato\GlsItaly\Data;

use SmartDato\GlsItaly\Enums\LabelFormat;
use Spatie\LaravelData\Data;

/**
 * One <Parcel> record of the label service — AddParcel sends exactly one,
 * CloseWorkDay repeats one per parcel to identify the shipments to close.
 */
final class ShipmentData extends Data
{
    /**
     * @param  array<int, string>  $services  two-character GLS accessory service codes, e.g. ['01', '36']
     */
    public function __construct(
        public string $contractCode,
        public RecipientData $recipient,
        public ParcelData $parcel,
        public float $cashOnDelivery = 0.0,
        public ?string $codCollectionMode = null,
        public string $note = '',
        public string $additionalNote = '',
        public string $clientReference = '',
        public string $bda = '',
        public array $services = [],
        public LabelFormat $labelFormat = LabelFormat::Pdf,
        public ?int $progressiveCounter = null,
        public ?string $deliveryTimeNote = null,
    ) {}
}
