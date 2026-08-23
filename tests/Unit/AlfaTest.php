<?php

use SmartDato\GlsItaly\Support\Alfa;

test('alfa transliterates, collapses whitespace, strips symbols and truncates', function (): void {
    expect(Alfa::alfa('Müller & Söhne GmbH', 35))->toBe('Mueller  Soehne GmbH')
        ->and(Alfa::alfa('Mario Rossi tel +390471123456 handle with care', 40))->toBe('Mario Rossi tel 390471123456 handle with')
        ->and(Alfa::alfa(null, 10))->toBe('')
        ->and(Alfa::alfa("  spaced\t\nout  ", 40))->toBe('spaced out');
});

test('alfaStreetCity keeps apostrophes and replaces other symbols with spaces', function (): void {
    expect(Alfa::alfaStreetCity("Straße d'Ölberg 12/A", 35))->toBe("Strasse d'Oelberg 12 A")
        ->and(Alfa::alfaStreetCity('Via Roma 1', 35))->toBe('Via Roma 1');
});

test('alfaEmail lowercases and replaces invalid characters with spaces', function (): void {
    expect(Alfa::alfaEmail('Mario.Rossi@Example.com', 70))->toBe('mario.rossi@example.com')
        ->and(Alfa::alfaEmail('weird+tag@example.com', 70))->toBe('weird tag@example.com');
});

test('num formats with a comma and two decimals without thousands separator', function (): void {
    expect(Alfa::num(1.5))->toBe('1,50')
        ->and(Alfa::num(0))->toBe('0,00')
        ->and(Alfa::num(null))->toBe('0,00')
        ->and(Alfa::num(1234.567))->toBe('1234,57');
});
