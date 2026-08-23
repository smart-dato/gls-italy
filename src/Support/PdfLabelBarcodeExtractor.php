<?php

namespace SmartDato\GlsItaly\Support;

use Smalot\PdfParser\Parser;

/**
 * GLS does not return the printed barcode as a field — the OLC connector has
 * always recovered it from the label PDF text. The substr(strpos - 3, 24)
 * heuristic is ported verbatim: it grabs the depot sigla in front of the
 * shipment number plus the trailing parcel data.
 */
class PdfLabelBarcodeExtractor
{
    public function extract(string $labelPdf, string $shipmentNumber): string
    {
        if ($shipmentNumber === '') {
            return '';
        }

        $parser = new Parser;
        $pdf = $parser->parseContent($labelPdf);
        $lines = explode(PHP_EOL, $pdf->getText());

        foreach ($lines as $line) {
            if (str_contains($line, $shipmentNumber)) {
                return str_replace(' ', '', mb_substr($line, strpos($line, $shipmentNumber) - 3, 24, 'UTF-8'));
            }
        }

        return '';
    }
}
