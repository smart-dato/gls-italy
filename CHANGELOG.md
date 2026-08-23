# Changelog

All notable changes to `gls-italy` will be documented in this file.

## 0.0.4 - 2026-08-23

- Stock release / svincolo giacenze (MU276): `releaseStock()` POSTs the
  spec-compliant XML (profile credentials, GG/MM/AA redelivery date) to
  redelivery_parcel.php and parses the per-shipment `<Svincolo>` outcomes.
- Address validation: `checkAddress()` replicates the envelope PHP's native
  SoapClient sent to wscheckaddress.asmx byte for byte (verified against a
  captured SoapClient request) and exposes the raw `<Esito>` verdict.
- `LegacyXmlRequest` moved to the `Support` namespace.

## 0.0.3 - 2026-08-23

- Tracking (MU40): typed GET requests for the Track & Trace XML feed
  (`trackByShipmentNumber`, `trackByBda`, `trackPickup`, `trackRetour`, plus a
  pickups-by-date-range request), with `ShipmentTrackingResult` /
  `PickupTrackingResult` owning the flat six-children TRACKING walker, the
  blank-time→20:01 and `H.i` datetime quirks, retour-note splitting and
  TESTOERRORE handling.

## 0.0.2 - 2026-08-23

- `ShipmentBuilder::fromData()` and `PickupBuilder::fromData()` seed a builder
  from a ready-made record, e.g. produced by a consumer-side mapper.

## 0.0.1 - 2026-08-23

Initial release, extracted from the OLC GLS Italy integration with the wire
format preserved byte for byte and verified against recorded production calls.

- Label service (MU162): `AddParcel` via the fluent `ShipmentBuilder` with PDF
  and ZPL labels, `CloseWorkDay`, `DeleteSped`, `ListSped` including the
  pending-shipment fallback matcher, barcode assembly with the "GLS Check"
  rule, and barcode extraction from the label PDF text.
- Pickups (MU302, legacy channel): `addpickup.php` via the fluent
  `PickupBuilder`, `deletepickup.php` cancellations.
- Configurable endpoints (proxy-friendly), timeout, TLS verification and
  label-service content type.
- Results expose the raw request/response for audit logging and can be
  replayed from stored payloads via `fromStrings()`.
