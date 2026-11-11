<?php

namespace EinfachBdP\Kalender;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;

/**
 * Lesen und Schreiben der Termine.
 *
 * Bewusst schlichtes SQL statt Doctrine-Entitäten: es sind zwei
 * Tabellen mit je einer Handvoll Spalten, und die Abfragen, die der
 * Kalender braucht, sind alle von der Form „alles, was den Zeitraum
 * X berührt“.
 *
 * Der Überschneidungstest lautet immer beginn <= bis AND ende >= von.
 * Damit erwischt er auch Termine, die vor dem Zeitraum anfangen und
 * nach ihm aufhören — ein Sommerlager, das über den Monatswechsel
 * läuft, muss in beiden Monaten auftauchen.
 */
class Speicher
{
    protected static function db()
    {
        return Application::getFacadeApplication()->make('database')->connection();
    }

    /**
     * Termine, die den Zeitraum berühren.
     *
     * @param string $von    Y-m-d
     * @param string $bis    Y-m-d
     * @param array  $stufen leer = alle
     */
    public static function zeitraum($von, $bis, array $stufen = [], $mitEntwuerfen = false)
    {
        /* Entwürfe bleiben draußen. Sie liegen im Jahresplan, sind aber
           noch nicht beschlossen — im öffentlichen Kalender hätten sie
           nichts verloren, und über das iCal-Abo landeten sie in den
           Kalendern der Leute. */
        $sql = 'SELECT * FROM BdPTermine WHERE beginn <= ? AND ende >= ?'
            . ($mitEntwuerfen ? '' : ' AND entwurf = 0');
        $werte = [$bis . ' 23:59:59', $von . ' 00:00:00'];

        $stufen = array_values(array_filter($stufen, ['\EinfachBdP\Kalender\Stufen', 'gibt']));
        if (count($stufen) > 0) {
            $sql .= ' AND stufe IN (' . implode(',', array_fill(0, count($stufen), '?')) . ')';
            $werte = array_merge($werte, $stufen);
        }

        $sql .= ' ORDER BY beginn ASC, titel ASC';

        return self::db()->fetchAllAssociative($sql, $werte);
    }

    /**
     * Alle Termine ab einem Stichtag. Für die iCal-Datei und die
     * Terminliste.
     */
    public static function ab($stichtag, $grenze = 500)
    {
        return self::db()->fetchAllAssociative(
            'SELECT * FROM BdPTermine WHERE ende >= ? AND entwurf = 0
              ORDER BY beginn ASC LIMIT ' . (int) $grenze,
            [$stichtag . ' 00:00:00']
        );
    }

    public static function alle($mitEntwuerfen = true)
    {
        return self::db()->fetchAllAssociative(
            'SELECT * FROM BdPTermine'
            . ($mitEntwuerfen ? '' : ' WHERE entwurf = 0')
            . ' ORDER BY beginn ASC'
        );
    }

    /**
     * Alle Termine eines Jahres, Entwürfe eingeschlossen. Für den
     * Jahresplan.
     */
    public static function jahr($jahr)
    {
        $jahr = (int) $jahr;

        return self::db()->fetchAllAssociative(
            'SELECT * FROM BdPTermine
              WHERE beginn <= ? AND ende >= ?
              ORDER BY beginn ASC, titel ASC',
            [$jahr . '-12-31 23:59:59', $jahr . '-01-01 00:00:00']
        );
    }

    /**
     * Verschiebt einen Termin, ohne seine Dauer zu ändern.
     *
     * Die Dauer wird aus dem alten Zeitraum übernommen und auf den
     * neuen Beginn addiert. Wer im Jahresplan ein Lager verschiebt,
     * will es verschieben und nicht verkürzen.
     */
    public static function verschieben($terminID, $neuerTag)
    {
        $termin = self::einer($terminID);

        if (!$termin || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $neuerTag)) {
            return false;
        }

        $alterBeginn = strtotime($termin['beginn']);
        $altesEnde = strtotime($termin['ende']);
        $dauer = max(0, (int) floor(($altesEnde - $alterBeginn) / 86400));

        $neuesEnde = date('Y-m-d', strtotime('+' . $dauer . ' days', strtotime($neuerTag)));

        self::db()->update('BdPTermine', [
            'beginn' => $neuerTag . ' ' . substr($termin['beginn'], 11),
            'ende' => $neuesEnde . ' ' . substr($termin['ende'], 11),
            'geaendert' => date('Y-m-d H:i:s'),
        ], ['terminID' => (int) $terminID]);

        return true;
    }

    public static function entwurfSetzen($terminID, $entwurf)
    {
        self::db()->update('BdPTermine', [
            'entwurf' => $entwurf ? 1 : 0,
            'geaendert' => date('Y-m-d H:i:s'),
        ], ['terminID' => (int) $terminID]);
    }

    public static function einer($terminID)
    {
        $zeile = self::db()->fetchAssociative(
            'SELECT * FROM BdPTermine WHERE terminID = ?',
            [(int) $terminID]
        );

        return $zeile ?: null;
    }

    /**
     * Anlegen oder ändern. Gibt die ID zurück.
     */
    public static function sichern(array $daten, $terminID = 0)
    {
        $db = self::db();

        $satz = [
            'titel' => mb_substr(trim((string) ($daten['titel'] ?? '')), 0, 255),
            'beginn' => (string) ($daten['beginn'] ?? ''),
            'ende' => (string) ($daten['ende'] ?? ''),
            'ganztaegig' => empty($daten['ganztaegig']) ? 0 : 1,
            'stufe' => Stufen::gibt($daten['stufe'] ?? '') ? $daten['stufe'] : Stufen::ALLE,
            'ort' => mb_substr(trim((string) ($daten['ort'] ?? '')), 0, 255),
            'beschreibung' => (string) ($daten['beschreibung'] ?? ''),
            'link' => mb_substr(trim((string) ($daten['link'] ?? '')), 0, 512),
            'geaendert' => date('Y-m-d H:i:s'),
            'entwurf' => empty($daten['entwurf']) ? 0 : 1,
        ];

        // Ein Termin ohne Ende endet an seinem Anfang.
        if ($satz['ende'] === '' || $satz['ende'] < $satz['beginn']) {
            $satz['ende'] = $satz['beginn'];
        }

        $terminID = (int) $terminID;
        if ($terminID > 0) {
            $db->update('BdPTermine', $satz, ['terminID' => $terminID]);

            return $terminID;
        }

        // Die UID bleibt über Änderungen hinweg stabil, sonst legen
        // abonnierte Kalender bei jeder Korrektur einen neuen Termin an.
        $satz['uid'] = bin2hex(random_bytes(12));
        $db->insert('BdPTermine', $satz);

        return (int) $db->lastInsertId();
    }

    public static function loeschen($terminID)
    {
        self::db()->delete('BdPTermine', ['terminID' => (int) $terminID]);
    }

    /* ---------------------------------------------------------------
       Ideenspeicher der Jahresplanung
       --------------------------------------------------------------- */

    public static function ideen()
    {
        return self::db()->fetchAllAssociative(
            'SELECT * FROM BdPPlanungsideen ORDER BY sortierung ASC, ideeID ASC'
        );
    }

    public static function idee($ideeID)
    {
        $zeile = self::db()->fetchAssociative(
            'SELECT * FROM BdPPlanungsideen WHERE ideeID = ?',
            [(int) $ideeID]
        );

        return $zeile ?: null;
    }

    public static function ideeSichern(array $daten, $ideeID = 0)
    {
        $db = self::db();

        $satz = [
            'titel' => mb_substr(trim((string) ($daten['titel'] ?? '')), 0, 255),
            'tage' => max(1, min(365, (int) ($daten['tage'] ?? 1))),
            'stufe' => Stufen::gibt($daten['stufe'] ?? '') ? $daten['stufe'] : Stufen::ALLE,
            'notiz' => (string) ($daten['notiz'] ?? ''),
        ];

        $ideeID = (int) $ideeID;

        if ($ideeID > 0) {
            $db->update('BdPPlanungsideen', $satz, ['ideeID' => $ideeID]);

            return $ideeID;
        }

        $satz['angelegt'] = date('Y-m-d H:i:s');
        $satz['sortierung'] = (int) $db->fetchOne(
            'SELECT COALESCE(MAX(sortierung), 0) + 1 FROM BdPPlanungsideen'
        );

        $db->insert('BdPPlanungsideen', $satz);

        return (int) $db->lastInsertId();
    }

    public static function ideeLoeschen($ideeID)
    {
        self::db()->delete('BdPPlanungsideen', ['ideeID' => (int) $ideeID]);
    }

    /**
     * Aus einer Idee wird ein Entwurf im Jahr.
     *
     * @return int|false neue Termin-ID
     */
    public static function ideeAblegen($ideeID, $tag)
    {
        $idee = self::idee($ideeID);

        if (!$idee || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tag)) {
            return false;
        }

        $ende = date('Y-m-d', strtotime('+' . ((int) $idee['tage'] - 1) . ' days', strtotime($tag)));

        $terminID = self::sichern([
            'titel' => $idee['titel'],
            'beginn' => $tag . ' 00:00:00',
            'ende' => $ende . ' 23:59:00',
            'ganztaegig' => 1,
            'stufe' => $idee['stufe'],
            'ort' => '',
            'beschreibung' => $idee['notiz'],
            'link' => '',
            'entwurf' => 1,
        ]);

        self::ideeLoeschen($ideeID);

        return $terminID;
    }

    /**
     * Und zurück: aus einem Termin wird wieder eine Idee.
     */
    public static function zurueckInDenSpeicher($terminID)
    {
        $termin = self::einer($terminID);

        if (!$termin) {
            return false;
        }

        $tage = max(1, (int) floor(
            (strtotime($termin['ende']) - strtotime($termin['beginn'])) / 86400
        ) + 1);

        self::ideeSichern([
            'titel' => $termin['titel'],
            'tage' => $tage,
            'stufe' => $termin['stufe'],
            'notiz' => $termin['beschreibung'],
        ]);

        self::loeschen($terminID);

        return true;
    }

    /* ---------------------------------------------------------------
       Freie Tage: Feiertage und Schulferien
       --------------------------------------------------------------- */

    public static function freieTage($von, $bis)
    {
        return self::db()->fetchAllAssociative(
            'SELECT * FROM BdPKalenderfrei WHERE beginn <= ? AND ende >= ? ORDER BY beginn ASC',
            [$bis, $von]
        );
    }

    /**
     * @param string $quelle api | gerechnet | hand
     *               Wichtig für das Aufräumen: Sobald die Schnittstelle
     *               ein Jahr geliefert hat, fliegen die gerechneten
     *               Feiertage dieses Jahres raus, damit nicht
     *               „Neujahr“ und „Neujahrstag“ nebeneinander stehen.
     *               Von Hand Eingetragenes bleibt immer.
     */
    public static function freiSetzen($titel, $von, $bis, $art, $quelle = 'hand')
    {
        $db = self::db();

        $vorhanden = $db->fetchOne(
            'SELECT freiID FROM BdPKalenderfrei WHERE titel = ? AND beginn = ?',
            [$titel, $von]
        );
        if ($vorhanden) {
            return (int) $vorhanden;
        }

        $db->insert('BdPKalenderfrei', [
            'titel' => $titel,
            'beginn' => $von,
            'ende' => $bis,
            'art' => $art === 'ferien' ? 'ferien' : 'feiertag',
            'quelle' => in_array($quelle, ['api', 'gerechnet', 'hand'], true) ? $quelle : 'hand',
            'land' => '',
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * Entfernt die gerechneten Feiertage eines Jahres. Wird gerufen,
     * nachdem die Schnittstelle dasselbe Jahr geliefert hat.
     */
    public static function gerechneteLoeschen($jahr)
    {
        return (int) self::db()->executeStatement(
            "DELETE FROM BdPKalenderfrei WHERE quelle = 'gerechnet' AND YEAR(beginn) = ?",
            [(int) $jahr]
        );
    }

    public static function freiLeeren($jahr)
    {
        self::db()->executeStatement(
            'DELETE FROM BdPKalenderfrei WHERE YEAR(beginn) = ?',
            [(int) $jahr]
        );
    }
}
