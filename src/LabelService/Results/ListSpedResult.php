<?php

namespace SmartDato\GlsItaly\LabelService\Results;

use Saloon\Http\Response;
use SmartDato\GlsItaly\Data\PendingShipmentData;
use SmartDato\GlsItaly\Support\Tags;

final class ListSpedResult
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
     * @return array<int, PendingShipmentData>
     */
    public function shipments(): array
    {
        return array_map(fn (string $parcelXml): PendingShipmentData => new PendingShipmentData(
            date: Tags::firstIn($parcelXml, 'Data'),
            shipmentNumber: Tags::firstIn($parcelXml, 'NumSpedizione'),
            clientReferences: Tags::firstIn($parcelXml, 'RiferimentiCliente'),
            ddt: Tags::firstIn($parcelXml, 'Ddt'),
            recipientName: Tags::firstIn($parcelXml, 'DenominazioneDestinatario'),
            city: Tags::firstIn($parcelXml, 'CittaDestinatario'),
            province: Tags::firstIn($parcelXml, 'ProvinciaDestinatario'),
            street: Tags::firstIn($parcelXml, 'IndirizzoDestinatario'),
            parcelCount: Tags::firstIn($parcelXml, 'TotaleColli'),
            weight: Tags::firstIn($parcelXml, 'PesoSpedizione'),
            status: Tags::firstIn($parcelXml, 'StatoSpedizione'),
            routing: Tags::firstIn($parcelXml, 'Instradamento'),
        ), Tags::allIn($this->rawResponse, 'Parcel'));
    }

    /**
     * The empty-NumeroSpedizione fallback ported verbatim from
     * GLS_IT_API::getReferenceShipment(): among the shipments waiting for
     * close-of-day, the LAST one matching recipient, city, province and
     * street wins.
     */
    public function findPendingByRecipient(string $recipientName, string $city, string $province, string $street): ?string
    {
        $reference = null;

        foreach (Tags::allIn($this->rawResponse, 'Parcel') as $parcelXml) {
            if (Tags::firstIn($parcelXml, 'StatoSpedizione') !== 'IN ATTESA DI CHIUSURA.') {
                continue;
            }

            if (Tags::firstIn($parcelXml, 'DenominazioneDestinatario') !== $recipientName) {
                continue;
            }

            if (Tags::firstIn($parcelXml, 'CittaDestinatario') !== $city) {
                continue;
            }

            if (Tags::firstIn($parcelXml, 'ProvinciaDestinatario') !== $province) {
                continue;
            }

            if (Tags::firstIn($parcelXml, 'IndirizzoDestinatario') !== $street) {
                continue;
            }

            $reference = Tags::firstIn($parcelXml, 'NumSpedizione');
        }

        return $reference;
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
