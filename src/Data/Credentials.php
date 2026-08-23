<?php

namespace SmartDato\GlsItaly\Data;

use Spatie\LaravelData\Data;

/**
 * One credential set serves every GLS Italy channel; the request families
 * serialize it to their own tag variants (SedeGls/CodiceClienteGls/
 * PasswordClienteGls, CodiceProfiloGls, CodiceCliente/Password, ...).
 */
final class Credentials extends Data
{
    public function __construct(
        public string $sede,
        public string $clientCode,
        public string $password,
        public ?string $contractCode = null,
    ) {}
}
