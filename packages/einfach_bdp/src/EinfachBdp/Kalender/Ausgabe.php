<?php

namespace EinfachBdP\Kalender;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Die beiden Ausgabewege des Kalenders.
 *
 *   /kalender/termine.ics   zum Abonnieren in Outlook, Apple, Google
 *   /kalender/daten         JSON für die Ansichten im Browser
 *
 * Beide hängen an Routen des Pakets und nicht am Block. Ein Abo darf
 * nicht kaputtgehen, weil jemand den Kalenderblock auf eine andere
 * Seite verschoben hat.
 */
class Ausgabe
{
    /* ---------------------------------------------------------------
       iCal
       --------------------------------------------------------------- */

    public function ical()
    {
        $termine = Speicher::ab(date('Y-m-d', strtotime('-2 years')), 2000);

        $zeilen = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Musterstamm//Stammeskalender//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Musterstamm',
            'X-WR-TIMEZONE:Europe/Berlin',
        ];

        $zeilen = array_merge($zeilen, $this->zeitzone());

        foreach ($termine as $t) {
            $zeilen = array_merge($zeilen, $this->einTermin($t));
        }

        $zeilen[] = 'END:VCALENDAR';

        $text = '';
        foreach ($zeilen as $zeile) {
            $text .= $this->falten($zeile) . "\r\n";
        }

        return new Response($text, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="stammeskalender.ics"',
            // Abonnenten fragen oft stündlich nach. Eine Viertelstunde
            // Cache nimmt Last, ohne dass Änderungen lange brauchen.
            'Cache-Control' => 'public, max-age=900',
        ]);
    }

    /**
     * Ein VEVENT.
     *
     * Ganztägige Termine bekommen VALUE=DATE. DTEND ist bei iCal
     * ausschließend: ein eintägiger Termin am 3. endet am 4. Wer das
     * vergisst, verkürzt jedes Lager um einen Tag.
     */
    protected function einTermin(array $t)
    {
        $ganztags = !empty($t['ganztaegig']);
        $beginn = strtotime($t['beginn']);
        $ende = strtotime($t['ende']);

        $zeilen = ['BEGIN:VEVENT'];
        $zeilen[] = 'UID:' . ($t['uid'] !== '' ? $t['uid'] : 'v' . $t['terminID']) . '@musterstamm.de';
        $zeilen[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');

        if ($ganztags) {
            $zeilen[] = 'DTSTART;VALUE=DATE:' . date('Ymd', $beginn);
            $zeilen[] = 'DTEND;VALUE=DATE:' . date('Ymd', strtotime('+1 day', $ende));
        } else {
            $zeilen[] = 'DTSTART;TZID=Europe/Berlin:' . date('Ymd\THis', $beginn);
            $zeilen[] = 'DTEND;TZID=Europe/Berlin:' . date('Ymd\THis', $ende);
        }

        $zeilen[] = 'SUMMARY:' . $this->maskieren($t['titel']);

        $beschreibung = trim((string) $t['beschreibung']);
        if ($t['link'] !== '') {
            $zeilen[] = 'URL:' . $this->maskieren($t['link']);
            $beschreibung = trim($beschreibung . "\n" . $t['link']);
        }
        if ($beschreibung !== '') {
            $zeilen[] = 'DESCRIPTION:' . $this->maskieren($beschreibung);
        }
        if ($t['ort'] !== '') {
            $zeilen[] = 'LOCATION:' . $this->maskieren($t['ort']);
        }

        $zeilen[] = 'CATEGORIES:' . $this->maskieren(Stufen::name($t['stufe']));
        if (!empty($t['geaendert'])) {
            $zeilen[] = 'LAST-MODIFIED:' . gmdate('Ymd\THis\Z', strtotime($t['geaendert']));
        }
        $zeilen[] = 'END:VEVENT';

        return $zeilen;
    }

    /**
     * VTIMEZONE für Europe/Berlin. Ohne sie legen manche Programme
     * Termine um eine Stunde versetzt an.
     */
    protected function zeitzone()
    {
        return [
            'BEGIN:VTIMEZONE',
            'TZID:Europe/Berlin',
            'BEGIN:DAYLIGHT',
            'TZOFFSETFROM:+0100',
            'TZOFFSETTO:+0200',
            'TZNAME:CEST',
            'DTSTART:19700329T020000',
            'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
            'END:DAYLIGHT',
            'BEGIN:STANDARD',
            'TZOFFSETFROM:+0200',
            'TZOFFSETTO:+0100',
            'TZNAME:CET',
            'DTSTART:19701025T030000',
            'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
            'END:STANDARD',
            'END:VTIMEZONE',
        ];
    }

    /**
     * Sonderzeichen nach RFC 5545: Backslash, Semikolon, Komma und
     * Zeilenumbruch. Reihenfolge zählt — der Backslash zuerst.
     */
    protected function maskieren($text)
    {
        $text = strip_tags((string) $text);
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace([';', ','], ['\\;', '\\,'], $text);
        $text = preg_replace('/\r\n|\r|\n/', '\\n', $text);

        return $text;
    }

    /**
     * Zeilen über 75 Oktetten werden umgebrochen, Folgezeilen beginnen
     * mit einem Leerzeichen. Umgebrochen wird an Zeichengrenzen, sonst
     * zerreißt es Umlaute.
     */
    protected function falten($zeile)
    {
        if (strlen($zeile) <= 74) {
            return $zeile;
        }

        $teile = [];
        $aktuell = '';
        $laenge = 0;
        $grenze = 73;

        foreach (preg_split('//u', $zeile, -1, PREG_SPLIT_NO_EMPTY) as $zeichen) {
            $breite = strlen($zeichen);
            if ($laenge + $breite > $grenze) {
                $teile[] = $aktuell;
                $aktuell = '';
                $laenge = 0;
                $grenze = 73;
            }
            $aktuell .= $zeichen;
            $laenge += $breite;
        }
        $teile[] = $aktuell;

        return implode("\r\n ", $teile);
    }

    /* ---------------------------------------------------------------
       JSON für die Ansichten
       --------------------------------------------------------------- */

    public function daten()
    {
        $anfrage = Request::createFromGlobals();

        $von = $this->datumOderNull($anfrage->query->get('von')) ?: date('Y-01-01');
        $bis = $this->datumOderNull($anfrage->query->get('bis')) ?: date('Y-12-31');

        // Ein Fenster über drei Jahre ergibt keinen Sinn und wäre nur
        // ein bequemer Weg, die Datenbank zu beschäftigen.
        if (strtotime($bis) - strtotime($von) > 3 * 366 * 86400) {
            $bis = date('Y-m-d', strtotime($von . ' +3 years'));
        }

        $termine = [];
        foreach (Speicher::zeitraum($von, $bis) as $t) {
            $termine[] = [
                'id' => (int) $t['terminID'],
                'titel' => $t['titel'],
                'beginn' => $t['beginn'],
                'ende' => $t['ende'],
                'ganztaegig' => (bool) $t['ganztaegig'],
                'stufe' => $t['stufe'],
                'ort' => $t['ort'],
                'beschreibung' => $t['beschreibung'],
                'link' => $t['link'],
            ];
        }

        $frei = [];
        foreach (Speicher::freieTage($von, $bis) as $f) {
            $frei[] = [
                'titel' => $f['titel'],
                'beginn' => $f['beginn'],
                'ende' => $f['ende'],
                'art' => $f['art'],
            ];
        }

        return new Response(
            json_encode([
                'ok' => true,
                'von' => $von,
                'bis' => $bis,
                'stufen' => Stufen::katalog(),
                'termine' => $termine,
                'frei' => $frei,
            ], JSON_UNESCAPED_UNICODE),
            200,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    protected function datumOderNull($wert)
    {
        $wert = (string) $wert;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $wert) ? $wert : null;
    }
}
