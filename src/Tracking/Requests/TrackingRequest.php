<?php

namespace SmartDato\GlsItaly\Tracking\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

abstract class TrackingRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '';
    }
}
