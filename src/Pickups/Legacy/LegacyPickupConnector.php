<?php

namespace SmartDato\GlsItaly\Pickups\Legacy;

use Saloon\Http\Connector;
use SmartDato\GlsItaly\Concerns\SendsGlsRequests;

/**
 * The legacy PHPApps pickup channel (MU302): plain XML POSTed to
 * addpickup.php / deletepickup.php.
 */
class LegacyPickupConnector extends Connector
{
    use SendsGlsRequests;

    public function resolveBaseUrl(): string
    {
        return config('gls-italy.endpoints.legacy', 'https://www.gls-italy.com/PHPApps');
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
