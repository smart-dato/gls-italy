# GLS Italy SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/smart-dato/gls-italy.svg?style=flat-square)](https://packagist.org/packages/smart-dato/gls-italy)
[![Tests](https://img.shields.io/github/actions/workflow/status/smart-dato/gls-italy/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/smart-dato/gls-italy/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/smart-dato/gls-italy.svg?style=flat-square)](https://packagist.org/packages/smart-dato/gls-italy)

A fluent PHP SDK for the GLS Italy carrier web services, built on
[Saloon](https://docs.saloon.dev) and
[spatie/laravel-data](https://spatie.be/docs/laravel-data).

The wire format is preserved byte for byte from the battle-tested integration
this package was extracted from — including the GLS field formatters (`alfa`,
`alfaStreetCity`, `alfaEmail`), the SOAP-1.2-envelope-around-XML-string
protocol, the CDATA-wrapped inner `<Info>` documents and the literal Italian
success strings GLS answers with. The request builders and result objects
never touch your models, storage paths or database — you map in, you persist
out.

## Coverage

| GLS service | Manual | Status |
| --- | --- | --- |
| Label service — `AddParcel`, `CloseWorkDay`, `DeleteSped`, `ListSped` | MU162 | ✅ since `0.0.1` |
| Pickups — `addpickup.php` / `deletepickup.php` | MU302 | ✅ since `0.0.1` |
| Tracking — `get_xml_track.php` | MU40 | ✅ since `0.0.3` |
| Stock release (svincolo giacenze) — `redelivery_parcel.php` | MU276 | planned |
| Address validation — `wscheckaddress.asmx` | — | planned |

The label service and pickup wire formats are verified byte for byte against
recorded production calls.

## Track a shipment or pickup

Tracking needs no password — just the depot and client/contract codes:

```php
$result = GlsItaly::trackByShipmentNumber('BZ', '620873098', '1234567');

foreach ($result->events() as $event) {
    $event->datetime;   // Carbon, or null when the feed value was unparseable
    $event->code;       // e.g. '901'; '906' is delivered
    $event->subsidiary; // the depot the event happened at
    $event->warning;    // true when the datetime could not be parsed
}

$result->shipmentNumber();
$result->destinationSede();
$result->retourReference(); // ['sede' => 'VE', 'number' => '123456789'] when GLS created a retour

$pickup = GlsItaly::trackPickup('BB', '9660004359', '2557');
$pickup->pickupEvents();    // TRACKINGRITIRO events
$pickup->shipmentEvents();  // events of the shipment the pickup generated
```

`trackByBda()` and `trackRetour()` cover the BDA and retour lookups.

## Requirements

- PHP `^8.4`
- Laravel `^11.0 || ^12.0 || ^13.0`

## Installation

```bash
composer require smart-dato/gls-italy
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="gls-italy-config"
```

```php
return [
    'endpoints' => [
        'label_service' => 'https://labelservice.gls-italy.com/ilswebservice.asmx',
        // legacy, stock_release, tracking, check_address ...
    ],
    'http' => [
        'timeout' => 30,
        'verify' => true,
        'label_service_content_type' => 'text/xml',
    ],
];
```

Every endpoint can be swapped, e.g. to route the label service through an HTTP
proxy: the SOAP method is selected by the request body's root element, never by
the URL, so a bare URL swap is enough. `http.verify` disables TLS peer
verification and `http.label_service_content_type` overrides the POST content
type for proxies that expect something other than `text/xml`.

## Getting started

Credentials are passed per call — the package holds no account state, so one
application can serve any number of GLS accounts:

```php
use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Facades\GlsItaly;

$gls = GlsItaly::withCredentials(new Credentials(
    sede: 'BZ',            // two-character depot code
    clientCode: '1234567', // codice cliente
    password: 'secret',
));
```

Outside Laravel-facade contexts, `new \SmartDato\GlsItaly\GlsItaly()` works the
same way — `withCredentials()` returns a configured clone.

## Create a shipment and store its label

```php
use SmartDato\GlsItaly\Data\ParcelData;
use SmartDato\GlsItaly\Data\RecipientData;
use SmartDato\GlsItaly\Enums\LabelFormat;

$result = $gls->shipment()
    ->contractCode('2557')                       // CodiceContrattoGls
    ->recipient(new RecipientData(
        name: 'Mario Rossi',
        street: 'Via Roma 1',
        city: 'Bolzano',
        zipcode: '39100',
        province: 'BZ',
        email: 'mario.rossi@example.com',
        phone: '+390471123456',                  // last 10 digits are sent as Cellulare1
    ))
    ->parcel(new ParcelData(weight: 1.5, volume: 0.01))
    ->cashOnDelivery(25.50, collectionMode: 'CONT') // ImportoContrassegno + ModalitaIncasso
    ->note('Mario Rossi tel +390471123456')      // NoteSpedizione, printed on the label
    ->additionalNote('handle with care')         // NoteAggiuntive, shown on the driver device
    ->clientReference('ORDER-4242')              // RiferimentoCliente
    ->bda('O2600004242')
    ->services(['01', '36'])                     // accessory service codes, see below
    ->labelFormat(LabelFormat::Pdf)              // or LabelFormat::Zpl
    ->deliveryTimeNote('Sig. Bianchi 9:00 - 17:00') // OrarioNoteGDO, only when set
    ->create();
```

All recipient and note values are passed raw; the serializer applies the GLS
field formatters (ASCII transliteration, whitespace collapsing, symbol
stripping, length limits) exactly like the original integration, so what GLS
receives does not depend on the caller pre-cleaning anything.

The result exposes everything GLS returned:

```php
$result->shipmentNumber();          // '620873098' — '' when GLS returned none
$result->hasShipmentNumber();
$result->barcode();                 // 'BZ620873098010M5' — assembled from the response;
                                    // 'GLS Check' routings drop the destination suffix
$result->extractedLabelBarcode();   // barcode read back from the label PDF text
$result->pdf();                     // binary PDF label (LabelFormat::Pdf)
$result->zpl();                     // raw ZPL commands (LabelFormat::Zpl)
$result->destinationDescription();  // e.g. 'MILANO', or 'GLS Check' on failed routing
$result->errorDescription();        // the soap:Text error, when GLS rejected the call

$result->storePdf('labels/620873098.pdf', disk: 's3')  // chainable; throws
    ->storeZpl('labels/620873098.zpl', disk: 's3');    // StorageException on failure

$result->rawRequest();              // exact bytes sent — for your API-call audit log
$result->rawResponse();             // exact bytes received
```

When `hasShipmentNumber()` is `false`, GLS accepted the parcel but returned no
number — look it up among the shipments waiting for close-of-day, matched by
recipient exactly like the legacy fallback:

```php
$number = $gls->listPendingShipments()
    ->findPendingByRecipient('Mario Rossi', 'Bolzano', 'BZ', 'Via Roma 1');
```

## Close the work day

Closing transmits the day's shipments to GLS. Each shipment is identified by
repeating its full parcel record:

```php
$closing = $gls->closeWorkDay();

foreach ($shipmentRecords as $record) {
    $closing->addShipment($record); // ShipmentData — e.g. ShipmentBuilder::toData()
}

$result = $closing->send();

$result->succeeded();   // the literal <DescrizioneErrore>OK</DescrizioneErrore> marker
$result->manifestPdf(); // the DistintaPDF manifest, when GLS returns one
```

`ShipmentBuilder::toData()` returns the validated `ShipmentData` DTO, so the
same record you created the shipment with can be replayed into the close-of-day
call. Mind the MU162 payload limits (~400 parcel records per call when PDF
labels are requested, ~1000 without).

## List and delete shipments

```php
foreach ($gls->listPendingShipments()->shipments() as $pending) {
    $pending->shipmentNumber; // plus date, recipientName, city, province, street,
                              // parcelCount, weight, status, routing, ...
}

$deletion = $gls->deleteShipment('620873098');
$deletion->succeeded();   // GLS confirms with an Italian sentence, not a status code
$deletion->resultText();  // the raw <DeleteSpedResult> for logging
```

## Request a pickup

Pickups go through the legacy MU302 channel (`addpickup.php`). Note the
credential difference: this channel authenticates with the web **profile**
code (`CodiceProfiloGls`), which is usually not the label-service client code.

```php
use SmartDato\GlsItaly\Data\PickupCancellationData;
use SmartDato\GlsItaly\Data\RecipientData;

$result = GlsItaly::withCredentials($pickupProfileCredentials)->pickup()
    ->requesterName('Your Company')
    ->bda('P2600044261')
    ->pickupAddress(new RecipientData(
        name: 'Mario Rossi',
        street: 'Via Roma 1',
        city: 'Bolzano',
        zipcode: '39100',
        province: 'BZ',
    ))
    ->pickupDate(new DateTimeImmutable('2026-08-24'))
    ->parcelCount(2)
    ->weight(12.0)
    ->deliveryAddress(null)              // null blanks the destination block (aggregated/detour)
    ->window('08', '13', '13', '18')     // the allowed morning/afternoon hour ranges
    ->notifyEmail('ops@example.com')
    ->phone('3288046977')
    ->create();

$result->pickupNumber();    // 'BB9660004359', null on failure
$result->error();           // GLS error text, or null on success
$result->ensureCreated();   // pickup number, or throws ApiErrorException

$cancellation = GlsItaly::withCredentials($pickupProfileCredentials)->cancelPickup(new PickupCancellationData(
    contractCode: '2557',
    pickupNumber: 'BB9660004359',
    requesterName: 'Mario Rossi',
    email: 'ops@example.com',
));
$cancellation->cancelled('BB9660004359');
```

## Accessory service codes (MU162)

`services()` takes the two-character GLS codes, for example:

| Code | Service | Code | Service |
| --- | --- | --- | --- |
| `01` | Entro ore 12 | `21` | Servizio al sabato (Venezia) |
| `02` | Ora fissa | `22` | Express 12 |
| `03` | Anticipato | `23` | Document return |
| `05` | Consegna al piano | `24` | Exchange |
| `06` | Mezzo idoneo | `25` | Preavviso telefonico |
| `07` | Servizio al sabato | `27` | Timbro |
| `08` | Verifica contenuto | `28` | e-ROD |
| `14`–`16` | Venezia laguna variants | `29` | Sabato pomeriggio |
| | | `36` | Saturday express |

## Error handling

GLS does not speak HTTP status codes — failures come back as error text inside
a 200 response, so results are always returned and inspected via
`succeeded()` / `errorDescription()`. The package throws only for problems on
your side of the wire, all extending `GlsItalyException` (except the builder
validation):

| Exception | Thrown when |
| --- | --- |
| `Exceptions\ValidationException` | a builder is sent incomplete — the message names the missing call (`"A recipient is required, call recipient() before create()."`) |
| `Exceptions\RequestException` | the connection itself failed (DNS, timeout, TLS) |
| `Exceptions\ResponseParseException` | a `store*()` helper is called but the response holds no label |
| `Exceptions\StorageException` | a `store*()` write returned `false` (disks configured with `throw => false`) |

## Faking in your tests

Saloon's `MockClient` intercepts every request the package sends:

```php
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

MockClient::global([
    MockResponse::make('<InfoLabel><Parcel><NumeroSpedizione>620873098</NumeroSpedizione>...</Parcel></InfoLabel>'),
]);

// exercise your code, then assert on the outgoing bytes:
MockClient::getGlobal()->getLastPendingRequest()->body()->all();

MockClient::destroyGlobal();
```

Pass `->progressiveCounter(999999999)` in tests to make the emitted XML fully
deterministic — without it, `ContatoreProgressivo` defaults to a random
nine-digit number, exactly like the original integration.

## Development

```bash
composer test      # pest — includes byte-for-byte wire-format snapshot tests
composer analyse   # phpstan
composer format    # pint
```

The wire snapshots in `tests/Fixtures/requests` were recorded from the
original production integration; the suite also reads real GLS label PDFs to
verify barcode extraction. Treat the snapshots as the contract — a diff there
means GLS receives different bytes.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [SmartDato](https://github.com/smart-dato)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
