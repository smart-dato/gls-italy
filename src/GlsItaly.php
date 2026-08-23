<?php

namespace SmartDato\GlsItaly;

use SmartDato\GlsItaly\AddressCheck\CheckAddressConnector;
use SmartDato\GlsItaly\AddressCheck\Requests\CheckAddressRequest;
use SmartDato\GlsItaly\AddressCheck\Results\AddressCheckResult;
use SmartDato\GlsItaly\Data\AddressQueryData;
use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\PickupCancellationData;
use SmartDato\GlsItaly\Data\RedeliveryData;
use SmartDato\GlsItaly\Exceptions\ValidationException;
use SmartDato\GlsItaly\LabelService\CloseWorkDayBuilder;
use SmartDato\GlsItaly\LabelService\LabelServiceConnector;
use SmartDato\GlsItaly\LabelService\Requests\DeleteSpedRequest;
use SmartDato\GlsItaly\LabelService\Requests\ListSpedRequest;
use SmartDato\GlsItaly\LabelService\Results\DeleteSpedResult;
use SmartDato\GlsItaly\LabelService\Results\ListSpedResult;
use SmartDato\GlsItaly\LabelService\ShipmentBuilder;
use SmartDato\GlsItaly\Pickups\Legacy\LegacyPickupConnector;
use SmartDato\GlsItaly\Pickups\Legacy\Requests\DeletePickupRequest;
use SmartDato\GlsItaly\Pickups\PickupBuilder;
use SmartDato\GlsItaly\Pickups\Results\DeletePickupResult;
use SmartDato\GlsItaly\StockRelease\Requests\RedeliveryParcelRequest;
use SmartDato\GlsItaly\StockRelease\Results\StockReleaseResult;
use SmartDato\GlsItaly\StockRelease\StockReleaseConnector;
use SmartDato\GlsItaly\Tracking\Requests\TrackByBdaRequest;
use SmartDato\GlsItaly\Tracking\Requests\TrackByShipmentNumberRequest;
use SmartDato\GlsItaly\Tracking\Requests\TrackPickupRequest;
use SmartDato\GlsItaly\Tracking\Requests\TrackRetourRequest;
use SmartDato\GlsItaly\Tracking\Results\PickupTrackingResult;
use SmartDato\GlsItaly\Tracking\Results\ShipmentTrackingResult;
use SmartDato\GlsItaly\Tracking\TrackingConnector;

class GlsItaly
{
    public function __construct(protected ?Credentials $credentials = null) {}

    public function withCredentials(Credentials $credentials): static
    {
        $clone = clone $this;
        $clone->credentials = $credentials;

        return $clone;
    }

    public function shipment(): ShipmentBuilder
    {
        return new ShipmentBuilder($this->labelServiceConnector(), $this->credentials);
    }

    public function closeWorkDay(): CloseWorkDayBuilder
    {
        return new CloseWorkDayBuilder($this->labelServiceConnector(), $this->credentials);
    }

    public function deleteShipment(string $shipmentNumber): DeleteSpedResult
    {
        $response = $this->labelServiceConnector()->call(
            new DeleteSpedRequest($this->requireCredentials(), $shipmentNumber),
        );

        return DeleteSpedResult::fromResponse($response, $shipmentNumber);
    }

    public function listPendingShipments(): ListSpedResult
    {
        $response = $this->labelServiceConnector()->call(
            new ListSpedRequest($this->requireCredentials()),
        );

        return ListSpedResult::fromResponse($response);
    }

    public function pickup(): PickupBuilder
    {
        return new PickupBuilder($this->legacyPickupConnector(), $this->credentials);
    }

    public function cancelPickup(PickupCancellationData $cancellation): DeletePickupResult
    {
        $response = $this->legacyPickupConnector()->call(
            new DeletePickupRequest($this->requireCredentials(), [$cancellation]),
        );

        return DeletePickupResult::fromResponse($response);
    }

    public function trackByShipmentNumber(string $departureSede, string $shipmentNumber, string $clientCode): ShipmentTrackingResult
    {
        return ShipmentTrackingResult::fromResponse($this->trackingConnector()->call(
            new TrackByShipmentNumberRequest($departureSede, $shipmentNumber, $clientCode),
        ));
    }

    public function trackByBda(string $departureSede, string $bda, string $contractCode): ShipmentTrackingResult
    {
        return ShipmentTrackingResult::fromResponse($this->trackingConnector()->call(
            new TrackByBdaRequest($departureSede, $bda, $contractCode),
        ));
    }

    public function trackPickup(string $departureSede, string $pickupNumber, string $contractCode): PickupTrackingResult
    {
        return PickupTrackingResult::fromResponse($this->trackingConnector()->call(
            new TrackPickupRequest($departureSede, $pickupNumber, $contractCode),
        ));
    }

    public function trackRetour(string $departureSede, string $shipmentNumber, string $contractSede, string $contractCode): ShipmentTrackingResult
    {
        return ShipmentTrackingResult::fromResponse($this->trackingConnector()->call(
            new TrackRetourRequest($departureSede, $shipmentNumber, $contractSede, $contractCode),
        ));
    }

    public function releaseStock(RedeliveryData ...$redeliveries): StockReleaseResult
    {
        $response = (new StockReleaseConnector)->call(
            new RedeliveryParcelRequest($this->requireCredentials(), array_values($redeliveries)),
        );

        return StockReleaseResult::fromResponse($response);
    }

    public function checkAddress(AddressQueryData $address): AddressCheckResult
    {
        $response = (new CheckAddressConnector)->call(
            new CheckAddressRequest($this->requireCredentials(), $address),
        );

        return AddressCheckResult::fromResponse($response);
    }

    protected function labelServiceConnector(): LabelServiceConnector
    {
        return new LabelServiceConnector;
    }

    protected function trackingConnector(): TrackingConnector
    {
        return new TrackingConnector;
    }

    protected function legacyPickupConnector(): LegacyPickupConnector
    {
        return new LegacyPickupConnector;
    }

    protected function requireCredentials(): Credentials
    {
        return $this->credentials
            ?? throw new ValidationException('Credentials are required, call withCredentials() first.');
    }
}
