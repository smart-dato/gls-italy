<?php

namespace SmartDato\GlsItaly\LabelService\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;
use SmartDato\GlsItaly\Support\Soap12Envelope;

abstract class SoapMethodRequest extends Request implements HasBody
{
    use HasStringBody;

    public const string XMLNS = 'https://labelservice.gls-italy.com/';

    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '';
    }

    /**
     * The method element that selects the SOAP operation, without envelope.
     */
    abstract public function methodXml(): string;

    protected function defaultBody(): string
    {
        return Soap12Envelope::wrap($this->methodXml());
    }
}
