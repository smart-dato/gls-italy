<?php

namespace SmartDato\GlsItaly\Tracking\Requests;

class TrackByBdaRequest extends TrackingRequest
{
    public function __construct(
        protected readonly string $departureSede,
        protected readonly string $bda,
        protected readonly string $contractCode,
    ) {}

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'locpartenza' => $this->departureSede,
            'bda' => $this->bda,
            'CodCli' => $this->contractCode,
        ];
    }
}
