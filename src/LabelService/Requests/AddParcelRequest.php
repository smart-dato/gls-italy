<?php

namespace SmartDato\GlsItaly\LabelService\Requests;

use SimpleXMLElement;
use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\ShipmentData;
use SmartDato\GlsItaly\LabelService\Xml\ParcelXmlSerializer;
use SmartDato\GlsItaly\Support\SimpleXmlExtended;
use SmartDato\GlsItaly\Support\Xml;

class AddParcelRequest extends SoapMethodRequest
{
    public function __construct(
        protected readonly Credentials $credentials,
        protected readonly ShipmentData $shipment,
    ) {}

    public function methodXml(): string
    {
        $info = new SimpleXMLElement('<Info/>');
        $info->addChild('SedeGls', $this->credentials->sede);
        $info->addChild('CodiceClienteGls', $this->credentials->clientCode);
        $info->addChild('PasswordClienteGls', $this->credentials->password);

        $parcelElement = $info->addChild('Parcel');
        Xml::appendSimpleXml($parcelElement, new ParcelXmlSerializer()->serialize($this->shipment));

        $xml = new SimpleXmlExtended('<AddParcel/>');
        $xml->addAttribute('xmlns', self::XMLNS);
        $xml->addChildWithCDATA('XMLInfoParcel', Xml::removeVersion($info));

        return Xml::removeVersion($xml);
    }
}
