<?php

namespace SmartDato\GlsItaly\LabelService\Results;

use Illuminate\Support\Facades\Storage;
use Saloon\Http\Response;
use SmartDato\GlsItaly\Exceptions\ResponseParseException;
use SmartDato\GlsItaly\Exceptions\StorageException;
use SmartDato\GlsItaly\Support\PdfLabelBarcodeExtractor;
use SmartDato\GlsItaly\Support\Tags;
use Throwable;

/**
 * The InfoLabel payload of an AddParcel call. Accessors preserve the legacy
 * OLC semantics: the barcode assembly skips the destination suffix for
 * "GLS Check" routings and collapses to '' when tags are missing, and the
 * shipment number is '' (not null) when GLS returned none — the consumer is
 * expected to fall back to ListSped in that case.
 */
final class AddParcelResult
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

    public function shipmentNumber(): string
    {
        return Tags::firstIn($this->rawResponse, 'NumeroSpedizione') ?? '';
    }

    public function hasShipmentNumber(): bool
    {
        return $this->shipmentNumber() !== '';
    }

    public function barcode(): string
    {
        $destinationDescription = $this->destinationDescription();

        try {
            $barcode = $this->requiredTag('SiglaMittente')
                .$this->requiredTag('NumeroSpedizione')
                .$this->requiredTag('TotaleColli');

            if ($destinationDescription !== 'GLS Check') {
                $barcode .= $this->requiredTag('TipoCollo').$this->requiredTag('SiglaSedeDestino');
            }
        } catch (ResponseParseException) {
            return '';
        }

        return $barcode;
    }

    public function pdf(): ?string
    {
        $encoded = Tags::firstIn($this->rawResponse, 'PdfLabel');

        if ($encoded === null) {
            return null;
        }

        $decoded = base64_decode($encoded, true);

        return $decoded === false ? null : $decoded;
    }

    public function zpl(): ?string
    {
        return Tags::firstIn($this->rawResponse, 'Zpl');
    }

    public function extractedLabelBarcode(): string
    {
        $label = $this->pdf() ?? $this->zpl();

        if ($label === null) {
            return '';
        }

        try {
            return new PdfLabelBarcodeExtractor()->extract($label, $this->shipmentNumber());
        } catch (Throwable) {
            return '';
        }
    }

    public function senderSigla(): ?string
    {
        return Tags::firstIn($this->rawResponse, 'SiglaMittente');
    }

    public function totalParcels(): ?string
    {
        return Tags::firstIn($this->rawResponse, 'TotaleColli');
    }

    public function parcelType(): ?string
    {
        return Tags::firstIn($this->rawResponse, 'TipoCollo');
    }

    public function destinationSigla(): ?string
    {
        return Tags::firstIn($this->rawResponse, 'SiglaSedeDestino');
    }

    public function destinationDescription(): ?string
    {
        return Tags::firstIn($this->rawResponse, 'DescrizioneSedeDestino');
    }

    public function errorDescription(): ?string
    {
        return Tags::firstIn($this->rawResponse, 'soap:Text');
    }

    public function storePdf(string $path, ?string $disk = null): self
    {
        $pdf = $this->pdf();

        if ($pdf === null) {
            throw ResponseParseException::missingTag('PdfLabel');
        }

        if (Storage::disk($disk)->put($path, $pdf) === false) {
            throw StorageException::writeFailed($path, $disk);
        }

        return $this;
    }

    public function storeZpl(string $path, ?string $disk = null): self
    {
        $zpl = $this->zpl();

        if ($zpl === null) {
            throw ResponseParseException::missingTag('Zpl');
        }

        if (Storage::disk($disk)->put($path, $zpl) === false) {
            throw StorageException::writeFailed($path, $disk);
        }

        return $this;
    }

    public function rawRequest(): string
    {
        return $this->rawRequest;
    }

    public function rawResponse(): string
    {
        return $this->rawResponse;
    }

    private function requiredTag(string $tag): string
    {
        return Tags::firstIn($this->rawResponse, $tag) ?? throw ResponseParseException::missingTag($tag);
    }
}
