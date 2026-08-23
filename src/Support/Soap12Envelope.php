<?php

namespace SmartDato\GlsItaly\Support;

/**
 * The label service speaks XML-string-in-SOAP: a hand-built method element is
 * wrapped in this fixed SOAP 1.2 envelope and POSTed as the raw body. The
 * envelope string must stay byte-identical to what the OLC connector sent.
 */
class Soap12Envelope
{
    public static function wrap(string $methodXml): string
    {
        return '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope"><soap12:Body>'
            .$methodXml
            .'</soap12:Body></soap12:Envelope>';
    }
}
