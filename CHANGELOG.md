# Changelog

All notable changes to `gls-italy` will be documented in this file.

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
