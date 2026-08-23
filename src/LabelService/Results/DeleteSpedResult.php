<?php

namespace SmartDato\GlsItaly\LabelService\Results;

use Saloon\Http\Response;
use SmartDato\GlsItaly\Support\Tags;

final class DeleteSpedResult
{
    private function __construct(
        private readonly string $rawRequest,
        private readonly string $rawResponse,
        private readonly string $shipmentNumber,
    ) {}

    public static function fromResponse(Response $response, string $shipmentNumber): self
    {
        return new self(
            (string) $response->getPendingRequest()->body()?->all(),
            $response->body(),
            $shipmentNumber,
        );
    }

    /**
     * Replay a stored request/response pair, e.g. from an API-call audit log.
     */
    public static function fromStrings(string $rawRequest, string $rawResponse, string $shipmentNumber): self
    {
        return new self($rawRequest, $rawResponse, $shipmentNumber);
    }

    /**
     * Ported verbatim from the OLC connector: GLS confirms a deletion with an
     * Italian sentence, not a status code.
     */
    public function succeeded(): bool
    {
        $needleOK = 'Eliminazione della spedizione '.$this->shipmentNumber.' avvenuta.';
        if (mb_strpos($this->rawResponse, $needleOK)) {
            return true;
        }

        return false;
    }

    public function resultText(): ?string
    {
        return Tags::allWithTags($this->rawResponse, 'DeleteSpedResult')[0] ?? null;
    }

    public function rawRequest(): string
    {
        return $this->rawRequest;
    }

    public function rawResponse(): string
    {
        return $this->rawResponse;
    }
}
