<?php

namespace SmartDato\GlsItaly\Support;

/**
 * Entity encoding for the legacy string-concatenated XML channels, ported
 * verbatim from GLS_IT_PICKUP_API::cleanup() (MU302 Appendix rules).
 */
class Entities
{
    public static function encode(string $subject): string
    {
        $search = ['&', '"', '\'', '<', '>'];
        $replace = ['&amp;', '&quot;', '&#39;', '&lt;', '&gt;'];

        return str_replace($search, $replace, $subject);
    }
}
