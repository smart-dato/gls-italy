<?php

namespace SmartDato\GlsItaly\Support;

/**
 * Tag extraction ported verbatim from OLC StringUtil::everythingInTag().
 *
 * GLS responses reach consumers through proxies and legacy ASMX endpoints, so
 * the extraction deliberately stays regex-based with the exact pattern the
 * connector used for years — replacing it with a real XML parser is a
 * behavior change that first needs recorded production fixtures.
 */
class Tags
{
    /**
     * @return array<int, string> every inner text of $tagname, in order
     */
    public static function allIn(string $string, string $tagname): array
    {
        $pattern = "#<\s*?{$tagname}\b[^>]*>(.*?)</{$tagname}\b[^>]*>#s";
        preg_match_all($pattern, $string, $matches);

        return $matches[1];
    }

    public static function firstIn(string $string, string $tagname): ?string
    {
        return self::allIn($string, $tagname)[0] ?? null;
    }

    /**
     * @return array<int, string> every full match including the tags themselves
     */
    public static function allWithTags(string $string, string $tagname): array
    {
        $pattern = "#<\s*?{$tagname}\b[^>]*>(.*?)</{$tagname}\b[^>]*>#s";
        preg_match_all($pattern, $string, $matches);

        return $matches[0];
    }
}
