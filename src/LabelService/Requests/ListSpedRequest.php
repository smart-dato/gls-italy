<?php

namespace SmartDato\GlsItaly\LabelService\Requests;

use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Support\SimpleXmlExtended;
use SmartDato\GlsItaly\Support\Xml;

class ListSpedRequest extends SoapMethodRequest
{
    public function __construct(
        protected readonly Credentials $credentials,
    ) {}

    public function methodXml(): string
    {
        $xml = new SimpleXmlExtended('<ListSped/>');
        $xml->addAttribute('xmlns', self::XMLNS);

        $xml->addChild('SedeGls', $this->credentials->sede);
        $xml->addChild('CodiceClienteGls', $this->credentials->clientCode);
        $xml->addChild('PasswordClienteGls', $this->credentials->password);

        return Xml::removeVersion($xml);
    }
}
