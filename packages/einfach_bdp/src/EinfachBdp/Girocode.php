<?php

namespace EinfachBdP;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Erzeugt den GiroCode zu einer Bankverbindung.
 *
 * Warum das gerechnet und nicht als Bild hochgeladen wird
 * ------------------------------------------------------
 * Vorher lag der Code als fertige SVG im Theme. Sobald die Redaktion
 * im Block eine andere IBAN einträgt, zeigt ein hochgeladenes Bild
 * weiter die alte — und zwar ohne dass es jemandem auffällt, denn ein
 * QR-Code sieht immer gleich aus. Wer ihn scannt, überweist dann an
 * das falsche Konto. Deshalb entsteht der Code aus genau den Feldern,
 * die auch als Text auf der Seite stehen.
 *
 * Das Format
 * ----------
 * EPC069-12, der Standard des European Payments Council, den alle
 * Banking-Apps im SEPA-Raum lesen. Zwölf Zeilen, durch \n getrennt:
 *
 *   BCD              Dienstkennung
 *   002              Fassung (bei 002 darf die BIC fehlen)
 *   1                Zeichensatz 1 = UTF-8
 *   SCT              SEPA Credit Transfer
 *   BIC
 *   Empfänger        höchstens 70 Zeichen
 *   IBAN
 *   Betrag           z. B. EUR12.34, hier immer leer
 *   Zweckcode
 *   Strukturierte Referenz
 *   Verwendungszweck höchstens 140 Zeichen
 *   Hinweis an den Zahler
 *
 * Betrag und Verwendungszweck bleiben leer: Was jemand überweist und
 * wofür, entscheidet er selbst.
 */
class Girocode
{
    /** Fehlerkorrekturstufe M, wie im EPC-Standard vorgesehen. */
    const STUFE = 0;

    /**
     * Baut die Nutzlast. Gibt '' zurück, wenn Pflichtangaben fehlen
     * oder zu lang sind — dann zeigt der Block keinen Code, statt
     * einen kaputten zu zeigen.
     */
    public static function nutzlast($empfaenger, $iban, $bic = '', $zweck = '')
    {
        $empfaenger = trim(preg_replace('/\s+/u', ' ', (string) $empfaenger));
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $iban));
        $bic = strtoupper(preg_replace('/\s+/', '', (string) $bic));
        $zweck = trim(preg_replace('/\s+/u', ' ', (string) $zweck));

        if ($empfaenger === '' || $iban === '') {
            return '';
        }
        if (mb_strlen($empfaenger) > 70 || mb_strlen($zweck) > 140) {
            return '';
        }
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban)) {
            return '';
        }
        if ($bic !== '' && !preg_match('/^[A-Z0-9]{8}([A-Z0-9]{3})?$/', $bic)) {
            return '';
        }

        return implode("\n", [
            'BCD', '002', '1', 'SCT',
            $bic, $empfaenger, $iban,
            '', '', '', $zweck, '',
        ]);
    }

    /**
     * Der Code als SVG. Gibt '' zurück, wenn nichts zu kodieren ist.
     *
     * SVG und kein PNG: Der Code besteht aus Rechtecken, wird auf
     * jedem Bildschirm scharf und wiegt weniger als drei Kilobyte.
     */
    public static function svg($empfaenger, $iban, $bic = '', $zweck = '', $farbe = '#0E4278')
    {
        $nutzlast = self::nutzlast($empfaenger, $iban, $bic, $zweck);
        if ($nutzlast === '') {
            return '';
        }

        require_once __DIR__ . '/../Fremd/qrcode.php';

        /* getMinimumQRCode() lässt die Fassung auf 1 stehen, wenn die
           Daten in keine passen, und läuft dann in einen harten
           Fehler. Bei 12 Zeilen EPC kann das nicht passieren, aber
           eine Blockansicht darf an so etwas grundsätzlich nicht
           sterben. */
        if (strlen($nutzlast) > 900) {
            return '';
        }

        $qr = \QRCode::getMinimumQRCode($nutzlast, self::STUFE);
        $anzahl = $qr->getModuleCount();
        if ($anzahl < 21) {
            return '';
        }

        $rand = 2;
        $kante = $anzahl + 2 * $rand;

        /* Ein Pfad aus waagerechten Strichen statt vieler <rect>:
           dieselbe Datei wird dadurch etwa um zwei Drittel kleiner,
           und die Kanten bleiben durch shape-rendering scharf. */
        $pfad = '';
        for ($z = 0; $z < $anzahl; $z++) {
            $lauf = 0;
            for ($s = 0; $s <= $anzahl; $s++) {
                $dunkel = $s < $anzahl && $qr->isDark($z, $s);
                if ($dunkel) {
                    ++$lauf;
                    continue;
                }
                if ($lauf > 0) {
                    $pfad .= 'M' . ($s - $lauf + $rand) . ' ' . ($z + $rand + 0.5) . 'h' . $lauf;
                    $lauf = 0;
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $kante . ' ' . $kante . '"'
            . ' shape-rendering="crispEdges" role="img">'
            . '<path fill="#FFFFFF" d="M0 0h' . $kante . 'v' . $kante . 'H0z"/>'
            . '<path stroke="' . htmlspecialchars($farbe, ENT_QUOTES) . '" d="' . $pfad . '"/>'
            . '</svg>';
    }
}
