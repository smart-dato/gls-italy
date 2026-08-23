<?php

namespace SmartDato\GlsItaly\Tests\Fixtures;

use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\ParcelData;
use SmartDato\GlsItaly\Data\RecipientData;
use SmartDato\GlsItaly\Data\ShipmentData;

/**
 * The exact model values the OLC characterization tests recorded their wire
 * snapshots with (tests/Fixtures/requests/*.xml) — keep both in sync.
 */
class GlsFixtures
{
    public static function credentials(): Credentials
    {
        return new Credentials(
            sede: 'BZ',
            clientCode: '123456',
            password: 'secret',
            contractCode: '2557',
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function shipmentData(array $overrides = []): ShipmentData
    {
        return new ShipmentData(...array_merge([
            'contractCode' => '2557',
            'recipient' => self::recipient(),
            'parcel' => new ParcelData(weight: 1.5, volume: 0.01),
            'note' => 'Mario Rossi tel +390471123456 handle with care',
            'additionalNote' => 'handle with care',
            'clientReference' => 'OLS202600004242',
            'bda' => 'O2600004242',
            'progressiveCounter' => 999999999,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function recipient(array $overrides = []): RecipientData
    {
        return new RecipientData(...array_merge([
            'name' => 'Mario Rossi',
            'street' => 'Via Roma 1',
            'city' => 'Bolzano',
            'zipcode' => '39100',
            'province' => 'BZ',
            'email' => 'Mario.Rossi@Example.com',
            'phone' => '+390471123456',
        ], $overrides));
    }

    public static function request(string $name): string
    {
        return file_get_contents(__DIR__."/requests/{$name}.xml");
    }

    public static function label(string $name): string
    {
        return file_get_contents(__DIR__."/labels/{$name}.pdf");
    }
}
