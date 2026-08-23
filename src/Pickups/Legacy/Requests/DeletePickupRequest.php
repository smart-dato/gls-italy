<?php

namespace SmartDato\GlsItaly\Pickups\Legacy\Requests;

use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\PickupCancellationData;
use SmartDato\GlsItaly\Support\LegacyXmlRequest;

class DeletePickupRequest extends LegacyXmlRequest
{
    /**
     * @param  array<int, PickupCancellationData>  $cancellations
     */
    public function __construct(
        protected readonly Credentials $credentials,
        protected readonly array $cancellations,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/deletepickup.php';
    }

    public function bodyXml(): string
    {
        $inner = $this->tag('SedeGls', $this->credentials->sede)
            .$this->tag('CodiceProfiloGls', $this->credentials->clientCode)
            .$this->tag('PasswordClienteGls', $this->credentials->password);

        foreach ($this->cancellations as $cancellation) {
            $segment = $this->tag('CodiceContrattoGls', $cancellation->contractCode)
                .$this->tag('NumeroRitiro', $cancellation->pickupNumber)
                .$this->tag('NomeCognome', $cancellation->requesterName)
                .$this->tag('Email', $cancellation->email);

            $inner .= $this->tag('Ritiro', $segment, false);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'.$this->tag('Info', $inner, false);
    }
}
