<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\PickupCancellationData;
use SmartDato\GlsItaly\Data\PickupData;
use SmartDato\GlsItaly\Data\RecipientData;
use SmartDato\GlsItaly\Exceptions\ApiErrorException;
use SmartDato\GlsItaly\Exceptions\ValidationException;
use SmartDato\GlsItaly\GlsItaly;
use SmartDato\GlsItaly\Pickups\Legacy\Requests\AddPickupRequest;
use SmartDato\GlsItaly\Pickups\Legacy\Requests\DeletePickupRequest;
use SmartDato\GlsItaly\Pickups\Results\AddPickupResult;
use SmartDato\GlsItaly\Tests\Fixtures\GlsFixtures;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function pickupCredentials(): Credentials
{
    return new Credentials(sede: 'BB', clientCode: '654321', password: 'pickup-secret', contractCode: '9876');
}

function pickupData(array $overrides = []): PickupData
{
    return new PickupData(...array_merge([
        'contractCode' => '9876',
        'requesterName' => 'GLSXMLTEST',
        'bda' => 'P2600007777',
        'pickupAddress' => new RecipientData(
            name: 'ACME & Co',
            street: 'Via Venezia 22',
            city: 'Bolzano',
            zipcode: '39100',
        ),
        'pickupDate' => new DateTimeImmutable('2026-09-01'),
        'parcelCount' => 2,
        'weight' => 10.5,
        'deliveryAddress' => new RecipientData(
            name: 'Mario Rossi',
            street: 'Via Roma 1',
            city: 'Bolzano',
            zipcode: '39100',
            email: 'Mario.Rossi@Example.com',
            phone: '+390471123456',
        ),
        'note' => 'Ritiro "fragile" & urgente',
        'notifyEmail' => 'hallo@example.com',
        'phone' => '+390471998877',
    ], $overrides));
}

test('add pickup request with a delivery address matches the recorded wire format', function (): void {
    $request = new AddPickupRequest(pickupCredentials(), [pickupData()]);

    expect($request->bodyXml())->toBe(GlsFixtures::request('pickup-single-shipment'))
        ->and($request->body()->all())->toBe($request->bodyXml());
});

test('add pickup request without a delivery address blanks the destination block', function (): void {
    $request = new AddPickupRequest(pickupCredentials(), [
        pickupData(['parcelCount' => 3, 'weight' => 12.5, 'deliveryAddress' => null]),
    ]);

    expect($request->bodyXml())->toBe(GlsFixtures::request('pickup-multiple-shipments'));
});

test('the fluent pickup builder creates a pickup and returns the pickup number', function (): void {
    MockClient::global([
        MockResponse::make('<?xml version="1.0" encoding="utf-8"?><RisultatiRitiri><RitiroCreato NumeroRitiro="BB9660004359"><Bda>P2600007777</Bda></RitiroCreato></RisultatiRitiri>'),
    ]);

    $result = new GlsItaly()->withCredentials(pickupCredentials())->pickup()
        ->requesterName('GLSXMLTEST')
        ->bda('P2600007777')
        ->pickupAddress(pickupData()->pickupAddress)
        ->pickupDate(new DateTimeImmutable('2026-09-01'))
        ->parcelCount(2)
        ->weight(10.5)
        ->notifyEmail('hallo@example.com')
        ->create();

    expect($result->succeeded())->toBeTrue()
        ->and($result->pickupNumber())->toBe('BB9660004359')
        ->and($result->ensureCreated())->toBe('BB9660004359')
        ->and($result->error())->toBeNull()
        ->and($result->rawRequest())->toContain('<CodiceProfiloGls>654321</CodiceProfiloGls>');
});

test('pickup errors keep the legacy precedence and messages', function (): void {
    $ritiroErrato = AddPickupResult::fromStrings('', '<?xml version="1.0" encoding="utf-8"?><RisultatiRitiri><RitiroErrato Errore="Colli non validi"/></RisultatiRitiri>');
    $rootError = AddPickupResult::fromStrings('', '<?xml version="1.0" encoding="utf-8"?><RisultatiRitiri><Errore>Profilo inesistente o password errata</Errore></RisultatiRitiri>');
    $empty = AddPickupResult::fromStrings('', '');
    $garbage = AddPickupResult::fromStrings('', 'not xml at all');

    expect($ritiroErrato->error())->toBe('Colli non validi')
        ->and($rootError->error())->toBe('Profilo inesistente o password errata')
        ->and($empty->error())->toBe('no response')
        ->and($garbage->error())->toBe('no valid Response from GLS Italy')
        ->and(fn (): string => $ritiroErrato->ensureCreated())->toThrow(ApiErrorException::class, 'Colli non validi');
});

test('delete pickup request follows the mu302 cancellation format', function (): void {
    $request = new DeletePickupRequest(pickupCredentials(), [
        new PickupCancellationData(
            contractCode: '9876',
            pickupNumber: 'BB9660004359',
            requesterName: 'Mario Rossi',
            email: 'ops@example.com',
        ),
    ]);

    expect($request->bodyXml())->toBe(
        '<?xml version="1.0" encoding="UTF-8"?><Info><SedeGls>BB</SedeGls><CodiceProfiloGls>654321</CodiceProfiloGls><PasswordClienteGls>pickup-secret</PasswordClienteGls>'
        .'<Ritiro><CodiceContrattoGls>9876</CodiceContrattoGls><NumeroRitiro>BB9660004359</NumeroRitiro><NomeCognome>Mario Rossi</NomeCognome><Email>ops@example.com</Email></Ritiro></Info>',
    )->and($request->resolveEndpoint())->toBe('/deletepickup.php');
});

test('cancel pickup parses cancelled numbers and per pickup errors', function (): void {
    MockClient::global([
        MockResponse::make('<?xml version="1.0" encoding="utf-8"?><RisultatiAnnullamentoRitiri>'
            .'<RitiroAnnullato NumeroRitiro=" E19570000077"><NumeroRitiro>E19570000077</NumeroRitiro></RitiroAnnullato>'
            .'<ErroreAnnullamento NumeroRitiro="E19570000063" Errore="Ritiro gia annullato."/>'
            .'</RisultatiAnnullamentoRitiri>'),
    ]);

    $result = new GlsItaly()->withCredentials(pickupCredentials())->cancelPickup(new PickupCancellationData(
        contractCode: '9876',
        pickupNumber: 'E19570000077',
        requesterName: 'Mario Rossi',
        email: 'ops@example.com',
    ));

    expect($result->cancelledNumbers())->toBe(['E19570000077'])
        ->and($result->cancelled('E19570000077'))->toBeTrue()
        ->and($result->errors())->toBe(['E19570000063' => 'Ritiro gia annullato.']);
});

test('the pickup builder validates its required calls', function (): void {
    $builder = fn () => new GlsItaly()->withCredentials(pickupCredentials())->pickup();

    expect(fn () => new GlsItaly()->pickup()->create())
        ->toThrow(ValidationException::class, 'Credentials are required, call GlsItaly::withCredentials() or credentials() before create().')
        ->and(fn () => $builder()->pickupAddress(pickupData()->pickupAddress)->pickupDate(new DateTimeImmutable('2026-09-01'))->create())
        ->toThrow(ValidationException::class, 'A requester name is required, call requesterName() before create().')
        ->and(fn () => $builder()->requesterName('GLSXMLTEST')->pickupDate(new DateTimeImmutable('2026-09-01'))->create())
        ->toThrow(ValidationException::class, 'A pickup address is required, call pickupAddress() before create().')
        ->and(fn () => $builder()->requesterName('GLSXMLTEST')->pickupAddress(pickupData()->pickupAddress)->create())
        ->toThrow(ValidationException::class, 'A pickup date is required, call pickupDate() before create().');
});
