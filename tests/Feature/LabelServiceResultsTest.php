<?php

use Illuminate\Support\Facades\Storage;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\GlsItaly\Exceptions\StorageException;
use SmartDato\GlsItaly\GlsItaly;
use SmartDato\GlsItaly\Tests\Fixtures\GlsFixtures;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function glsItalyClient(): GlsItaly
{
    return new GlsItaly()->withCredentials(GlsFixtures::credentials());
}

function addParcelResponse(string $destinationDescription = 'MILANO', ?string $pdf = '%PDF-fake'): string
{
    $pdfTag = $pdf === null ? '' : '<PdfLabel>'.base64_encode($pdf).'</PdfLabel>';

    return '<InfoLabel><Parcel><SiglaMittente>BZ</SiglaMittente><NumeroSpedizione>620873098</NumeroSpedizione>'
        .'<TotaleColli>01</TotaleColli><TipoCollo>0</TipoCollo><SiglaSedeDestino>M5</SiglaSedeDestino>'
        ."<DescrizioneSedeDestino>{$destinationDescription}</DescrizioneSedeDestino>{$pdfTag}</Parcel></InfoLabel>";
}

test('creating a shipment returns the parsed label result', function (): void {
    MockClient::global([MockResponse::make(addParcelResponse())]);

    $result = glsItalyClient()->shipment()
        ->contractCode('2557')
        ->recipient(GlsFixtures::recipient())
        ->parcel(GlsFixtures::shipmentData()->parcel)
        ->create();

    expect($result->shipmentNumber())->toBe('620873098')
        ->and($result->hasShipmentNumber())->toBeTrue()
        ->and($result->barcode())->toBe('BZ620873098010M5')
        ->and($result->pdf())->toBe('%PDF-fake')
        ->and($result->zpl())->toBeNull()
        ->and($result->rawRequest())->toContain('<AddParcel xmlns=')
        ->and($result->rawResponse())->toContain('620873098');
});

test('gls check routings drop the destination suffix from the barcode', function (): void {
    MockClient::global([MockResponse::make(addParcelResponse(destinationDescription: 'GLS Check'))]);

    $result = glsItalyClient()->shipment()
        ->contractCode('2557')
        ->recipient(GlsFixtures::recipient())
        ->parcel(GlsFixtures::shipmentData()->parcel)
        ->create();

    expect($result->barcode())->toBe('BZ62087309801');
});

test('a response without a shipment number yields an empty string for the fallback', function (): void {
    MockClient::global([MockResponse::make('<InfoLabel><Parcel></Parcel></InfoLabel>')]);

    $result = glsItalyClient()->shipment()
        ->contractCode('2557')
        ->recipient(GlsFixtures::recipient())
        ->parcel(GlsFixtures::shipmentData()->parcel)
        ->create();

    expect($result->shipmentNumber())->toBe('')
        ->and($result->hasShipmentNumber())->toBeFalse()
        ->and($result->barcode())->toBe('');
});

test('the pdf label can be stored on a disk', function (): void {
    Storage::fake('labels');
    MockClient::global([MockResponse::make(addParcelResponse())]);

    $result = glsItalyClient()->shipment()
        ->contractCode('2557')
        ->recipient(GlsFixtures::recipient())
        ->parcel(GlsFixtures::shipmentData()->parcel)
        ->create();

    $result->storePdf('labels/620873098.pdf', 'labels');

    expect(Storage::disk('labels')->get('labels/620873098.pdf'))->toBe('%PDF-fake');
});

test('failed disk writes surface as storage exceptions', function (): void {
    Storage::shouldReceive('disk')->andReturnSelf();
    Storage::shouldReceive('put')->andReturnFalse();
    MockClient::global([MockResponse::make(addParcelResponse())]);

    $result = glsItalyClient()->shipment()
        ->contractCode('2557')
        ->recipient(GlsFixtures::recipient())
        ->parcel(GlsFixtures::shipmentData()->parcel)
        ->create();

    expect(fn () => $result->storePdf('labels/620873098.pdf'))
        ->toThrow(StorageException::class);
});

test('deleting a shipment checks the literal italian confirmation', function (): void {
    MockClient::global([
        MockResponse::make('<DeleteSpedResult>Eliminazione della spedizione 620873098 avvenuta.</DeleteSpedResult>'),
        MockResponse::make('<DeleteSpedResult>Spedizione non trovata</DeleteSpedResult>'),
    ]);

    $succeeded = glsItalyClient()->deleteShipment('620873098');
    $failed = glsItalyClient()->deleteShipment('999999999');

    expect($succeeded->succeeded())->toBeTrue()
        ->and($failed->succeeded())->toBeFalse()
        ->and($failed->resultText())->toBe('<DeleteSpedResult>Spedizione non trovata</DeleteSpedResult>');
});

test('list pending shipments finds the last matching recipient exactly like the legacy fallback', function (): void {
    $matching = '<Parcel><StatoSpedizione>IN ATTESA DI CHIUSURA.</StatoSpedizione>'
        .'<DenominazioneDestinatario>Mario Rossi</DenominazioneDestinatario>'
        .'<CittaDestinatario>Bolzano</CittaDestinatario><ProvinciaDestinatario>BZ</ProvinciaDestinatario>'
        .'<IndirizzoDestinatario>Via Roma 1</IndirizzoDestinatario><NumSpedizione>620999888</NumSpedizione></Parcel>';
    $otherRecipient = str_replace(['Mario Rossi', '620999888'], ['Luigi Verdi', '620111111'], $matching);
    $alreadyClosed = str_replace(['IN ATTESA DI CHIUSURA.', '620999888'], ['CHIUSO', '620222222'], $matching);

    MockClient::global([
        MockResponse::make("<ListParcel>{$otherRecipient}{$alreadyClosed}{$matching}</ListParcel>"),
    ]);

    $result = glsItalyClient()->listPendingShipments();

    expect($result->shipments())->toHaveCount(3)
        ->and($result->shipments()[0]->recipientName)->toBe('Luigi Verdi')
        ->and($result->findPendingByRecipient('Mario Rossi', 'Bolzano', 'BZ', 'Via Roma 1'))->toBe('620999888')
        ->and($result->findPendingByRecipient('Mario Rossi', 'Milano', 'MI', 'Via Roma 1'))->toBeNull();
});

test('close work day success is the literal ok marker', function (): void {
    MockClient::global([
        MockResponse::make('<soap:Envelope><DescrizioneErrore xmlns="">OK</DescrizioneErrore></soap:Envelope>'),
        MockResponse::make('<soap:Envelope><DescrizioneErrore xmlns="">Password errata</DescrizioneErrore></soap:Envelope>'),
    ]);

    $client = glsItalyClient();

    $succeeded = $client->closeWorkDay()->addShipment(GlsFixtures::shipmentData())->send();
    $failed = $client->closeWorkDay()->addShipment(GlsFixtures::shipmentData())->send();

    expect($succeeded->succeeded())->toBeTrue()
        ->and($failed->succeeded())->toBeFalse();
});
