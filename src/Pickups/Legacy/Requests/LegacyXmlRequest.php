<?php

namespace SmartDato\GlsItaly\Pickups\Legacy\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;
use SmartDato\GlsItaly\Support\Entities;

/**
 * The legacy channel builds its XML by string concatenation — ported verbatim
 * from GLS_IT_PICKUP_API::addTag(): an empty value renders a self-closing
 * tag, and only leaf values are entity-encoded.
 */
abstract class LegacyXmlRequest extends Request implements HasBody
{
    use HasStringBody;

    protected Method $method = Method::POST;

    /**
     * The complete XML document, including the declaration.
     */
    abstract public function bodyXml(): string;

    protected function defaultBody(): string
    {
        return $this->bodyXml();
    }

    protected function tag(string $tag, string|int|null $value = '', bool $cleanup = true): string
    {
        if (strlen((string) $value)) {
            if ($cleanup) {
                $value = Entities::encode((string) $value);
            }

            return '<'.$tag.'>'.$value.'</'.$tag.'>';
        }

        return '<'.$tag.'/>';
    }
}
