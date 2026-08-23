<?php

namespace SmartDato\GlsItaly\LabelService\Requests;

use SimpleXMLElement;
use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\ShipmentData;
use SmartDato\GlsItaly\LabelService\Xml\ParcelXmlSerializer;
use SmartDato\GlsItaly\Support\SimpleXmlExtended;
use SmartDato\GlsItaly\Support\Xml;

class CloseWorkDayRequest extends SoapMethodRequest
{
    /**
     * @param  array<int, ShipmentData>  $shipments
     */
    public function __construct(
        protected readonly Credentials $credentials,
        protected readonly array $shipments,
    ) {}

    public function methodXml(): string
    {
        $info = new SimpleXMLElement('<Info/>');
        $info->addChild('SedeGls', $this->credentials->sede);
        $info->addChild('CodiceClienteGls', $this->credentials->clientCode);
        $info->addChild('PasswordClienteGls', $this->credentials->password);

        $serializer = new ParcelXmlSerializer;
        foreach ($this->shipments as $shipment) {
            $parcelElement = $info->addChild('Parcel');
            Xml::appendSimpleXml($parcelElement, $serializer->serialize($shipment));
        }

        $xml = new SimpleXmlExtended('<CloseWorkDay/>');
        $xml->addAttribute('xmlns', self::XMLNS);
        $xml->addChildWithCDATA('XMLCloseInfoParcel', Xml::removeVersion($info));

        return Xml::removeVersion($xml);
    }
}
