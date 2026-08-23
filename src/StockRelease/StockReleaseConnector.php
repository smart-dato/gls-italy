<?php

namespace SmartDato\GlsItaly\StockRelease;

use Saloon\Http\Connector;
use SmartDato\GlsItaly\Concerns\SendsGlsRequests;

/**
 * The svincolo giacenze channel (MU276): plain XML POSTed to
 * redelivery_parcel.php, authenticated with the web profile credentials.
 */
class StockReleaseConnector extends Connector
{
    use SendsGlsRequests;

    public function resolveBaseUrl(): string
    {
        return config('gls-italy.endpoints.stock_release', 'https://www.gls-italy.com/PHPApps/redelivery_parcel.php');
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'text/xml',
            'Content-Type' => 'text/xml',
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
