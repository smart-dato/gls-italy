<?php

namespace SmartDato\GlsItaly\Tracking\Requests;

class TrackPickupRequest extends TrackingRequest
{
    public function __construct(
        protected readonly string $departureSede,
        protected readonly string $pickupNumber,
        protected readonly string $contractCode,
    ) {}

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'locpartenza' => $this->departureSede,
            'numrit' => $this->pickupNumber,
            'CodCli' => $this->contractCode,
        ];
    }
}
