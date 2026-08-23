<?php

namespace SmartDato\GlsItaly\Exceptions;

class ResponseParseException extends GlsItalyException
{
    public static function missingTag(string $tag): self
    {
        return new self("The GLS response contains no <{$tag}> element.");
    }
}
