<?php

namespace EinfachBdP\Kalender;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Die Kategorien des Stammeskalenders.
 *
 * Die Farben sind die Stufenfarben des CD. Sie sind hier absichtlich
 * als feste Hex-Werte hinterlegt und nicht als CSS-Variablen: die
 * iCal-Datei und die Legende im Dashboard brauchen den Wert als Text,
 * und ein Kalender, dessen Farben je nach Ausgabekanal auseinander
 * laufen, ist keiner.
 *
 * kontrast ist die Textfarbe auf der jeweiligen Fläche. Gelb trägt
 * Blau (7,00:1), die drei dunklen tragen Weiß (Blau 10,15:1,
 * Rot 8,35:1, Waldgrün 7,42:1) — alle AAA.
 */
class Stufen
{
    const ALLE = 'alle';

    public static function katalog()
    {
        return [
            'woelflinge' => [
                'name' => 'Wölflingsstufe',
                'kurz' => 'Wölflinge',
                'farbe' => '#FFD208',
                'kontrast' => '#0E4278',
            ],
            'pfadfinder' => [
                'name' => 'Pfadfinderstufe',
                'kurz' => 'Pfadfinder*innen',
                'farbe' => '#0E4278',
                'kontrast' => '#FFFFFF',
            ],
            'ranger' => [
                'name' => 'Thing / Ranger*Rover-Stufe',
                'kurz' => 'Thing / R*R',
                'farbe' => '#971B2F',
                'kontrast' => '#FFFFFF',
            ],
            'alle' => [
                'name' => 'Alle und Interessierte',
                'kurz' => 'Alle',
                'farbe' => '#006C55',
                'kontrast' => '#FFFFFF',
            ],
        ];
    }

    public static function gibt($handle)
    {
        return array_key_exists((string) $handle, self::katalog());
    }

    public static function eine($handle)
    {
        $katalog = self::katalog();
        $handle = (string) $handle;

        return isset($katalog[$handle]) ? $katalog[$handle] : $katalog[self::ALLE];
    }

    public static function farbe($handle)
    {
        $stufe = self::eine($handle);

        return $stufe['farbe'];
    }

    public static function name($handle)
    {
        $stufe = self::eine($handle);

        return $stufe['name'];
    }
}
