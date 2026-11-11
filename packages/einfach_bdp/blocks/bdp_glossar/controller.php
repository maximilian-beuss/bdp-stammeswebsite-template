<?php

namespace Concrete\Package\EinfachBdp\Block\BdPGlossar;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use EinfachBdP\Fliesstext;
use EinfachBdP\Werkzeug;

/**
 * Das Pfadi-Alphabet: Begriffe mit Erklärung.
 *
 * Vorher war das ein Inhaltsblock mit 28.000 Zeichen Markup — sechzig
 * Einträge, dazwischen von Hand gesetzte Buchstabenüberschriften, eine
 * Buchstabenleiste mit sechsundzwanzig Links und ein Suchfeld. Einen
 * Begriff einzufügen hieß: die richtige Stelle im Quelltext finden,
 * die Reihenfolge selbst beachten, und bei einem neuen Anfangsbuchstaben
 * zusätzlich Überschrift und Leiste anpassen.
 *
 * Jetzt trägt man Begriff und Erklärung ein. Alles andere ergibt sich:
 * Sortierung, Gruppierung nach Anfangsbuchstaben, die Leiste mit genau
 * den Buchstaben, die es gibt, und das Suchfeld.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPGlossar';
    protected $btInterfaceWidth = 760;
    protected $btInterfaceHeight = 640;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Glossar');
    }

    public function getBlockTypeDescription()
    {
        return t('Begriffe mit Erklärung. Sortierung, Buchstabengruppen und Suchfeld entstehen von allein.');
    }

    public function add()
    {
        $this->set('zeigeSuche', 1);
        $this->set('zeigeAlphabet', 1);
        $this->set('eintragListe', []);
    }

    public function edit()
    {
        $this->set('eintragListe', Werkzeug::liste($this->eintraege));
    }

    public function view()
    {
        $eintraege = [];

        foreach (Werkzeug::liste($this->eintraege) as $e) {
            $begriff = trim(Werkzeug::feld($e, 'begriff'));
            if ($begriff === '') {
                continue;
            }

            $eintraege[] = [
                'begriff' => $begriff,
                'erklaerung' => Fliesstext::zuHtml(Werkzeug::feld($e, 'erklaerung')),
            ];
        }

        /* Sortieren mit Rücksicht auf Umlaute: Ohne Collator landet
           „Übernachtung“ hinter „Zelt“, weil das Ü einen höheren
           Zeichenwert hat. Gibt es die intl-Erweiterung nicht, tut es
           ein Vergleich über die entumlautete Fassung. */
        usort($eintraege, function ($a, $b) {
            if (class_exists('Collator')) {
                $sortierer = new \Collator('de_DE');

                return $sortierer->compare($a['begriff'], $b['begriff']);
            }

            return strcasecmp(self::entumlauten($a['begriff']), self::entumlauten($b['begriff']));
        });

        /* Nach Anfangsbuchstaben gruppieren. Umlaute zählen zum
           Grundbuchstaben — ein eigener Abschnitt „Ü“ mit einem
           einzigen Eintrag hilft niemandem beim Suchen. */
        $gruppen = [];

        foreach ($eintraege as $e) {
            $buchstabe = mb_strtoupper(mb_substr(self::entumlauten($e['begriff']), 0, 1));

            if (!preg_match('/^[A-Z]$/', $buchstabe)) {
                $buchstabe = '#';
            }

            $gruppen[$buchstabe][] = $e;
        }

        $this->set('gruppen', $gruppen);
        $this->set('anzahl', count($eintraege));
        $this->set('alphabet', range('A', 'Z'));
    }

    protected static function entumlauten($text)
    {
        return strtr($text, [
            'Ä' => 'A', 'Ö' => 'O', 'Ü' => 'U',
            'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss',
        ]);
    }

    public function getSearchableContent()
    {
        $teile = [];
        foreach (Werkzeug::liste($this->eintraege) as $e) {
            $teile[] = Werkzeug::feld($e, 'begriff') . ' ' . Werkzeug::feld($e, 'erklaerung');
        }

        return implode(' ', $teile);
    }

    public function save($daten)
    {
        parent::save([
            'eintraege' => Werkzeug::listeSpeichern($daten['eintraege'] ?? [], ['begriff']),
            'zeigeSuche' => empty($daten['zeigeSuche']) ? 0 : 1,
            'zeigeAlphabet' => empty($daten['zeigeAlphabet']) ? 0 : 1,
        ]);
    }
}
