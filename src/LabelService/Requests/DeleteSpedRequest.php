<?php

namespace SmartDato\GlsItaly\LabelService\Requests;

use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Support\SimpleXmlExtended;
use SmartDato\GlsItaly\Support\Xml;

class DeleteSpedRequest extends SoapMethodRequest
{
    public function __construct(
        protected readonly Credentials $credentials,
        protected readonly string $shipmentNumber,
    ) {}

    public function methodXml(): string
    {
        $xml = new SimpleXmlExtended('<DeleteSped/>');
        $xml->addAttribute('xmlns', self::XMLNS);

        $xml->addChild('SedeGls', $this->credentials->sede);
        $xml->addChild('CodiceClienteGls', $this->credentials->clientCode);
        $xml->addChild('PasswordClienteGls', $this->credentials->password);
        $xml->addChild('NumSpedizione', $this->shipmentNumber);

        return Xml::removeVersion($xml);
    }
}
