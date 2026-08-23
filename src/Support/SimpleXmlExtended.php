<?php

namespace SmartDato\GlsItaly\Support;

use SimpleXMLElement;

class SimpleXmlExtended extends SimpleXMLElement
{
    public function addCData(string $cdataText): void
    {
        $node = dom_import_simplexml($this);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($cdataText));
    }

    public function addChildWithCDATA(string $name, ?string $value = null): ?SimpleXMLElement
    {
        $newChild = $this->addChild($name);
        if ($newChild !== null) {
            $node = dom_import_simplexml($newChild);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection((string) $value));
        }

        return $newChild;
    }
}
