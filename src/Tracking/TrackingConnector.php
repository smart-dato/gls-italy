<?php

namespace SmartDato\GlsItaly\Tracking;

use Saloon\Http\Connector;
use SmartDato\GlsItaly\Concerns\SendsGlsRequests;

/**
 * The read-only Track & Trace XML feed (MU40): plain GET requests with query
 * string parameters, no authentication beyond depot and client codes.
 */
class TrackingConnector extends Connector
{
    use SendsGlsRequests;

    public function resolveBaseUrl(): string
    {
        return config('gls-italy.endpoints.tracking', 'https://infoweb.gls-italy.com/XML/get_xml_track.php');
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
