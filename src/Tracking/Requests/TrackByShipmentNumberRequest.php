<?php

namespace SmartDato\GlsItaly\Tracking\Requests;

class TrackByShipmentNumberRequest extends TrackingRequest
{
    public function __construct(
        protected readonly string $departureSede,
        protected readonly string $shipmentNumber,
        protected readonly string $clientCode,
    ) {}

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'locpartenza' => $this->departureSede,
            'NumSped' => $this->shipmentNumber,
            'Cli' => $this->clientCode,
        ];
    }
}
