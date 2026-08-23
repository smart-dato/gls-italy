<?php

namespace SmartDato\GlsItaly\Pickups\Legacy\Requests;

use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\PickupData;
use SmartDato\GlsItaly\Data\RecipientData;
use SmartDato\GlsItaly\Support\Alfa;

class AddPickupRequest extends LegacyXmlRequest
{
    /**
     * @param  array<int, PickupData>  $pickups
     */
    public function __construct(
        protected readonly Credentials $credentials,
        protected readonly array $pickups,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/addpickup.php';
    }

    public function bodyXml(): string
    {
        $inner = $this->tag('SedeGls', $this->credentials->sede)
            .$this->tag('CodiceProfiloGls', $this->credentials->clientCode)
            .$this->tag('PasswordClienteGls', $this->credentials->password);

        foreach ($this->pickups as $pickup) {
            $inner .= $this->tag('Ritiro', $this->pickupSegment($pickup), false);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'.$this->tag('Info', $inner, false);
    }

    protected function pickupSegment(PickupData $pickup): string
    {
        $segment = $this->tag('CodiceContrattoGls', $pickup->contractCode);
        $segment .= $this->tag('NomeRichiedente', $pickup->requesterName);
        $segment .= $this->tag('Bda', $pickup->bda);
        $segment .= $this->tag('Colli', $pickup->parcelCount);
        $segment .= $this->tag('Bancali');
        $segment .= $this->tag('FuoriSagoma');
        $segment .= $this->tag('MisureColli', $pickup->parcelCount.',1,1,1');
        $segment .= $this->tag('PesoReale', Alfa::num($pickup->weight));
        $segment .= $this->tag('PesoVolume');
        $segment .= $this->tag('DataRitiro', $pickup->pickupDate->format('d/m/Y'));
        $segment .= $this->tag('Dalle1', $pickup->morningFrom);
        $segment .= $this->tag('Alle1', $pickup->morningTo);
        $segment .= $this->tag('Dalle2', $pickup->afternoonFrom);
        $segment .= $this->tag('Alle2', $pickup->afternoonTo);
        $segment .= $this->tag('TipoCollo', $pickup->parcelType);

        $pickupAddress = $pickup->pickupAddress;
        $segment .= $this->tag('Clienteritiro', $pickupAddress->name);
        $segment .= $this->tag('Indirizzoritiro', $pickupAddress->street);
        $segment .= $this->tag('Localitaritiro', $pickupAddress->city);
        $segment .= $this->tag('CAPritiro', $pickupAddress->zipcode);
        $segment .= $this->tag('Provinciaritiro', $pickupAddress->province);

        $segment .= $this->deliverySegment($pickup->deliveryAddress);

        $segment .= $this->tag('NoteSpedizione', $pickup->note);
        $segment .= $this->tag('EmailRichiedente', $pickup->notifyEmail);
        $segment .= $this->tag('Telefono', $pickup->phone);
        $segment .= $this->tag('ServiziAccessori', implode(',', $pickup->services));
        $segment .= $this->tag('EmailLuogoritiro', $pickup->pickupLocationEmail ?? $pickup->notifyEmail);

        return $segment;
    }

    protected function deliverySegment(?RecipientData $delivery): string
    {
        if ($delivery === null) {
            return $this->tag('Clientedestinatario')
                .$this->tag('Indirizzodestinatario')
                .$this->tag('Localitadestinatario')
                .$this->tag('CAPdestinatario')
                .$this->tag('Provinciadestinatario')
                .$this->tag('Telefonodestinatario')
                .$this->tag('Emaildestinatario');
        }

        return $this->tag('Clientedestinatario', $delivery->name)
            .$this->tag('Indirizzodestinatario', $delivery->street)
            .$this->tag('Localitadestinatario', $delivery->city)
            .$this->tag('CAPdestinatario', $delivery->zipcode)
            .$this->tag('Provinciadestinatario', $delivery->province)
            .$this->tag('Telefonodestinatario', $delivery->phone)
            .$this->tag('Emaildestinatario', strtolower($delivery->email));
    }
}
