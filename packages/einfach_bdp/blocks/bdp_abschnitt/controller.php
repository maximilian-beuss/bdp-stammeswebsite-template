<?php

namespace Concrete\Package\EinfachBdp\Block\BdPAbschnitt;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\Page\Page;
use EinfachBdP\Platzhalter;
use EinfachBdP\Werkzeug;

/**
 * Ein Abschnitt: Überschrift plus Text.
 *
 * Der eigentliche Sinn ist die Zerlegung. Vorher bestand eine Seite
 * aus einem einzigen Inhaltsblock mit dreißig Absätzen; wer den
 * dritten ändern wollte, bekam den ganzen Berg in den Editor und
 * konnte nebenbei alles andere zerschießen. Ein Abschnitt je Thema
 * lässt sich einzeln bearbeiten, einzeln verschieben und einzeln
 * wieder löschen.
 *
 * Die Überschrift ist ein eigenes Feld und kein Teil des Fließtexts,
 * damit sie immer die richtige Auszeichnung bekommt und damit man an
 * der Blockliste im Bearbeitungsmodus sieht, worum es in dem Block
 * geht.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPAbschnitt';
    protected $btInterfaceWidth = 700;
    protected $btInterfaceHeight = 560;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;
    protected $btDefaultSet = 'einfach_bdp';

    /**
     * Die Ikons des BdP-Zeichensatzes, gruppiert.
     *
     * Nur die gegenständlichen — die Striche, Wellen und Kringel aus
     * demselben Satz sind Zierlinien und neben einer Überschrift, die
     * ihren gestrichelten Strich schon hat, eine Dopplung.
     *
     * Die Dateien liegen einfarbig in blau, gelb und rot vor. Benutzt
     * wird nur der blaue Satz, und zwar als Maske: Die Farbe kommt
     * dann aus dem CSS und passt damit immer zur Fläche, auf der das
     * Ikon steht. Mit fertig eingefärbten Dateien bekäme man auf der
     * blauen Fläche ein blaues Ikon — unsichtbar.
     */
    const IKONS = [
        'unterwegs' => [
            'kompass' => 'Kompass',
            'karte' => 'Karte',
            'rucksack' => 'Rucksack',
            'angekommen' => 'Angekommen',
            'pfeile' => 'Pfeile',
        ],
        'lager' => [
            'kohte' => 'Kohte',
            'feuer' => 'Feuer',
            'feuerkreis' => 'Feuerkreis',
            'kessel' => 'Kessel',
            'lampe' => 'Lampe',
            'flasche' => 'Flasche',
            'koschi' => 'Kochgeschirr',
        ],
        'stamm' => [
            'halstuch' => 'Halstuch',
            'girlande' => 'Girlande',
            'affe' => 'Affe (Rucksackrolle)',
            'blatt' => 'Blatt',
            'wald' => 'Wald',
        ],
        'zeichen' => [
            'ausrufezeichen' => 'Ausrufezeichen',
            'instagram' => 'Instagram',
        ],
    ];

    /** Alle Ikonkürzel in einer flachen Liste. */
    public static function ikonListe()
    {
        $alle = [];
        foreach (self::IKONS as $gruppe) {
            foreach ($gruppe as $kuerzel => $name) {
                $alle[$kuerzel] = $name;
            }
        }

        return $alle;
    }

    public function getBlockTypeName()
    {
        return t('Abschnitt');
    }

    public function getBlockTypeDescription()
    {
        return t('Überschrift und Text als ein Abschnitt, wahlweise auf farbiger Fläche.');
    }

    public function add()
    {
        $this->set('ueberschrift', '');
        $this->set('ebene', 'h2');
        $this->set('text', '');
        $this->set('flaeche', 'weiss');
        $this->set('ikon', '');
        $this->set('ankerName', '');
        $this->set('bildfID', 0);
        $this->set('bildText', '');
        $this->set('bildStil', 'kein');
        $this->maskeVorbereiten('');
    }

    public function edit()
    {
        $this->maskeVorbereiten(LinkAbstractor::translateFromEditMode($this->text));
    }

    protected function maskeVorbereiten($text)
    {
        $this->set('textFuerEditor', $text);
        $this->set('bildDatei', Werkzeug::datei($this->bildfID));
        $this->set('ikonGruppen', self::IKONS);
    }

    public function view()
    {
        $seite = Page::getCurrentPage();

        $this->set('ueberschriftText', Platzhalter::ersetze($this->ueberschrift, $seite));
        $this->set('textHtml', LinkAbstractor::translateFrom($this->text));

        $datei = Werkzeug::datei($this->bildfID);

        $this->set('bildUrl', $datei ? (string) $datei->getURL() : '');
        $this->set('bildBreite', $datei ? (int) $datei->getAttribute('width') : 0);
        $this->set('bildHoehe', $datei ? (int) $datei->getAttribute('height') : 0);

        /* Der blaue Satz dient als Form, nicht als Farbe — das CSS
           legt ihn als Maske an und färbt ihn passend zur Fläche. */
        $ikon = (string) $this->ikon;
        $this->set('ikonUrl', isset(self::ikonListe()[$ikon])
            ? DIR_REL . '/application/themes/einfach_bdp/images/ikons/blau/' . $ikon . '.svg'
            : '');
    }

    /**
     * Für die Übersicht im Bearbeitungsmodus. Ohne das heißen alle
     * Blöcke gleich und man sucht sich dumm.
     */
    public function getSearchableContent()
    {
        return $this->ueberschrift . ' ' . strip_tags((string) $this->text);
    }

    public function save($daten)
    {
        $anker = strtolower(trim((string) ($daten['ankerName'] ?? '')));
        $anker = preg_replace('/[^a-z0-9\-]/', '-', $anker);
        $anker = trim(preg_replace('/-+/', '-', $anker), '-');

        parent::save([
            'ueberschrift' => trim((string) ($daten['ueberschrift'] ?? '')),
            'ebene' => in_array($daten['ebene'] ?? '', ['h2', 'h3', 'keine'], true) ? $daten['ebene'] : 'h2',
            'text' => LinkAbstractor::translateTo((string) ($daten['text'] ?? '')),
            'flaeche' => in_array($daten['flaeche'] ?? '', ['weiss', 'gedaempft', 'sand', 'gelb', 'blau'], true)
                ? $daten['flaeche'] : 'weiss',
            'ikon' => isset(self::ikonListe()[$daten['ikon'] ?? '']) ? $daten['ikon'] : '',
            'ankerName' => mb_substr($anker, 0, 80),
            'bildfID' => (int) ($daten['bildfID'] ?? 0),
            'bildText' => trim((string) ($daten['bildText'] ?? '')),
            'bildStil' => in_array($daten['bildStil'] ?? '', ['kein', 'links', 'rechts', 'oben', 'unten'], true)
                ? $daten['bildStil'] : 'kein',
        ]);
    }
}
