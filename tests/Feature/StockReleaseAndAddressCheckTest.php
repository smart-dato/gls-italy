<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\GlsItaly\AddressCheck\Results\AddressCheckResult;
use SmartDato\GlsItaly\Data\AddressQueryData;
use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\RecipientData;
use SmartDato\GlsItaly\Data\RedeliveryData;
use SmartDato\GlsItaly\Enums\StockReleaseType;
use SmartDato\GlsItaly\GlsItaly;
use SmartDato\GlsItaly\StockRelease\Requests\RedeliveryParcelRequest;
use SmartDato\GlsItaly\StockRelease\Results\StockReleaseResult;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function profileCredentials(): Credentials
{
    return new Credentials(sede: 'BB', clientCode: 'ProfileX', password: 'profile-secret', contractCode: '2557');
}

test('redelivery request follows the mu276 format', function (): void {
    $request = new RedeliveryParcelRequest(profileCredentials(), [new RedeliveryData(
        shipmentNumber: 'BZ620873098',
        releaseType: StockReleaseType::ReturnToSender->value,
        clientPhone: '0471662940',
        notifyRecipientByPhone: false,
        chargesToSender: true,
        redeliveryDate: new DateTimeImmutable('2026-09-01'),
        note: 'Return it',
    )]);

    expect($request->bodyXml())->toBe(
        '<?xml version="1.0" encoding="UTF-8"?><Info><SedeProfiloGls>BB</SedeProfiloGls><CodiceProfiloGls>ProfileX</CodiceProfiloGls><PasswordClienteGls>profile-secret</PasswordClienteGls>'
        .'<Redelivery><NumeroSpedizione>BZ620873098</NumeroSpedizione><TipoSvincolo>3</TipoSvincolo><TelefonoCliente>0471662940</TelefonoCliente>'
        .'<ConPreavvisoTelefonico>N</ConPreavvisoTelefonico><TelefonoDestinatario/><AnnullandoCAssegno>N</AnnullandoCAssegno><VariandoCAssegno>N</VariandoCAssegno>'
        .'<NuovoImportoCAssegno/><VariandoInPortoFranco>N</VariandoInPortoFranco><VariandoInPortoAssegnato>N</VariandoInPortoAssegnato>'
        .'<SpeseAdMittente>S</SpeseAdMittente><SpeseAdDestinatario>N</SpeseAdDestinatario><DataRiconsegna>01/09/26</DataRiconsegna><Note>Return it</Note>'
        .'<CognomeNome/><NuovoIndirizzo/><NuovaLocalita/><NuovoZipcode/><NuovaProvincia/></Redelivery></Info>',
    );
});

test('a redelivery to another address carries the new recipient', function (): void {
    $request = new RedeliveryParcelRequest(profileCredentials(), [new RedeliveryData(
        shipmentNumber: 'BZ620873098',
        releaseType: StockReleaseType::DeliverToOtherAddress->value,
        notifyRecipientByPhone: true,
        recipientPhone: '3331234567',
        chargesToSender: true,
        redeliveryDate: new DateTimeImmutable('2026-09-01'),
        newRecipient: new RecipientData(
            name: 'Luigi Verdi',
            street: 'Via Milano 2',
            city: 'Bolzano',
            zipcode: '39100',
            province: 'BZ',
        ),
    )]);

    expect($request->bodyXml())->toContain('<TipoSvincolo>2</TipoSvincolo>')
        ->and($request->bodyXml())->toContain('<ConPreavvisoTelefonico>S</ConPreavvisoTelefonico><TelefonoDestinatario>3331234567</TelefonoDestinatario>')
        ->and($request->bodyXml())->toContain('<CognomeNome>Luigi Verdi</CognomeNome><NuovoIndirizzo>Via Milano 2</NuovoIndirizzo><NuovaLocalita>Bolzano</NuovaLocalita><NuovoZipcode>39100</NuovoZipcode><NuovaProvincia>BZ</NuovaProvincia>');
});

test('stock release outcomes follow the mu276 response example', function (): void {
    $response = '<?xml version="1.0" encoding="utf-8"?><Risultati>'
        .'<Spedizione Numero="SM111111111"><Svincolo>Ok</Svincolo></Spedizione>'
        .'<Spedizione Numero="SM111111112"><Svincolo>Spedizione non trovata</Svincolo></Spedizione>'
        .'<Spedizione Numero="SM111111113"><Svincolo>Giacenza Scaduta</Svincolo></Spedizione>'
        .'</Risultati>';

    $result = StockReleaseResult::fromStrings('', $response);

    expect($result->outcomes())->toBe([
        'SM111111111' => 'Ok',
        'SM111111112' => 'Spedizione non trovata',
        'SM111111113' => 'Giacenza Scaduta',
    ])->and($result->released('SM111111111'))->toBeTrue()
        ->and($result->released('SM111111112'))->toBeFalse()
        ->and($result->outcome('SM999999999'))->toBeNull();
});

test('releasing stock posts to the redelivery endpoint', function (): void {
    MockClient::global([
        MockResponse::make('<?xml version="1.0" encoding="utf-8"?><Risultati><Spedizione Numero="BZ620873098"><Svincolo>Ok</Svincolo></Spedizione></Risultati>'),
    ]);

    $result = new GlsItaly()->withCredentials(profileCredentials())->releaseStock(new RedeliveryData(
        shipmentNumber: 'BZ620873098',
        releaseType: StockReleaseType::ReturnToSender->value,
    ));

    expect($result->released('BZ620873098'))->toBeTrue()
        ->and((string) MockClient::getGlobal()->getLastPendingRequest()->getUri())
        ->toBe('https://www.gls-italy.com/PHPApps/redelivery_parcel.php');
});

test('check address replicates the native soap client envelope and headers', function (): void {
    MockClient::global([
        MockResponse::make('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><CheckAddressResponse xmlns="https://checkaddress.gls-italy.com/"><CheckAddressResult><Esito>Destinazione corretta.</Esito></CheckAddressResult></CheckAddressResponse></soap:Body></soap:Envelope>'),
    ]);

    $result = new GlsItaly()
        ->withCredentials(new Credentials(sede: 'BZ', clientCode: '123456', password: 'secret'))
        ->checkAddress(new AddressQueryData(province: 'BZ', zipcode: '39100', city: 'Bolzano', street: 'Via Roma 1'));

    $pendingRequest = MockClient::getGlobal()->getLastPendingRequest();

    expect($result->esito())->toBe('Destinazione corretta.')
        ->and((string) $pendingRequest->getUri())->toBe('https://checkaddress.gls-italy.com/wscheckaddress.asmx')
        ->and($pendingRequest->headers()->get('SOAPAction'))->toBe('"https://checkaddress.gls-italy.com/CheckAddress"')
        ->and($pendingRequest->headers()->get('Content-Type'))->toBe('text/xml; charset=utf-8')
        ->and($pendingRequest->body()->all())->toBe(
            '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="https://checkaddress.gls-italy.com/"><SOAP-ENV:Body><ns1:CheckAddress>'
            .'<ns1:SedeGls>BZ</ns1:SedeGls><ns1:CodiceClienteGls>123456</ns1:CodiceClienteGls><ns1:PasswordClienteGls>secret</ns1:PasswordClienteGls>'
            .'<ns1:SiglaProvincia>BZ</ns1:SiglaProvincia><ns1:Cap>39100</ns1:Cap><ns1:Localita>Bolzano</ns1:Localita><ns1:Indirizzo>Via Roma 1</ns1:Indirizzo>'
            .'</ns1:CheckAddress></SOAP-ENV:Body></SOAP-ENV:Envelope>'."\n",
        );
});

test('an escaped check address payload still yields the esito', function (): void {
    $escaped = '<soap:Envelope><soap:Body><CheckAddressResponse><CheckAddressResult>&lt;Esito&gt;Indirizzo non identificato. A seguire gli indirizzi&lt;/Esito&gt;</CheckAddressResult></CheckAddressResponse></soap:Body></soap:Envelope>';

    $result = AddressCheckResult::fromStrings('', $escaped);

    expect($result->esito())->toBe('Indirizzo non identificato. A seguire gli indirizzi')
        ->and(AddressCheckResult::fromStrings('', 'garbage')->esito())->toBeNull();
});
