<?php

namespace SmartDato\GlsItaly\Tracking\Results;

use Carbon\Carbon;
use Exception;
use SimpleXMLElement;
use SmartDato\GlsItaly\Data\TrackingEventData;

/**
 * The T&T feed's TRACKING element is a FLAT repeating sequence of
 * Data/Ora/Luogo/Stato/Note/Codice children, not one wrapper per event — the
 * walker groups them positionally in sixes, exactly like the legacy OLC
 * parser. Feed quirks preserved: a blank time means 20:01, and times sometimes
 * arrive as H.i instead of H:i. An incomplete trailing group is dropped.
 */
trait ParsesTrackingEvents
{
    /**
     * @return array<int, TrackingEventData>
     */
    protected function walkShipmentEvents(?SimpleXMLElement $container): array
    {
        $tracking = $container?->SPEDIZIONE?->TRACKING;

        if ($tracking === null) {
            return [];
        }

        $groups = [];
        $values = [];
        foreach ($tracking->children() as $child) {
            $values[] = trim((string) $child);
            if (count($values) === 6) {
                $groups[] = $values;
                $values = [];
            }
        }

        return array_map(function (array $group): TrackingEventData {
            $datetime = $this->parseEventDatetime($group[0], $group[1]);

            return new TrackingEventData(
                datetime: $datetime,
                subsidiary: $group[2],
                code: $group[5],
                state: $group[3],
                note: $group[4],
                warning: $datetime === null,
            );
        }, $groups);
    }

    protected function parseEventDatetime(string $date, string $time): ?Carbon
    {
        try {
            if (strlen($time) == 0) {
                return Carbon::createFromFormat('d/m/y H:i', $date.' 20:01');
            }

            return Carbon::createFromFormat('d/m/y H:i', $date.' '.$time);
        } catch (Exception $e) {
            try {
                return Carbon::createFromFormat('d/m/y H.i', $date.' '.$time);
            } catch (Exception $e) {
                return null;
            }
        }
    }
}
