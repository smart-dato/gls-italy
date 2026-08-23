<?php

namespace SmartDato\GlsItaly\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

/**
 * One tracking event from the T&T XML feed (MU40). A null datetime means the
 * feed value could not be parsed — the consumer decides the substitute; the
 * warning flag mirrors that condition for downstream dedup logic.
 */
final class TrackingEventData extends Data
{
    public function __construct(
        public ?Carbon $datetime,
        public string $subsidiary,
        public string $code,
        public string $state = '',
        public string $note = '',
        public bool $warning = false,
    ) {}
}
