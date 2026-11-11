<?php

namespace EinfachBdP\Kalender;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Config;

/**
 * Schulferien und Feiertage von mehr-schulferien.de.
 *
 * Die Schnittstelle
 * -----------------
 *   GET https://www.mehr-schulferien.de/api/v2.1/federal-states/{land}
 *       /periods?start_date=JJJJ-MM-TT&end_date=JJJJ-MM-TT
 *
 * Sie liefert je Eintrag name, type (public_holiday oder
 * school_vacation), starts_on und ends_on. Zeiträume, die in den
 * angefragten Bereich hineinragen, kommen vollständig mit — die
 * Weihnachtsferien 2025/26 erscheinen also auch bei einer Anfrage für
 * 2026. Das ist gewollt und wird hier nicht abgeschnitten.
 *
 * Der Cache
 * ---------
 * Die Datenbank ist der Cache. Abgerufene Zeiträume landen in
 * BdPKalenderfrei, und in BdPFerienabruf steht, wann für
 * welches Land und welches Jahr zuletzt gefragt wurde.
 *
 * Ohne dieses Protokoll gäbe es keinen Unterschied zwischen „für 2029
 * gibt es noch keine Ferientermine“ und „2029 wurde noch nie
 * abgefragt“ — die Folge wäre bei jedem Seitenaufruf eine neue
 * Anfrage. Genau das soll nicht passieren: Ferientermine ändern sich
 * ein-, zweimal im Jahr, nicht im Minutentakt.
 *
 * Wie weit im Voraus
 * ------------------
 * Die Ministerien legen Ferien meist nur zwei bis drei Jahre im
 * Voraus fest. Für spätere Jahre antwortet die Schnittstelle mit einer
 * leeren Liste — kein Fehler, sondern schlicht noch nichts da. Deshalb
 * wird ein leeres Jahr nicht als Fehlschlag verbucht, aber auch nicht
 * ständig neu gefragt.
 *
 * Wenn die Schnittstelle ausfällt
 * -------------------------------
 * Dann bleibt stehen, was schon in der Datenbank liegt, und die
 * Feiertage lassen sich weiterhin rechnen (siehe Feiertage.php). Ein
 * Ausfall kostet also höchstens die Ferien eines noch nicht geholten
 * Jahres, nie die schon vorhandenen Daten.
 */
class Ferienquelle
{
    const BASIS = 'https://www.mehr-schulferien.de/api/v2.1/federal-states/';

    /** So lange gilt ein Abruf als frisch. */
    const FRISCH_TAGE = 7;

    /** Ein leeres Jahr wird seltener nachgefragt — da kommt so bald nichts. */
    const FRISCH_TAGE_LEER = 30;

    /** Sekunden, die eine Anfrage höchstens dauern darf. */
    const ZEITLIMIT = 8;

    /**
     * Die sechzehn Bundesländer mit den Kürzeln, die die Schnittstelle
     * in der Adresse erwartet.
     *
     * Fest eingetragen und nicht abgefragt: Die Liste ändert sich
     * seit 1990 nicht, und eine Anfrage nur zum Füllen eines
     * Auswahlfelds wäre verschwendet. Geprüft gegen
     * /api/v2.1/federal-states.
     */
    public static function laender()
    {
        return [
            'baden-wuerttemberg' => 'Baden-Württemberg',
            'bayern' => 'Bayern',
            'berlin' => 'Berlin',
            'brandenburg' => 'Brandenburg',
            'bremen' => 'Bremen',
            'hamburg' => 'Hamburg',
            'hessen' => 'Hessen',
            'mecklenburg-vorpommern' => 'Mecklenburg-Vorpommern',
            'niedersachsen' => 'Niedersachsen',
            'nordrhein-westfalen' => 'Nordrhein-Westfalen',
            'rheinland-pfalz' => 'Rheinland-Pfalz',
            'saarland' => 'Saarland',
            'sachsen' => 'Sachsen',
            'sachsen-anhalt' => 'Sachsen-Anhalt',
            'schleswig-holstein' => 'Schleswig-Holstein',
            'thueringen' => 'Thüringen',
        ];
    }

    /**
     * Das eingestellte Bundesland. Hessen, solange nichts anderes
     * gewählt wurde — der Stamm sitzt in Schwalbach am Taunus.
     */
    public static function land()
    {
        $land = (string) Config::get('einfach_bdp.ferien.land', 'hessen');

        return array_key_exists($land, self::laender()) ? $land : 'hessen';
    }

    public static function landSetzen($land)
    {
        if (!array_key_exists($land, self::laender())) {
            return false;
        }

        Config::save('einfach_bdp.ferien.land', $land);

        return true;
    }

    public static function landName($land = null)
    {
        $land = $land === null ? self::land() : $land;
        $alle = self::laender();

        return isset($alle[$land]) ? $alle[$land] : $land;
    }

    protected static function db()
    {
        return Application::getFacadeApplication()->make('database')->connection();
    }

    /* ==============================================================
       Abrufen
       ============================================================== */

    /**
     * Holt ein Jahr, wenn nötig.
     *
     * @param int    $jahr
     * @param string $land     null = das eingestellte
     * @param bool   $erzwingen Protokoll ignorieren und neu holen
     *
     * @return array{status:string,anzahl:int,meldung:string}
     *         status: geholt | frisch | leer | fehler
     */
    public static function jahrSichern($jahr, $land = null, $erzwingen = false)
    {
        $jahr = (int) $jahr;
        $land = $land === null ? self::land() : $land;

        if (!array_key_exists($land, self::laender())) {
            return ['status' => 'fehler', 'anzahl' => 0, 'meldung' => 'Unbekanntes Bundesland: ' . $land];
        }

        if ($jahr < 2000 || $jahr > 2100) {
            return ['status' => 'fehler', 'anzahl' => 0, 'meldung' => 'Jahr außerhalb des Bereichs'];
        }

        if (!$erzwingen && self::istFrisch($land, $jahr)) {
            return ['status' => 'frisch', 'anzahl' => 0, 'meldung' => 'Bereits aktuell'];
        }

        $antwort = self::anfragen($land, $jahr);

        if ($antwort['fehler'] !== '') {
            self::abrufMerken($land, $jahr, 0, $antwort['fehler']);

            return ['status' => 'fehler', 'anzahl' => 0, 'meldung' => $antwort['fehler']];
        }

        $geschrieben = self::eintragen($antwort['zeitraeume'], $land);
        self::abrufMerken($land, $jahr, count($antwort['zeitraeume']), '');

        // Die Schnittstelle ist jetzt die Quelle für dieses Jahr. Was
        // vorher gerechnet wurde, kann weg — sonst stünden „Neujahr“
        // und „Neujahrstag“ am selben Tag nebeneinander.
        if (count($antwort['zeitraeume']) > 0) {
            Speicher::gerechneteLoeschen($jahr);
        }

        if (count($antwort['zeitraeume']) === 0) {
            return [
                'status' => 'leer',
                'anzahl' => 0,
                'meldung' => 'Für ' . $jahr . ' liegen noch keine Termine vor.',
            ];
        }

        return [
            'status' => 'geholt',
            'anzahl' => $geschrieben,
            'meldung' => $geschrieben . ' Zeiträume übernommen.',
        ];
    }

    /**
     * Sorgt dafür, dass die genannten Jahre vorliegen. Für den
     * Aufruf aus dem Dashboard und von der Kommandozeile.
     *
     * @return array Jahr => Ergebnis von jahrSichern()
     */
    public static function jahreSichern(array $jahre, $land = null, $erzwingen = false)
    {
        $ergebnis = [];

        foreach ($jahre as $jahr) {
            $ergebnis[(int) $jahr] = self::jahrSichern($jahr, $land, $erzwingen);
        }

        return $ergebnis;
    }

    /* ==============================================================
       Netz
       ============================================================== */

    /**
     * @return array{zeitraeume:array,fehler:string}
     */
    protected static function anfragen($land, $jahr)
    {
        $adresse = self::BASIS . rawurlencode($land) . '/periods'
            . '?start_date=' . $jahr . '-01-01'
            . '&end_date=' . $jahr . '-12-31';

        $roh = self::abholen($adresse, $status);

        if ($roh === null) {
            return ['zeitraeume' => [], 'fehler' => 'Die Schnittstelle war nicht erreichbar.'];
        }

        if ($status >= 400) {
            return ['zeitraeume' => [], 'fehler' => 'Die Schnittstelle antwortete mit HTTP ' . $status . '.'];
        }

        $daten = json_decode($roh, true);

        if (!is_array($daten)) {
            return ['zeitraeume' => [], 'fehler' => 'Die Antwort war kein gültiges JSON.'];
        }

        // Die Schnittstelle meldet Fehler auch mit HTTP 200 im Rumpf.
        if (!empty($daten['errors'])) {
            $erster = is_array($daten['errors']) ? reset($daten['errors']) : [];
            $text = is_array($erster) && isset($erster['detail']) ? $erster['detail'] : 'Unbekannter Fehler';

            return ['zeitraeume' => [], 'fehler' => 'Die Schnittstelle meldet: ' . $text];
        }

        if (!isset($daten['data']) || !is_array($daten['data'])) {
            return ['zeitraeume' => [], 'fehler' => 'Die Antwort enthielt kein Feld „data“.'];
        }

        $zeitraeume = [];

        foreach ($daten['data'] as $eintrag) {
            if (!is_array($eintrag)) {
                continue;
            }

            $von = isset($eintrag['starts_on']) ? (string) $eintrag['starts_on'] : '';
            $bis = isset($eintrag['ends_on']) ? (string) $eintrag['ends_on'] : $von;
            $name = trim((string) ($eintrag['name'] ?? ''));

            if ($name === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $von)) {
                continue;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $bis) || $bis < $von) {
                $bis = $von;
            }

            // Die Flags sind verlässlicher als das Feld type: Bei
            // Zeiträumen, die beides sind (bewegliche Ferientage rund
            // um einen Feiertag), stehen sie beide auf true.
            $istFerien = !empty($eintrag['is_school_vacation'])
                || ($eintrag['type'] ?? '') === 'school_vacation';

            $zeitraeume[] = [
                'titel' => $name,
                'von' => $von,
                'bis' => $bis,
                'art' => $istFerien ? 'ferien' : 'feiertag',
            ];
        }

        return ['zeitraeume' => $zeitraeume, 'fehler' => ''];
    }

    /**
     * Holt eine Adresse. curl, wenn vorhanden, sonst Streams.
     *
     * @param int|null $status wird gesetzt
     *
     * @return string|null null = gar nicht erreicht
     */
    protected static function abholen($adresse, &$status)
    {
        $status = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init($adresse);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::ZEITLIMIT,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_USERAGENT => 'BdP-Ortsgruppe Website (Kalender)',
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            $antwort = curl_exec($ch);
            $fehler = curl_errno($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            return ($fehler || $antwort === false) ? null : $antwort;
        }

        $kontext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n"
                    . "User-Agent: BdP-Ortsgruppe Website (Kalender)\r\n",
                'timeout' => self::ZEITLIMIT,
                'ignore_errors' => true,
            ],
        ]);

        $antwort = @file_get_contents($adresse, false, $kontext);

        if (isset($http_response_header[0])
            && preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $treffer)) {
            $status = (int) $treffer[1];
        }

        return $antwort === false ? null : $antwort;
    }

    /* ==============================================================
       Datenbank
       ============================================================== */

    /**
     * Schreibt die Zeiträume. Was aus derselben Quelle und demselben
     * Land stammt und sich überschneidet, wird ersetzt — sonst
     * sammelten sich bei einer Korrektur seitens des Ministeriums zwei
     * Fassungen desselben Zeitraums an.
     *
     * Von Hand eingetragene Zeiträume bleiben unangetastet.
     */
    protected static function eintragen(array $zeitraeume, $land)
    {
        $db = self::db();
        $geschrieben = 0;

        foreach ($zeitraeume as $z) {
            $vorhanden = $db->fetchOne(
                'SELECT freiID FROM BdPKalenderfrei
                  WHERE titel = ? AND beginn = ? AND ende = ?',
                [$z['titel'], $z['von'], $z['bis']]
            );

            $satz = [
                'titel' => $z['titel'],
                'beginn' => $z['von'],
                'ende' => $z['bis'],
                'art' => $z['art'],
                'quelle' => 'api',
                'land' => $land,
            ];

            if ($vorhanden) {
                $db->update('BdPKalenderfrei', $satz, ['freiID' => (int) $vorhanden]);
            } else {
                $db->insert('BdPKalenderfrei', $satz);
            }

            ++$geschrieben;
        }

        return $geschrieben;
    }

    /**
     * Wirft alles weg, was aus der Schnittstelle stammt. Von Hand
     * Eingetragenes bleibt. Für einen Wechsel des Bundeslandes.
     */
    public static function apiDatenLeeren($land = null)
    {
        $db = self::db();

        if ($land === null) {
            $db->executeStatement("DELETE FROM BdPKalenderfrei WHERE quelle = 'api'");
            $db->executeStatement('DELETE FROM BdPFerienabruf');

            return;
        }

        $db->executeStatement(
            "DELETE FROM BdPKalenderfrei WHERE quelle = 'api' AND land = ?",
            [$land]
        );
        $db->executeStatement('DELETE FROM BdPFerienabruf WHERE land = ?', [$land]);
    }

    /* ==============================================================
       Abrufprotokoll
       ============================================================== */

    protected static function istFrisch($land, $jahr)
    {
        $zeile = self::db()->fetchAssociative(
            'SELECT geholt, anzahl, fehler FROM BdPFerienabruf WHERE land = ? AND jahr = ?',
            [$land, (int) $jahr]
        );

        if (!$zeile) {
            return false;
        }

        // Ein Fehlschlag darf gleich beim nächsten Mal neu versucht
        // werden — sonst hinge eine kurze Störung tagelang nach.
        if ($zeile['fehler'] !== '') {
            return (time() - strtotime($zeile['geholt'])) < 3600;
        }

        $tage = (int) $zeile['anzahl'] === 0 ? self::FRISCH_TAGE_LEER : self::FRISCH_TAGE;

        return (time() - strtotime($zeile['geholt'])) < $tage * 86400;
    }

    protected static function abrufMerken($land, $jahr, $anzahl, $fehler)
    {
        $db = self::db();
        $jahr = (int) $jahr;

        $satz = [
            'geholt' => date('Y-m-d H:i:s'),
            'anzahl' => (int) $anzahl,
            'fehler' => mb_substr((string) $fehler, 0, 255),
        ];

        $vorhanden = $db->fetchOne(
            'SELECT abrufID FROM BdPFerienabruf WHERE land = ? AND jahr = ?',
            [$land, $jahr]
        );

        if ($vorhanden) {
            $db->update('BdPFerienabruf', $satz, ['abrufID' => (int) $vorhanden]);
        } else {
            $db->insert('BdPFerienabruf', $satz + ['land' => $land, 'jahr' => $jahr]);
        }
    }

    /**
     * Das Protokoll für die Anzeige im Dashboard.
     */
    public static function protokoll()
    {
        return self::db()->fetchAllAssociative(
            'SELECT * FROM BdPFerienabruf ORDER BY land ASC, jahr ASC'
        );
    }
}
