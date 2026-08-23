<?php

namespace SmartDato\GlsItaly\Pickups\Results;

use Saloon\Http\Response;
use SimpleXMLElement;

/**
 * The RisultatiAnnullamentoRitiri payload of a deletepickup.php call
 * (MU302 §3.3).
 */
final class DeletePickupResult
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
     * @return array<int, string> the cancelled pickup numbers
     */
    public function cancelledNumbers(): array
    {
        $xml = $this->xml();

        if ($xml === null) {
            return [];
        }

        $numbers = [];
        foreach ($xml->RitiroAnnullato as $cancelled) {
            $numbers[] = trim((string) $cancelled['NumeroRitiro']);
        }

        return $numbers;
    }

    /**
     * @return array<string, string> pickup number => error message
     */
    public function errors(): array
    {
        $xml = $this->xml();

        if ($xml === null) {
            return [];
        }

        $errors = [];
        foreach ($xml->ErroreAnnullamento as $failed) {
            $errors[trim((string) $failed['NumeroRitiro'])] = (string) $failed['Errore'];
        }

        return $errors;
    }

    public function cancelled(string $pickupNumber): bool
    {
        return in_array($pickupNumber, $this->cancelledNumbers(), true);
    }

    public function rawRequest(): string
    {
        return $this->rawRequest;
    }

    public function rawResponse(): string
    {
        return $this->rawResponse;
    }

    private function xml(): ?SimpleXMLElement
    {
        if (strlen($this->rawResponse) === 0) {
            return null;
        }

        $xml = @simplexml_load_string($this->rawResponse);

        return $xml === false ? null : $xml;
    }
}
