<?php

namespace SmartDato\GlsItaly\LabelService;

use Saloon\Http\Connector;
use SmartDato\GlsItaly\Concerns\SendsGlsRequests;

/**
 * The ilswebservice.asmx SOAP 1.2 endpoint. The base URL is configuration so
 * a consumer can route the identical body through a proxy — the SOAP method
 * is selected by the body's root element, never by the URL.
 */
class LabelServiceConnector extends Connector
{
    use SendsGlsRequests;

    public function resolveBaseUrl(): string
    {
        return config('gls-italy.endpoints.label_service', 'https://labelservice.gls-italy.com/ilswebservice.asmx');
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'text/xml',
            'Content-Type' => config('gls-italy.http.label_service_content_type', 'text/xml'),
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
