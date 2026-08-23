<?php

namespace SmartDato\GlsItaly\Tracking\Requests;

class TrackPickupsByDateRangeRequest extends TrackingRequest
{
    public function __construct(
        protected readonly string $departureSede,
        protected readonly string $contractCode,
        protected readonly string $from,
        protected readonly string $to,
    ) {}

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'locpartenza' => $this->departureSede,
            'CodCli' => $this->contractCode,
            'dadata' => $this->from,
            'adata' => $this->to,
            'tiporicerca' => 'ritiri',
        ];
    }
}
