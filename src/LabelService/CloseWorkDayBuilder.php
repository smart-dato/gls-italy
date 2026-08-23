<?php

namespace SmartDato\GlsItaly\LabelService;

use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\ShipmentData;
use SmartDato\GlsItaly\Exceptions\ValidationException;
use SmartDato\GlsItaly\LabelService\Requests\CloseWorkDayRequest;
use SmartDato\GlsItaly\LabelService\Results\CloseWorkDayResult;

class CloseWorkDayBuilder
{
    /** @var array<int, ShipmentData> */
    protected array $shipments = [];

    public function __construct(
        protected readonly LabelServiceConnector $connector,
        protected ?Credentials $credentials = null,
    ) {}

    public function credentials(Credentials $credentials): self
    {
        $this->credentials = $credentials;

        return $this;
    }

    public function addShipment(ShipmentData $shipment): self
    {
        $this->shipments[] = $shipment;

        return $this;
    }

    /**
     * @throws ValidationException
     */
    public function send(): CloseWorkDayResult
    {
        if ($this->credentials === null) {
            throw new ValidationException('Credentials are required, call GlsItaly::withCredentials() or credentials() before send().');
        }

        if ($this->shipments === []) {
            throw new ValidationException('At least one shipment is required, call addShipment() before send().');
        }

        $response = $this->connector->call(new CloseWorkDayRequest($this->credentials, $this->shipments));

        return CloseWorkDayResult::fromResponse($response);
    }
}
