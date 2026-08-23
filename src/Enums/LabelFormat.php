<?php

namespace SmartDato\GlsItaly\Enums;

/**
 * The GeneraPdf request values from MU162 §AddParcel.
 */
enum LabelFormat: int
{
    case None = 0;
    case PdfDeferred = 3;
    case Pdf = 4;
    case ZplDeferred = 5;
    case Zpl = 6;
}
