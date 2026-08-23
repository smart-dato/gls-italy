<?php

namespace SmartDato\GlsItaly\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SmartDato\GlsItaly\GlsItaly withCredentials(\SmartDato\GlsItaly\Data\Credentials $credentials)
 * @method static \SmartDato\GlsItaly\LabelService\ShipmentBuilder shipment()
 * @method static \SmartDato\GlsItaly\LabelService\CloseWorkDayBuilder closeWorkDay()
 * @method static \SmartDato\GlsItaly\LabelService\Results\DeleteSpedResult deleteShipment(string $shipmentNumber)
 * @method static \SmartDato\GlsItaly\LabelService\Results\ListSpedResult listPendingShipments()
 * @method static \SmartDato\GlsItaly\Pickups\PickupBuilder pickup()
 * @method static \SmartDato\GlsItaly\Pickups\Results\DeletePickupResult cancelPickup(\SmartDato\GlsItaly\Data\PickupCancellationData $cancellation)
 * @method static \SmartDato\GlsItaly\Tracking\Results\ShipmentTrackingResult trackByShipmentNumber(string $departureSede, string $shipmentNumber, string $clientCode)
 * @method static \SmartDato\GlsItaly\Tracking\Results\ShipmentTrackingResult trackByBda(string $departureSede, string $bda, string $contractCode)
 * @method static \SmartDato\GlsItaly\Tracking\Results\PickupTrackingResult trackPickup(string $departureSede, string $pickupNumber, string $contractCode)
 * @method static \SmartDato\GlsItaly\Tracking\Results\ShipmentTrackingResult trackRetour(string $departureSede, string $shipmentNumber, string $contractSede, string $contractCode)
 *
 * @see \SmartDato\GlsItaly\GlsItaly
 */
class GlsItaly extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SmartDato\GlsItaly\GlsItaly::class;
    }
}
