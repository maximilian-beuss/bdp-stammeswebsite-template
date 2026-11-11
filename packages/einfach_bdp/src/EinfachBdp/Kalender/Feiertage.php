<?php

namespace EinfachBdP\Kalender;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Gesetzliche Feiertage in Hessen.
 *
 * Gerechnet statt gepflegt: Bis auf die vier festen Termine hängen alle
 * am Ostersonntag, und der lässt sich für jedes Jahr ausrechnen. Damit
 * muss niemand jedes Jahr eine Liste nachtragen, und es kann auch
 * niemand vergessen.
 *
 * Hessen-spezifisch ist Fronleichnam. Reformationstag und Allerheiligen
 * sind hier keine Feiertage und stehen deshalb nicht in der Liste.
 *
 * Die Schulferien lassen sich nicht rechnen — die legt das
 * Kultusministerium fest. Die werden im Dashboard von Hand eingetragen.
 */
class Feiertage
{
    /**
     * @return array Liste aus ['titel' => …, 'datum' => 'Y-m-d']
     */
    public static function fuerJahr($jahr)
    {
        $jahr = (int) $jahr;

        // easter_date() steckt in der ext/calendar, die nicht überall
        // aktiv ist. Deshalb die Anonyme Gaußsche Osterformel von Hand.
        $ostern = self::ostersonntag($jahr);

        $verschoben = function ($tage) use ($ostern) {
            return date('Y-m-d', strtotime($tage . ' days', $ostern));
        };

        return [
            ['titel' => 'Neujahr', 'datum' => $jahr . '-01-01'],
            ['titel' => 'Karfreitag', 'datum' => $verschoben(-2)],
            ['titel' => 'Ostersonntag', 'datum' => $verschoben(0)],
            ['titel' => 'Ostermontag', 'datum' => $verschoben(1)],
            ['titel' => 'Tag der Arbeit', 'datum' => $jahr . '-05-01'],
            ['titel' => 'Christi Himmelfahrt', 'datum' => $verschoben(39)],
            ['titel' => 'Pfingstsonntag', 'datum' => $verschoben(49)],
            ['titel' => 'Pfingstmontag', 'datum' => $verschoben(50)],
            ['titel' => 'Fronleichnam', 'datum' => $verschoben(60)],
            ['titel' => 'Tag der Deutschen Einheit', 'datum' => $jahr . '-10-03'],
            ['titel' => '1. Weihnachtstag', 'datum' => $jahr . '-12-25'],
            ['titel' => '2. Weihnachtstag', 'datum' => $jahr . '-12-26'],
        ];
    }

    /**
     * Ostersonntag nach der Gaußschen Osterformel in der Fassung von
     * Lichtenberg. Gilt für den gregorianischen Kalender.
     *
     * @return int Zeitstempel
     */
    public static function ostersonntag($jahr)
    {
        $jahr = (int) $jahr;

        $k = intdiv($jahr, 100);
        $m = 15 + intdiv(3 * $k + 3, 4) - intdiv(8 * $k + 13, 25);
        $s = 2 - intdiv(3 * $k + 3, 4);
        $a = $jahr % 19;
        $d = (19 * $a + $m) % 30;
        $r = intdiv($d + intdiv($a, 11), 29);
        $og = 21 + $d - $r;
        $sz = 7 - ($jahr + intdiv($jahr, 4) + $s) % 7;
        $oe = 7 - ($og - $sz) % 7;
        $os = $og + $oe;   // Tag im März, kann über 31 hinausgehen

        return mktime(12, 0, 0, 3, $os, $jahr);
    }

    /**
     * Trägt die Feiertage eines Jahres ein. Vorhandene Einträge mit
     * gleichem Titel und Datum werden nicht verdoppelt.
     *
     * @return int Zahl der geschriebenen Einträge
     */
    public static function eintragen($jahr)
    {
        $n = 0;
        foreach (self::fuerJahr($jahr) as $tag) {
            // Als „gerechnet“ gekennzeichnet, damit die Einträge weichen
            // können, sobald die Schnittstelle dasselbe Jahr liefert.
            Speicher::freiSetzen($tag['titel'], $tag['datum'], $tag['datum'], 'feiertag', 'gerechnet');
            ++$n;
        }

        return $n;
    }
}
