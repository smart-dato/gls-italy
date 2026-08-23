<?php

namespace SmartDato\GlsItaly\AddressCheck\Results;

use Saloon\Http\Response;
use SmartDato\GlsItaly\Support\Tags;

/**
 * The CheckAddressResult payload: an untyped XML fragment whose <Esito> text
 * states the verdict ("Destinazione corretta.", "Frazione/Comune non
 * identificata...", "Indirizzo non identificato..."). The consumer maps the
 * verdict onto its own rating scale.
 */
final class AddressCheckResult
{
    private function __construct(
        private readonly string $rawRequest,
        private readonly string $rawResponse,
    ) {}

    public static function fromResponse(Response $response): self
    {
        return new self(
            (string) $response->getPendingRequest()->body()?->all(),
            $response->body(),
        );
    }

    /**
     * Replay a stored request/response pair, e.g. from an API-call audit log.
     */
    public static function fromStrings(string $rawRequest, string $rawResponse): self
    {
        return new self($rawRequest, $rawResponse);
    }

    public function esito(): ?string
    {
        $esito = Tags::firstIn($this->rawResponse, 'Esito');

        if ($esito !== null) {
            return html_entity_decode($esito, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        $decoded = html_entity_decode($this->rawResponse, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return Tags::firstIn($decoded, 'Esito');
    }

    public function resultXml(): ?string
    {
        return Tags::firstIn($this->rawResponse, 'CheckAddressResult');
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
