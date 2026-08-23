<?php

use SmartDato\GlsItaly\Data\PickupData;
use SmartDato\GlsItaly\Exceptions\ValidationException;
use SmartDato\GlsItaly\GlsItaly;
use SmartDato\GlsItaly\Tests\Fixtures\GlsFixtures;

test('creating a shipment without credentials fails with an actionable message', function (): void {
    expect(fn () => new GlsItaly()->shipment()
        ->contractCode('2557')
        ->recipient(GlsFixtures::recipient())
        ->parcel(GlsFixtures::shipmentData()->parcel)
        ->create())
        ->toThrow(ValidationException::class, 'Credentials are required, call GlsItaly::withCredentials() or credentials() before create().');
});

test('creating a shipment without a recipient fails with an actionable message', function (): void {
    expect(fn () => new GlsItaly()->withCredentials(GlsFixtures::credentials())->shipment()
        ->contractCode('2557')
        ->parcel(GlsFixtures::shipmentData()->parcel)
        ->create())
        ->toThrow(ValidationException::class, 'A recipient is required, call recipient() before create().');
});

test('creating a shipment without a parcel fails with an actionable message', function (): void {
    expect(fn () => new GlsItaly()->withCredentials(GlsFixtures::credentials())->shipment()
        ->contractCode('2557')
        ->recipient(GlsFixtures::recipient())
        ->create())
        ->toThrow(ValidationException::class, 'A parcel is required, call parcel() before create().');
});

test('creating a shipment without a contract code fails with an actionable message', function (): void {
    expect(fn () => new GlsItaly()->withCredentials(GlsFixtures::credentials())->shipment()
        ->recipient(GlsFixtures::recipient())
        ->parcel(GlsFixtures::shipmentData()->parcel)
        ->create())
        ->toThrow(ValidationException::class, 'A contract code is required, call contractCode() before create().');
});

test('close work day requires at least one shipment', function (): void {
    expect(fn () => new GlsItaly()->withCredentials(GlsFixtures::credentials())->closeWorkDay()->send())
        ->toThrow(ValidationException::class, 'At least one shipment is required, call addShipment() before send().');
});

test('deleting without credentials fails with an actionable message', function (): void {
    expect(fn () => new GlsItaly()->deleteShipment('620873098'))
        ->toThrow(ValidationException::class, 'Credentials are required, call withCredentials() first.');
});

test('the facade resolves the entry object from the container', function (): void {
    expect(SmartDato\GlsItaly\Facades\GlsItaly::withCredentials(GlsFixtures::credentials()))
        ->toBeInstanceOf(GlsItaly::class);
});

test('fromData seeds the shipment builder with a complete record', function (): void {
    $data = GlsFixtures::shipmentData(['cashOnDelivery' => 25.5, 'codCollectionMode' => 'CONT', 'services' => ['01', '36']]);

    $roundTripped = new GlsItaly()->withCredentials(GlsFixtures::credentials())
        ->shipment()
        ->fromData($data)
        ->toData();

    expect($roundTripped)->toEqual($data);
});

test('fromData seeds the pickup builder with a complete record', function (): void {
    $data = new PickupData(
        contractCode: '9876',
        requesterName: 'GLSXMLTEST',
        bda: 'P2600007777',
        pickupAddress: GlsFixtures::recipient(),
        pickupDate: new DateTimeImmutable('2026-09-01'),
        parcelCount: 2,
        weight: 10.5,
        note: 'fragile',
        notifyEmail: 'ops@example.com',
        phone: '+390471998877',
    );

    $roundTripped = new GlsItaly()->withCredentials(GlsFixtures::credentials())
        ->pickup()
        ->fromData($data)
        ->toData();

    expect($roundTripped)->toEqual($data);
});
