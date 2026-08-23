<?php

namespace SmartDato\GlsItaly\AddressCheck;

use Saloon\Http\Connector;
use SmartDato\GlsItaly\Concerns\SendsGlsRequests;

/**
 * The wscheckaddress.asmx SOAP 1.1 service. The headers replicate what PHP's
 * native SoapClient sent for years.
 */
class CheckAddressConnector extends Connector
{
    use SendsGlsRequests;

    public function resolveBaseUrl(): string
    {
        return config('gls-italy.endpoints.check_address', 'https://checkaddress.gls-italy.com/wscheckaddress.asmx');
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => '"https://checkaddress.gls-italy.com/CheckAddress"',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        return [
            'timeout' => config('gls-italy.http.timeout', 30),
            'verify' => config('gls-italy.http.verify', true),
        ];
    }
}
