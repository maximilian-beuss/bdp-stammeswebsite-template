<?php

namespace EinfachBdP;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Kleinkram, den mehrere Blöcke brauchen.
 */
class Werkzeug
{
    /**
     * Liest eine als JSON abgelegte Liste aus einem Blockfeld.
     *
     * Gibt immer ein Array zurück, auch wenn das Feld leer, kaputt oder
     * gar kein Array ist. Ein Blockview darf an einem verunglückten
     * JSON nicht sterben — im schlimmsten Fall zeigt er nichts.
     */
    public static function liste($json)
    {
        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $daten = json_decode($json, true);

        return is_array($daten) ? array_values($daten) : [];
    }

    /**
     * Schreibt eine Liste zurück. Leere Zeilen fliegen raus, damit ein
     * versehentlich hinzugefügtes und nicht ausgefülltes Feld nicht als
     * Geisterelement in der Ausgabe landet.
     */
    public static function listeSpeichern($werte, array $pflichtfelder = [])
    {
        if (is_string($werte)) {
            $werte = json_decode($werte, true);
        }
        if (!is_array($werte)) {
            return '[]';
        }

        $sauber = [];
        foreach ($werte as $zeile) {
            if (!is_array($zeile)) {
                continue;
            }

            $leer = true;
            foreach ($pflichtfelder ?: array_keys($zeile) as $feld) {
                if (isset($zeile[$feld]) && trim((string) $zeile[$feld]) !== '') {
                    $leer = false;
                    break;
                }
            }
            if (!$leer) {
                $sauber[] = $zeile;
            }
        }

        return json_encode($sauber, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Wert aus einer Listenzeile holen, ohne bei fehlendem Schlüssel zu
     * meckern.
     */
    public static function feld(array $zeile, $name, $ersatz = '')
    {
        return isset($zeile[$name]) ? (string) $zeile[$name] : $ersatz;
    }

    /**
     * Löst ein Linkziel aus einem Blockfeld auf.
     *
     * Warum das nötig ist: Ein Feld wie „Knopf: Ziel“ enthält oft einen
     * seiteninternen Verweis. Ihn als fertigen Pfad zu speichern geht
     * schief, sobald die Installation in einem Unterverzeichnis liegt
     * oder die Seite verschoben wird. Concrete kennt dafür Platzhalter:
     *
     *   {CCM:BASE_URL}/pfad   Basispfad der Installation davor
     *   {CCM:CID_42}          Link auf die Seite mit der ID 42
     *   {CCM:FID_7}           Link auf eine Datei
     *
     * LinkAbstractor löst sie beim Ausliefern auf. Steht kein
     * Platzhalter drin, wird nichts angefasst — externe Adressen,
     * mailto: und tel: bleiben unverändert.
     */
    public static function link($ziel)
    {
        $ziel = (string) $ziel;

        if ($ziel === '' || strpos($ziel, '{CCM:') === false) {
            return $ziel;
        }

        return \Concrete\Core\Editor\LinkAbstractor::translateFrom($ziel);
    }

    /**
     * Wandelt eine Datei-ID in eine URL. Gibt '' zurück, wenn die Datei
     * gelöscht wurde — das passiert im Dateimanager schneller, als man
     * denkt, und darf keine kaputten <img> hinterlassen.
     */
    public static function bildUrl($fID)
    {
        $fID = (int) $fID;
        if ($fID < 1) {
            return '';
        }

        $datei = \Concrete\Core\File\File::getByID($fID);
        if (!is_object($datei)) {
            return '';
        }

        $version = $datei->getApprovedVersion();

        return is_object($version) ? (string) $version->getURL() : '';
    }

    /**
     * Datei-Objekt zur ID, oder null. Für Blöcke, die neben der URL auch
     * Maße oder den Titel brauchen.
     */
    public static function datei($fID)
    {
        $fID = (int) $fID;
        if ($fID < 1) {
            return null;
        }

        $datei = \Concrete\Core\File\File::getByID($fID);
        if (!is_object($datei)) {
            return null;
        }

        $version = $datei->getApprovedVersion();

        return is_object($version) ? $version : null;
    }

    /**
     * Formatiert ein Datum auf Deutsch („15. Oktober 2026").
     *
     * Akzeptiert ein DateTimeInterface oder einen Datums-String.
     * Gibt ['text' => '15. Oktober 2026', 'iso' => '2026-10-15', 'ts' => 1792015200]
     * zurück, oder leere Werte, wenn kein gültiges Datum vorliegt.
     */
    public static function datumDeutsch($wert)
    {
        $ts = 0;

        if ($wert instanceof \DateTimeInterface) {
            $ts = $wert->getTimestamp();
        } elseif (is_string($wert) && trim($wert) !== '') {
            $parsed = strtotime($wert);
            if ($parsed !== false) {
                $ts = $parsed;
            }
        }

        if ($ts <= 0) {
            return ['text' => '', 'iso' => '', 'ts' => 0];
        }

        $monate = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ];

        $tag = (int) date('j', $ts);
        $monat = $monate[(int) date('n', $ts)] ?? '';
        $jahr = date('Y', $ts);

        return [
            'text' => $tag . '. ' . $monat . ' ' . $jahr,
            'iso' => date('Y-m-d', $ts),
            'ts' => $ts,
        ];
    }

    /**
     * Liest die Berichts-/Kacheldaten einer Seite aus.
     *
     * Unterstützt sowohl die Berichts-Attribute (berichtsdatum,
     * kurzbeschreibung, Vorschaubild) als auch die normalen
     * Seiten-Attribute (seiten_bild, cDescription, gruppe_stufe).
     */
    public static function berichtDaten(\Concrete\Core\Page\Page $seite)
    {
        $bild = $seite->getAttribute('Vorschaubild');
        if (!is_object($bild)) {
            $bild = $seite->getAttribute('vorschaubild');
        }
        if (!is_object($bild)) {
            $bild = $seite->getAttribute('seiten_bild');
        }
        if (!is_object($bild)) {
            $bild = $seite->getAttribute('thumbnail');
        }

        $bildUrl = '';
        if (is_object($bild)) {
            if (method_exists($bild, 'getURL')) {
                $bildUrl = (string) $bild->getURL();
            } elseif (method_exists($bild, 'getApprovedVersion')) {
                $v = $bild->getApprovedVersion();
                if (is_object($v)) {
                    $bildUrl = (string) $v->getURL();
                }
            }
        }

        $text = trim((string) $seite->getAttribute('kurzbeschreibung'));
        if ($text === '') {
            $text = trim((string) $seite->getCollectionDescription());
        }

        $rohDatum = $seite->getAttribute('berichtsdatum');
        if (!$rohDatum && $seite->getPageTypeHandle() === 'bericht') {
            $rohDatum = $seite->getCollectionDatePublic();
        }
        $datum = self::datumDeutsch($rohDatum);

        $etikett = trim((string) $seite->getAttribute('gruppe_stufe'));
        if ($etikett === '' && $datum['text'] !== '') {
            $etikett = $datum['text'];
        }

        return [
            'titel' => (string) $seite->getCollectionName(),
            'text' => $text,
            'ziel' => (string) $seite->getCollectionLink(),
            'bild' => $bildUrl,
            'etikett' => $etikett,
            'datum' => $datum['text'],
            'datumIso' => $datum['iso'],
            'zeitstempel' => $datum['ts'] > 0 ? $datum['ts'] : (int) strtotime((string) $seite->getCollectionDatePublic()),
        ];
    }

    /**
     * Holt alle veröffentlichten Berichte (Seitentyp „bericht"),
     * absteigend nach Berichtsdatum sortiert.
     *
     * @param int $limit 0 = alle
     * @return array Liste aus berichtDaten()-Arrays
     */
    public static function berichte($limit = 0)
    {
        $liste = new \Concrete\Core\Page\PageList();
        $liste->filterByPageTypeHandle('bericht');
        $liste->filterByExcludePageList(false);

        $eintraege = [];

        foreach ($liste->getResults() as $seite) {
            if (!is_object($seite) || $seite->isError() || $seite->isSystemPage()) {
                continue;
            }
            $eintraege[] = self::berichtDaten($seite);
        }

        usort($eintraege, static function (array $a, array $b) {
            return $b['zeitstempel'] <=> $a['zeitstempel'];
        });

        if ($limit > 0) {
            return array_slice($eintraege, 0, $limit);
        }

        return $eintraege;
    }
}
