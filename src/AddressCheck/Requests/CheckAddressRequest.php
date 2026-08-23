<?php

namespace SmartDato\GlsItaly\AddressCheck\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;
use SmartDato\GlsItaly\Data\AddressQueryData;
use SmartDato\GlsItaly\Data\Credentials;

/**
 * Replicates the envelope PHP's native SoapClient generated from the live
 * WSDL, byte for byte — including the declaration line break and the ns1
 * prefix.
 */
class CheckAddressRequest extends Request implements HasBody
{
    use HasStringBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly Credentials $credentials,
        protected readonly AddressQueryData $address,
    ) {}

    public function resolveEndpoint(): string
    {
        return '';
    }

    protected function defaultBody(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="https://checkaddress.gls-italy.com/"><SOAP-ENV:Body><ns1:CheckAddress>'
            .'<ns1:SedeGls>'.$this->encode($this->credentials->sede).'</ns1:SedeGls>'
            .'<ns1:CodiceClienteGls>'.$this->encode($this->credentials->clientCode).'</ns1:CodiceClienteGls>'
            .'<ns1:PasswordClienteGls>'.$this->encode($this->credentials->password).'</ns1:PasswordClienteGls>'
            .'<ns1:SiglaProvincia>'.$this->encode($this->address->province).'</ns1:SiglaProvincia>'
            .'<ns1:Cap>'.$this->encode($this->address->zipcode).'</ns1:Cap>'
            .'<ns1:Localita>'.$this->encode($this->address->city).'</ns1:Localita>'
            .'<ns1:Indirizzo>'.$this->encode($this->address->street).'</ns1:Indirizzo>'
            .'</ns1:CheckAddress></SOAP-ENV:Body></SOAP-ENV:Envelope>'."\n";
    }

    protected function encode(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1, 'UTF-8');
    }
}
