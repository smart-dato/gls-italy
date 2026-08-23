<?php

namespace SmartDato\GlsItaly\Pickups\Results;

use Saloon\Http\Response;
use SimpleXMLElement;
use SmartDato\GlsItaly\Exceptions\ApiErrorException;

/**
 * The RisultatiRitiri payload of an addpickup.php call. Error precedence is
 * ported verbatim from the OLC connector: RitiroErrato@Errore, then the root
 * <Errore>, then the generic fallback message.
 */
final class AddPickupResult
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

    public function pickupNumber(): ?string
    {
        $xml = $this->xml();

        if ($xml === null) {
            return null;
        }

        if ($xml->RitiroCreato) {
            return (string) $xml->RitiroCreato['NumeroRitiro'];
        }

        return null;
    }

    public function succeeded(): bool
    {
        return $this->pickupNumber() !== null;
    }

    public function error(): ?string
    {
        if (strlen($this->rawResponse) === 0) {
            return 'no response';
        }

        $xml = $this->xml();

        if ($xml === null) {
            return 'no valid Response from GLS Italy';
        }

        if ($xml->RitiroCreato) {
            return null;
        }

        if (isset($xml->RitiroErrato)) {
            return (string) $xml->RitiroErrato['Errore'];
        }

        if (isset($xml->Errore)) {
            return (string) $xml->Errore;
        }

        return 'no valid Response from GLS Italy';
    }

    /**
     * @throws ApiErrorException
     */
    public function ensureCreated(): string
    {
        return $this->pickupNumber() ?? throw new ApiErrorException((string) $this->error());
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
