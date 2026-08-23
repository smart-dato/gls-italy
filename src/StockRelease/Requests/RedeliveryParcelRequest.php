<?php

namespace SmartDato\GlsItaly\StockRelease\Requests;

use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\RedeliveryData;
use SmartDato\GlsItaly\Support\Alfa;
use SmartDato\GlsItaly\Support\LegacyXmlRequest;

class RedeliveryParcelRequest extends LegacyXmlRequest
{
    /**
     * @param  array<int, RedeliveryData>  $redeliveries
     */
    public function __construct(
        protected readonly Credentials $credentials,
        protected readonly array $redeliveries,
    ) {}

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function bodyXml(): string
    {
        $inner = $this->tag('SedeProfiloGls', $this->credentials->sede)
            .$this->tag('CodiceProfiloGls', $this->credentials->clientCode)
            .$this->tag('PasswordClienteGls', $this->credentials->password);

        foreach ($this->redeliveries as $redelivery) {
            $inner .= $this->tag('Redelivery', $this->redeliverySegment($redelivery), false);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'.$this->tag('Info', $inner, false);
    }

    protected function redeliverySegment(RedeliveryData $redelivery): string
    {
        $segment = $this->tag('NumeroSpedizione', $redelivery->shipmentNumber);
        $segment .= $this->tag('TipoSvincolo', $redelivery->releaseType);
        $segment .= $this->tag('TelefonoCliente', $redelivery->clientPhone);
        $segment .= $this->tag('ConPreavvisoTelefonico', $redelivery->notifyRecipientByPhone ? 'S' : 'N');
        $segment .= $this->tag('TelefonoDestinatario', $redelivery->recipientPhone);
        $segment .= $this->tag('AnnullandoCAssegno', $redelivery->cancelCashOnDelivery ? 'S' : 'N');
        $segment .= $this->tag('VariandoCAssegno', $redelivery->changeCashOnDelivery ? 'S' : 'N');
        $segment .= $this->tag('NuovoImportoCAssegno', $redelivery->newCashOnDeliveryAmount === null ? '' : Alfa::num($redelivery->newCashOnDeliveryAmount));
        $segment .= $this->tag('VariandoInPortoFranco', $redelivery->changeToPortoFranco ? 'S' : 'N');
        $segment .= $this->tag('VariandoInPortoAssegnato', $redelivery->changeToPortoAssegnato ? 'S' : 'N');
        $segment .= $this->tag('SpeseAdMittente', $redelivery->chargesToSender ? 'S' : 'N');
        $segment .= $this->tag('SpeseAdDestinatario', $redelivery->chargesToRecipient ? 'S' : 'N');
        $segment .= $this->tag('DataRiconsegna', $redelivery->redeliveryDate?->format('d/m/y') ?? '');
        $segment .= $this->tag('Note', $redelivery->note);

        $recipient = $redelivery->newRecipient;
        if ($recipient === null) {
            return $segment
                .$this->tag('CognomeNome')
                .$this->tag('NuovoIndirizzo')
                .$this->tag('NuovaLocalita')
                .$this->tag('NuovoZipcode')
                .$this->tag('NuovaProvincia');
        }

        $segment .= $this->tag('CognomeNome', $recipient->name);
        $segment .= $this->tag('NuovoIndirizzo', $recipient->street);
        $segment .= $this->tag('NuovaLocalita', $recipient->city);
        $segment .= $this->tag('NuovoZipcode', $recipient->zipcode);
        $segment .= $this->tag('NuovaProvincia', $recipient->province);

        return $segment;
    }
}
