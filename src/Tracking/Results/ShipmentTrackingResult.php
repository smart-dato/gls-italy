<?php

namespace SmartDato\GlsItaly\Tracking\Results;

use Saloon\Http\Response;
use SimpleXMLElement;
use SmartDato\GlsItaly\Data\TrackingEventData;

final class ShipmentTrackingResult
{
    use ParsesTrackingEvents;

    private function __construct(
        private readonly string $url,
        private readonly string $rawResponse,
    ) {}

    public static function fromResponse(Response $response): self
    {
        return new self((string) $response->getPsrRequest()->getUri(), $response->body());
    }

    /**
     * Replay a stored response, e.g. from a tracking log.
     */
    public static function fromStrings(string $rawResponse, string $url = ''): self
    {
        return new self($url, $rawResponse);
    }

    /**
     * @return array<int, TrackingEventData>
     */
    public function events(): array
    {
        return $this->walkShipmentEvents($this->xml());
    }

    public function shipmentNumber(): ?string
    {
        $number = $this->xml()?->SPEDIZIONE?->NumSped;

        return $number === null ? null : (string) $number;
    }

    public function departureSede(): ?string
    {
        $sede = $this->xml()?->SPEDIZIONE?->SedePartenza;

        return $sede === null ? null : (string) $sede;
    }

    public function destinationSede(): ?string
    {
        $sede = $this->xml()?->SPEDIZIONE?->SedeDestinazione;

        return $sede === null ? null : (string) $sede;
    }

    /**
     * The T&T feed announces a created retour inside a tracking note; the
     * legacy parser split it into the depot sigla and the retour number.
     *
     * @return array{sede: string, number: string}|null
     */
    public function retourReference(): ?array
    {
        $note = (string) $this->xml()?->SPEDIZIONE?->TRACKING?->Note;

        if (! str_starts_with($note, 'Resa con spedizione:')) {
            return null;
        }

        preg_match('/(\w{2})(\d{9})/', $note, $matches);

        if (count($matches) == 3) {
            return ['sede' => trim($matches[1]), 'number' => $matches[2]];
        }

        return null;
    }

    public function errorText(): ?string
    {
        $xml = $this->xml();

        if ($xml === null || ! isset($xml->TESTOERRORE)) {
            return null;
        }

        return (string) $xml->TESTOERRORE;
    }

    public function url(): string
    {
        return $this->url;
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

        $xml = @simplexml_load_string($this->rawResponse, null, LIBXML_NOCDATA);

        return $xml === false ? null : $xml;
    }
}
