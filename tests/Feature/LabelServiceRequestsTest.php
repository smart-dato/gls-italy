<?php

use SmartDato\GlsItaly\Data\ParcelData;
use SmartDato\GlsItaly\Enums\LabelFormat;
use SmartDato\GlsItaly\LabelService\Requests\AddParcelRequest;
use SmartDato\GlsItaly\LabelService\Requests\CloseWorkDayRequest;
use SmartDato\GlsItaly\LabelService\Requests\DeleteSpedRequest;
use SmartDato\GlsItaly\LabelService\Requests\ListSpedRequest;
use SmartDato\GlsItaly\Support\Soap12Envelope;
use SmartDato\GlsItaly\Tests\Fixtures\GlsFixtures;

test('add parcel request emits the recorded wire format byte for byte', function (): void {
    $request = new AddParcelRequest(GlsFixtures::credentials(), GlsFixtures::shipmentData());

    expect($request->methodXml())->toBe(GlsFixtures::request('add-parcel-default'));
});

test('add parcel request cleans special characters exactly like the legacy connector', function (): void {
    $shipment = GlsFixtures::shipmentData([
        'recipient' => GlsFixtures::recipient([
            'name' => 'Müller & Söhne GmbH',
            'street' => "Straße d'Ölberg 12/A",
        ]),
        'note' => 'tel +390471123456 handle with care',
    ]);

    $request = new AddParcelRequest(GlsFixtures::credentials(), $shipment);

    expect($request->methodXml())->toBe(GlsFixtures::request('add-parcel-special-characters'));
});

test('the soap envelope wraps the method xml unchanged', function (): void {
    $request = new AddParcelRequest(GlsFixtures::credentials(), GlsFixtures::shipmentData());

    expect($request->body()->all())->toBe(
        '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope"><soap12:Body>'
        .GlsFixtures::request('add-parcel-default')
        .'</soap12:Body></soap12:Envelope>',
    )->and($request->body()->all())->toBe(Soap12Envelope::wrap($request->methodXml()));
});

test('close work day request repeats every shipment inside one cdata document', function (): void {
    $request = new CloseWorkDayRequest(GlsFixtures::credentials(), [
        GlsFixtures::shipmentData(),
        GlsFixtures::shipmentData(),
    ]);

    expect($request->methodXml())->toBe(GlsFixtures::request('close-work-day'));
});

test('delete sped request emits the recorded wire format', function (): void {
    $request = new DeleteSpedRequest(GlsFixtures::credentials(), '620873098');

    expect($request->methodXml())->toBe(GlsFixtures::request('delete-sped'));
});

test('list sped request emits the recorded wire format', function (): void {
    $request = new ListSpedRequest(GlsFixtures::credentials());

    expect($request->methodXml())->toBe(GlsFixtures::request('list-sped'));
});

test('a random progressive counter is generated when none is given', function (): void {
    $shipment = GlsFixtures::shipmentData(['progressiveCounter' => null]);

    $request = new AddParcelRequest(GlsFixtures::credentials(), $shipment);

    expect($request->methodXml())->toMatch('/<ContatoreProgressivo>\d{9}<\/ContatoreProgressivo>/');
});

test('optional elements follow the legacy rules', function (): void {
    $shipment = GlsFixtures::shipmentData([
        'cashOnDelivery' => 25.5,
        'codCollectionMode' => 'CONT',
        'services' => ['01', '36'],
        'labelFormat' => LabelFormat::Zpl,
        'deliveryTimeNote' => 'Sig Bianchi 9:00 - 17:00',
        'parcel' => new ParcelData(weight: 1.5, volume: 0.01),
    ]);

    $xml = new AddParcelRequest(GlsFixtures::credentials(), $shipment)->methodXml();

    expect($xml)->toContain('<ImportoContrassegno>25,50</ImportoContrassegno>')
        ->and($xml)->toContain('<ModalitaIncasso>CONT</ModalitaIncasso>')
        ->and($xml)->toContain('<ServiziAccessori>01,36</ServiziAccessori>')
        ->and($xml)->toContain('<GeneraPdf>6</GeneraPdf>')
        ->and($xml)->toContain('<OrarioNoteGDO>Sig Bianchi 9:00 - 17:00</OrarioNoteGDO>');
});
