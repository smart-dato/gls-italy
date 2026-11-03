<?php

namespace SmartDato\GlsItaly\LabelService\Xml;

use SimpleXMLElement;
use SmartDato\GlsItaly\Data\ShipmentData;
use SmartDato\GlsItaly\Exceptions\ValidationException;
use SmartDato\GlsItaly\Support\Alfa;

/**
 * Ported field for field from GLS_IT_XML::makeSingleAddParcelRequest — the
 * element order, the constants (Colli 1, Incoterm 0, TipoPorto F, TipoCollo 0,
 * empty FormatoPdf) and the formatter choices are all part of the wire format.
 */
class ParcelXmlSerializer
{
    public function serialize(ShipmentData $shipment): SimpleXMLElement
    {
        $hasShop = $shipment->partnerShopId !== null || $shipment->shopId !== null;
        if ($hasShop && (trim($shipment->partnerShopId ?? '') === '' || trim($shipment->shopId ?? '') === '')) {
            throw new ValidationException('Shop delivery requires both a partner shop ID and a shop ID.');
        }

        if ($hasShop && $shipment->cashOnDelivery > 0.0) {
            throw new ValidationException('GLS Italy shop delivery does not support cash on delivery.');
        }

        if ($hasShop && (! preg_match('/\A[A-Za-z0-9_-]+\z/', $shipment->partnerShopId ?? '') || ! preg_match('/\A[A-Za-z0-9_-]+\z/', $shipment->shopId ?? ''))) {
            throw new ValidationException('Shop delivery IDs may contain only letters, digits, underscores and hyphens.');
        }

        $recipient = $shipment->recipient;

        $xml = new SimpleXMLElement('<Parcel/>');
        $xml->addChild('CodiceContrattoGls', $shipment->contractCode);
        $xml->addChild('RagioneSociale', Alfa::alfa($recipient->name, 35));
        $xml->addChild('Indirizzo', Alfa::alfaStreetCity($recipient->street, 35));
        $xml->addChild('Localita', Alfa::alfaStreetCity($recipient->city, 30));
        $xml->addChild('Zipcode', $recipient->zipcode);
        $xml->addChild('Provincia', $recipient->province);
        $xml->addChild('Colli', '1');
        $xml->addChild('Incoterm', '0');
        $xml->addChild('PesoReale', Alfa::num($shipment->parcel->weight));
        $xml->addChild('ImportoContrassegno', Alfa::num($shipment->cashOnDelivery));
        $xml->addChild('NoteSpedizione', Alfa::alfa($shipment->note, 40));
        $xml->addChild('TipoPorto', 'F');
        $xml->addChild('PesoVolume', Alfa::num($shipment->parcel->volume));
        $xml->addChild('TipoCollo', '0');
        if ($shipment->codCollectionMode !== null) {
            $xml->addChild('ModalitaIncasso', $shipment->codCollectionMode);
        }
        $xml->addChild('RiferimentoCliente', Alfa::alfa($shipment->clientReference, 40));
        $xml->addChild('NoteAggiuntive', Alfa::alfa($shipment->additionalNote, 40));
        $xml->addChild('CodiceClienteDestinatario', '');
        $xml->addChild('Email', Alfa::alfaEmail($recipient->email, 70));
        $xml->addChild('Cellulare1', Alfa::alfa(substr($recipient->phone, -10), 10));
        $xml->addChild('GeneraPdf', (string) $shipment->labelFormat->value);
        $xml->addChild('bda', Alfa::alfa($shipment->bda, 11));
        $xml->addChild('FormatoPdf', '');
        $xml->addChild('ContatoreProgressivo', (string) ($shipment->progressiveCounter ?? mt_rand(100000000, 999999999)));
        $xml->addChild('ServiziAccessori', implode(',', $shipment->services));
        if ($shipment->deliveryTimeNote !== null) {
            $xml->addChild('OrarioNoteGDO', $shipment->deliveryTimeNote);
        }

        if ($hasShop) {
            $xml->addChild('PARTNER_SHOP_ID', $shipment->partnerShopId);
            $xml->addChild('SHOP_ID', $shipment->shopId);
        }

        return $xml;
    }
}
