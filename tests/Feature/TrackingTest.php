<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\GlsItaly\GlsItaly;
use SmartDato\GlsItaly\Tracking\Results\PickupTrackingResult;
use SmartDato\GlsItaly\Tracking\Results\ShipmentTrackingResult;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function trackingResponse(string $firstNote = ''): string
{
    return '<ELENCO><SPEDIZIONE><NumSped><![CDATA[620873098]]></NumSped>'
        .'<SedePartenza><![CDATA[BZ]]></SedePartenza><SedeDestinazione><![CDATA[MILANO]]></SedeDestinazione><TRACKING>'
        .'<Data><![CDATA[01/09/26]]></Data><Ora><![CDATA[10:30]]></Ora><Luogo><![CDATA[BOLZANO]]></Luogo>'
        ."<Stato><![CDATA[PARTITA]]></Stato><Note><![CDATA[{$firstNote}]]></Note><Codice><![CDATA[901]]></Codice>"
        .'<Data><![CDATA[02/09/26]]></Data><Ora><![CDATA[]]></Ora><Luogo><![CDATA[MILANO]]></Luogo>'
        .'<Stato><![CDATA[IN CONSEGNA]]></Stato><Note><![CDATA[]]></Note><Codice><![CDATA[905]]></Codice>'
        .'<Data><![CDATA[03/09/26]]></Data><Ora><![CDATA[14.30]]></Ora><Luogo><![CDATA[MILANO]]></Luogo>'
        .'<Stato><![CDATA[CONSEGNATA]]></Stato><Note><![CDATA[]]></Note><Codice><![CDATA[906]]></Codice>'
        .'</TRACKING></SPEDIZIONE></ELENCO>';
}

test('tracking requests build the legacy query string urls', function (): void {
    MockClient::global([
        MockResponse::make(trackingResponse()),
        MockResponse::make(trackingResponse()),
        MockResponse::make('<ELENCO></ELENCO>'),
        MockResponse::make(trackingResponse()),
    ]);
    $gls = new GlsItaly;
    $urls = [];

    $gls->trackByShipmentNumber('BZ', '620873098', '123456');
    $urls[] = (string) MockClient::getGlobal()->getLastPendingRequest()->getUri();
    $gls->trackByBda('V1', '0442266', '2557');
    $urls[] = (string) MockClient::getGlobal()->getLastPendingRequest()->getUri();
    $gls->trackPickup('BB', '9660004359', '2557');
    $urls[] = (string) MockClient::getGlobal()->getLastPendingRequest()->getUri();
    $gls->trackRetour('VE', '123456789', 'BZ', '2557');
    $urls[] = (string) MockClient::getGlobal()->getLastPendingRequest()->getUri();

    expect($urls)->toBe([
        'https://infoweb.gls-italy.com/XML/get_xml_track.php?locpartenza=BZ&NumSped=620873098&Cli=123456',
        'https://infoweb.gls-italy.com/XML/get_xml_track.php?locpartenza=V1&bda=0442266&CodCli=2557',
        'https://infoweb.gls-italy.com/XML/get_xml_track.php?locpartenza=BB&numrit=9660004359&CodCli=2557',
        'https://infoweb.gls-italy.com/XML/get_xml_track.php?locpartenza=VE&NumSped=123456789&sedecon=BZ&CodCli=2557',
    ]);
});

test('shipment tracking walks the flat tracking children in groups of six', function (): void {
    $result = ShipmentTrackingResult::fromStrings(trackingResponse());

    $events = $result->events();

    expect($events)->toHaveCount(3)
        ->and($events[0]->datetime->format('Y-m-d H:i'))->toBe('2026-09-01 10:30')
        ->and($events[0]->subsidiary)->toBe('BOLZANO')
        ->and($events[0]->code)->toBe('901')
        ->and($events[0]->state)->toBe('PARTITA')
        ->and($events[1]->datetime->format('Y-m-d H:i'))->toBe('2026-09-02 20:01')
        ->and($events[2]->datetime->format('Y-m-d H:i'))->toBe('2026-09-03 14:30')
        ->and($events[2]->code)->toBe('906')
        ->and($result->shipmentNumber())->toBe('620873098')
        ->and($result->departureSede())->toBe('BZ')
        ->and($result->destinationSede())->toBe('MILANO')
        ->and($result->retourReference())->toBeNull();
});

test('an unparseable event datetime yields null with a warning', function (): void {
    $response = '<ELENCO><SPEDIZIONE><TRACKING>'
        .'<Data><![CDATA[garbage]]></Data><Ora><![CDATA[nope]]></Ora><Luogo><![CDATA[BOLZANO]]></Luogo>'
        .'<Stato><![CDATA[X]]></Stato><Note><![CDATA[]]></Note><Codice><![CDATA[901]]></Codice>'
        .'</TRACKING></SPEDIZIONE></ELENCO>';

    $events = ShipmentTrackingResult::fromStrings($response)->events();

    expect($events)->toHaveCount(1)
        ->and($events[0]->datetime)->toBeNull()
        ->and($events[0]->warning)->toBeTrue();
});

test('a retour announcement in the first note is split into sede and number', function (): void {
    $result = ShipmentTrackingResult::fromStrings(trackingResponse('Resa con spedizione: VE123456789'));

    expect($result->retourReference())->toBe(['sede' => 'VE', 'number' => '123456789']);
});

test('error responses expose the testoerrore text', function (): void {
    $result = ShipmentTrackingResult::fromStrings('<ELENCO><TESTOERRORE> Nessuna spedizione trovata </TESTOERRORE></ELENCO>');

    expect($result->errorText())->toBe(' Nessuna spedizione trovata ')
        ->and($result->events())->toBeEmpty()
        ->and(ShipmentTrackingResult::fromStrings('not xml')->events())->toBeEmpty();
});

test('pickup tracking parses event lists single events and the generated shipment', function (): void {
    $response = '<ELENCO><RITIRO><NumRit><![CDATA[9660004359]]></NumRit><TRACKINGRITIRO>'
        .'<Data><![CDATA[01/09/26]]></Data><Ora><![CDATA[08:15]]></Ora><Luogo><![CDATA[BOLZANO]]></Luogo>'
        .'<Stato><![CDATA[REGISTRATO]]></Stato><Note><![CDATA[]]></Note><Codice><![CDATA[29]]></Codice>'
        .'<Data><![CDATA[02/09/26]]></Data><Ora><![CDATA[09:00]]></Ora><Luogo><![CDATA[TRENTO]]></Luogo>'
        .'<Stato><![CDATA[EFFETTUATO]]></Stato><Note><![CDATA[]]></Note><Codice><![CDATA[30]]></Codice>'
        .'</TRACKINGRITIRO>'
        .'<SPEDIZIONE><NumSped><![CDATA[170000016]]></NumSped><SedePartenza><![CDATA[BB]]></SedePartenza>'
        .'<SedeDestinazione><![CDATA[MILANO]]></SedeDestinazione><TRACKING>'
        .'<Data><![CDATA[02/09/26]]></Data><Ora><![CDATA[11:00]]></Ora><Luogo><![CDATA[BOLZANO]]></Luogo>'
        .'<Stato><![CDATA[PARTITA]]></Stato><Note><![CDATA[]]></Note><Codice><![CDATA[901]]></Codice>'
        .'</TRACKING></SPEDIZIONE></RITIRO></ELENCO>';

    $result = PickupTrackingResult::fromStrings($response);

    expect($result->hasPickup())->toBeTrue()
        ->and($result->pickupEvents())->toHaveCount(2)
        ->and($result->pickupEvents()[0]->datetime->format('Y-m-d H:i'))->toBe('2026-09-01 08:15')
        ->and($result->pickupEvents()[1]->subsidiary)->toBe('TRENTO')
        ->and($result->shipmentEvents())->toHaveCount(1)
        ->and($result->shipmentEvents()[0]->code)->toBe('901')
        ->and($result->shipmentNumber())->toBe('170000016')
        ->and($result->shipmentDepartureSede())->toBe('BB')
        ->and($result->shipmentDestinationSede())->toBe('MILANO');
});

test('a single pickup event with a garbage date carries a warning', function (): void {
    $response = '<ELENCO><RITIRO><TRACKINGRITIRO>'
        .'<Data><![CDATA[garbage]]></Data><Ora><![CDATA[nope]]></Ora><Luogo><![CDATA[BOLZANO]]></Luogo>'
        .'<Stato><![CDATA[REGISTRATO]]></Stato><Note><![CDATA[]]></Note><Codice><![CDATA[29]]></Codice>'
        .'</TRACKINGRITIRO></RITIRO></ELENCO>';

    $events = PickupTrackingResult::fromStrings($response)->pickupEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0]->datetime)->toBeNull()
        ->and($events[0]->warning)->toBeTrue()
        ->and($events[0]->code)->toBe('29');
});

test('a missing pickup exposes the error text', function (): void {
    $result = PickupTrackingResult::fromStrings('<ELENCO><TESTOERRORE>Ritiro non trovato</TESTOERRORE></ELENCO>');

    expect($result->hasPickup())->toBeFalse()
        ->and($result->errorText())->toBe('Ritiro non trovato')
        ->and($result->pickupEvents())->toBeEmpty()
        ->and($result->shipmentEvents())->toBeEmpty();
});
