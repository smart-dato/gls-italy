<?php

namespace SmartDato\GlsItaly\Concerns;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use SmartDato\GlsItaly\Exceptions\RequestException;

trait SendsGlsRequests
{
    public function call(Request $request): Response
    {
        try {
            return $this->send($request);
        } catch (FatalRequestException $exception) {
            throw new RequestException($exception->getMessage(), previous: $exception);
        }
    }
}
