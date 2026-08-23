<?php

namespace SmartDato\GlsItaly\Data;

use DateTimeInterface;
use Spatie\LaravelData\Data;

/**
 * One <Ritiro> record of the legacy pickup channel (MU302 §3.2 /
 * addpickup.php). A null delivery address sends the destination block empty —
 * GLS then treats the pickup as aggregated/detour routed.
 */
final class PickupData extends Data
{
    /**
     * @param  array<int, string>  $services  two-character GLS accessory service codes
     */
    public function __construct(
        public string $contractCode,
        public string $requesterName,
        public string $bda,
        public RecipientData $pickupAddress,
        public DateTimeInterface $pickupDate,
        public int $parcelCount = 1,
        public float $weight = 0.0,
        public ?RecipientData $deliveryAddress = null,
        public string $note = '',
        public string $notifyEmail = '',
        public string $phone = '',
        public ?string $pickupLocationEmail = null,
        public array $services = [],
        public string $morningFrom = '08',
        public string $morningTo = '13',
        public string $afternoonFrom = '13',
        public string $afternoonTo = '18',
        public string $parcelType = '0',
    ) {}
}
