<?php

namespace SmartDato\GlsItaly\StockRelease\Results;

use Saloon\Http\Response;
use SimpleXMLElement;

/**
 * The <Risultati> payload of a redelivery_parcel.php call (MU276 §3.4): one
 * <Spedizione Numero="..."> per shipment, with "Ok" or the blocking error in
 * its <Svincolo> tag.
 */
final class StockReleaseResult
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
     * @return array<string, string> shipment number => Svincolo outcome text
     */
    public function outcomes(): array
    {
        $xml = $this->xml();

        if ($xml === null) {
            return [];
        }

        $outcomes = [];
        foreach ($xml->Spedizione as $shipment) {
            $outcomes[trim((string) $shipment['Numero'])] = (string) $shipment->Svincolo;
        }

        return $outcomes;
    }

    public function outcome(string $shipmentNumber): ?string
    {
        return $this->outcomes()[$shipmentNumber] ?? null;
    }

    public function released(string $shipmentNumber): bool
    {
        return $this->outcome($shipmentNumber) === 'Ok';
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
