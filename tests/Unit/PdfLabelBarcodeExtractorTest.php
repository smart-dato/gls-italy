<?php

use SmartDato\GlsItaly\Support\PdfLabelBarcodeExtractor;
use SmartDato\GlsItaly\Tests\Fixtures\GlsFixtures;

test('extracts the printed barcode from real gls labels', function (string $label, string $shipmentNumber, string $expected): void {
    $barcode = new PdfLabelBarcodeExtractor()->extract(GlsFixtures::label($label), $shipmentNumber);

    expect($barcode)->toBe($expected);
})->skip(
    fn (): bool => ! file_exists(__DIR__.'/../Fixtures/labels/example01.pdf'),
    'The real label fixtures carry personal data and are gitignored — copy them in locally to run this test.',
)->with([
    'easy 01' => ['example01', '620873098', 'BZ620873098010M5'],
    'easy 02' => ['example02', '620872767', 'BZ620872767010BA'],
    'hard 03' => ['example03', '620873016', 'BZ620873016010FW'],
    'hard 04' => ['example04', '620871924', 'BZ620871924010M101'],
]);

test('returns an empty string when the shipment number is empty or absent', function (): void {
    $extractor = new PdfLabelBarcodeExtractor;

    expect($extractor->extract(GlsFixtures::label('example01'), ''))->toBe('')
        ->and($extractor->extract(GlsFixtures::label('example01'), '999999999'))->toBe('');
})->skip(
    fn (): bool => ! file_exists(__DIR__.'/../Fixtures/labels/example01.pdf'),
    'The real label fixtures carry personal data and are gitignored — copy them in locally to run this test.',
);
