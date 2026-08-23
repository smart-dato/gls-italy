<?php

namespace SmartDato\GlsItaly\Data;

use DateTimeInterface;
use Spatie\LaravelData\Data;

/**
 * One <Redelivery> record of a redelivery_parcel.php call (MU276 §3.2). The
 * shipment number includes the depot sigla, e.g. "BZ620873098"; the release
 * type is a TipoSvincolo code — see Enums\StockReleaseType. A new recipient
 * is only sent for release type 2 (deliver to another address).
 */
final class RedeliveryData extends Data
{
    public function __construct(
        public string $shipmentNumber,
        public string $releaseType,
        public string $clientPhone = '',
        public bool $notifyRecipientByPhone = false,
        public string $recipientPhone = '',
        public bool $cancelCashOnDelivery = false,
        public bool $changeCashOnDelivery = false,
        public ?float $newCashOnDeliveryAmount = null,
        public bool $changeToPortoFranco = false,
        public bool $changeToPortoAssegnato = false,
        public bool $chargesToSender = false,
        public bool $chargesToRecipient = false,
        public ?DateTimeInterface $redeliveryDate = null,
        public string $note = '',
        public ?RecipientData $newRecipient = null,
    ) {}
}
