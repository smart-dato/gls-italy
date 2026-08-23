<?php

namespace SmartDato\GlsItaly\Enums;

/**
 * The TipoSvincolo codes from MU276 §3.3.
 */
enum StockReleaseType: string
{
    case RedeliverSameAddress = '1';
    case DeliverToOtherAddress = '2';
    case ReturnToSender = '3';
    case Destroy = '4';
    case CollectAtDepot = '7';
    case PartialDeliveryAndReturn = '8';
    case PartialDeliveryAndDestroy = '9';
}
