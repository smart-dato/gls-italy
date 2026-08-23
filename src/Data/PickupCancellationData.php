<?php

namespace SmartDato\GlsItaly\Data;

use Spatie\LaravelData\Data;

/**
 * One <Ritiro> record of a deletepickup.php call (MU302 §3.3). The pickup
 * number includes the depot letters, e.g. "E19570000071".
 */
final class PickupCancellationData extends Data
{
    public function __construct(
        public string $contractCode,
        public string $pickupNumber,
        public string $requesterName,
        public string $email,
    ) {}
}
