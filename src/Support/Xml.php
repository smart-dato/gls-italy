<?php

namespace SmartDato\GlsItaly\Support;

use SimpleXMLElement;

/**
 * Ported verbatim from the OLC XmlUtil helpers the GLS_IT connector used —
 * removeVersion() also strips line breaks, which is part of the wire format.
 */
class Xml
{
    public static function removeVersion(SimpleXMLElement $xml): string
    {
        $withoutDeclaration = str_replace("<?xml version=\"1.0\"?>\n", '', (string) $xml->saveXML());

        return (string) preg_replace('/\r\n|\n\r|\n|\r/', '', $withoutDeclaration);
    }

    public static function appendSimpleXml(SimpleXMLElement $simpleXmlTo, SimpleXMLElement $simpleXmlFrom): ?SimpleXMLElement
    {
        $simpleXmlTemp = null;
        foreach ($simpleXmlFrom->children() as $simpleXmlChild) {
            $simpleXmlTemp = $simpleXmlTo->addChild($simpleXmlChild->getName(), (string) $simpleXmlChild);
            foreach ($simpleXmlChild->attributes() as $attrKey => $attrValue) {
                $simpleXmlTemp->addAttribute($attrKey, $attrValue);
            }
            self::appendSimpleXml($simpleXmlTemp, $simpleXmlChild);
        }

        return $simpleXmlTemp;
    }
}
