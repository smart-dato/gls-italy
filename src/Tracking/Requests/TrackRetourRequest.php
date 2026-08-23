<?php

namespace SmartDato\GlsItaly\Tracking\Requests;

class TrackRetourRequest extends TrackingRequest
{
    public function __construct(
        protected readonly string $departureSede,
        protected readonly string $shipmentNumber,
        protected readonly string $contractSede,
        protected readonly string $contractCode,
    ) {}

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'locpartenza' => $this->departureSede,
            'NumSped' => $this->shipmentNumber,
            'sedecon' => $this->contractSede,
            'CodCli' => $this->contractCode,
        ];
    }
}
