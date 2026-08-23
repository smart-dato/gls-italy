<?php

namespace SmartDato\GlsItaly\LabelService\Results;

use Saloon\Http\Response;
use SmartDato\GlsItaly\Support\Tags;

final class CloseWorkDayResult
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

    /**
     * Ported verbatim from the OLC connector: success is the literal substring
     * check the connector has always used, including its truthiness quirk.
     */
    public function succeeded(): bool
    {
        $needleOK = '<DescrizioneErrore xmlns="">OK</DescrizioneErrore>';
        if (mb_strpos($this->rawResponse, $needleOK)) {
            return true;
        }

        return false;
    }

    public function manifestPdf(): ?string
    {
        $encoded = Tags::firstIn($this->rawResponse, 'DistintaPDF');

        if ($encoded === null) {
            return null;
        }

        $decoded = base64_decode($encoded, true);

        return $decoded === false ? null : $decoded;
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
