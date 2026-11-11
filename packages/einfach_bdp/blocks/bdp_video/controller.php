<?php

namespace Concrete\Package\EinfachBdp\Block\BdPVideo;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use EinfachBdP\Werkzeug;

/**
 * Ein Video mit Untertiteln.
 *
 * Warum ein eigener Block und nicht der HTML-Block mit einem <video>
 * darin: Untertitel sind der halbe Sinn der Sache. Wer sie von Hand
 * einträgt, muss den richtigen <track>-Pfad kennen, die Sprachkürzel
 * treffen und daran denken, welche Spur `default` bekommt. Genau das
 * geht beim Redigieren als Erstes verloren.
 *
 * Abgespielt wird mit Plyr. Der eingebaute Browserspieler sieht in
 * jedem Browser anders aus und lässt sich nicht einfärben; Plyr bringt
 * eine Bedienleiste mit, die überall gleich aussieht, sich über
 * CSS-Variablen an das Corporate Design anpassen lässt und Tastatur
 * und Screenreader ordentlich bedient.
 *
 * Plyr ist ein Aufsatz auf ein ganz normales <video controls>: Ohne
 * JavaScript bleibt der Spieler des Browsers stehen, mit allem, was
 * dazugehört — abspielen, Ton, Vollbild, Untertitel ✅ WCAG 1.2.2
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPVideo';
    protected $btInterfaceWidth = 740;
    protected $btInterfaceHeight = 620;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btDefaultSet = 'einfach_bdp';

    /**
     * Sprachen, die zur Auswahl stehen. Mehr braucht der Stamm nicht,
     * und eine kurze Liste trifft man schneller als ein Freitextfeld,
     * in dem „deutsch“ statt „de“ landet und die Spur unbenannt bleibt.
     */
    const SPRACHEN = [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'pl' => 'Polski',
        'uk' => 'Українська',
    ];

    /**
     * Die Farben des Corporate Designs, nach Gruppen sortiert.
     *
     * Als Hexwerte und nicht als var(--bdp-…): Die Werte werden hier
     * gebraucht, um die passende Symbolfarbe auszurechnen, und dafür
     * müsste man einen CSS-Verweis erst wieder auflösen.
     *
     * Die Gruppe „akzent“ trägt den Hinweis aus dem CD: Akzentfarben
     * sind dort für Ikons und Striche vorgesehen, nicht für Flächen.
     * Der Fortschrittsbalken ist ein Strich, der Abspielknopf eine
     * Fläche — deshalb steht die Warnung in der Maske und nicht als
     * Verbot im Code. Was das CD hergibt, entscheidet die Redaktion.
     */
    const PALETTE = [
        'primaer' => [
            'blau' => ['Pfadiblau', '#0E4278'],
            'gelb' => ['Wölflingsgelb', '#FFD208'],
            'rot' => ['R*R-Rot', '#971B2F'],
            'kluft' => ['Kluft (Blau dunkel)', '#0B2341'],
        ],
        'akzent' => [
            'waldgruen' => ['Waldgrün', '#006C55'],
            'lagerplatz' => ['Lagerplatzgrün', '#00B74F'],
            'glut' => ['Glut', '#E84E1B'],
            'lagerfeuer' => ['Lagerfeuer', '#EF7D00'],
            'morgenkreis' => ['Morgenkreis', '#EF859D'],
            'singekreis' => ['Singekreis', '#8E7FAE'],
        ],
        'neutral' => [
            'kohte' => ['Kohte (Schwarz)', '#222423'],
            'weiss' => ['Weiß', '#FFFFFF'],
        ],
    ];

    /**
     * Hexwert zu einem Kürzel. 'eigen' liefert den freien Wert, sofern
     * er wie eine Farbe aussieht.
     */
    public static function farbwert($kuerzel, $eigen, $ersatz)
    {
        if ($kuerzel === 'eigen') {
            $eigen = trim((string) $eigen);

            return preg_match('/^#[0-9a-fA-F]{6}$/', $eigen) ? strtoupper($eigen) : $ersatz;
        }

        foreach (self::PALETTE as $gruppe) {
            if (isset($gruppe[$kuerzel])) {
                return $gruppe[$kuerzel][1];
            }
        }

        return $ersatz;
    }

    /**
     * Weiß oder Kohte-Schwarz — je nachdem, was auf der gewählten
     * Farbe besser lesbar ist.
     *
     * Ohne das kann die Redaktion einen gelben Abspielknopf wählen und
     * bekommt einen weißen Pfeil darauf: 1,6:1, praktisch unsichtbar.
     * Die Rechnung ist die aus WCAG 2.1 (relative Leuchtdichte,
     * Kontrastverhältnis) — dieselbe, die ein Prüfwerkzeug anlegt
     * ✅ WCAG 1.4.11
     */
    public static function symbolfarbe($hex)
    {
        $hell = self::verhaeltnis($hex, '#FFFFFF');
        $dunkel = self::verhaeltnis($hex, '#222423');

        return $dunkel > $hell ? '#222423' : '#FFFFFF';
    }

    protected static function verhaeltnis($a, $b)
    {
        $la = self::leuchtdichte($a);
        $lb = self::leuchtdichte($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    protected static function leuchtdichte($hex)
    {
        $hex = ltrim((string) $hex, '#');
        $teile = [];
        foreach ([0, 2, 4] as $i) {
            $k = hexdec(substr($hex, $i, 2)) / 255;
            $teile[] = $k <= 0.03928 ? $k / 12.92 : pow(($k + 0.055) / 1.055, 2.4);
        }

        return 0.2126 * $teile[0] + 0.7152 * $teile[1] + 0.0722 * $teile[2];
    }

    public function getBlockTypeName()
    {
        return t('Video');
    }

    public function getBlockTypeDescription()
    {
        return t('Ein Video aus der Dateiverwaltung mit Standbild, Bildunterschrift und Untertiteln.');
    }

    public function add()
    {
        $this->set('videofID', 0);
        $this->set('posterfID', 0);
        $this->set('titel', '');
        $this->set('text', '');
        $this->set('breite', 'voll');
        $this->set('vorladen', 'metadata');
        $this->set('schleife', 0);
        $this->set('akzent', 'gelb');
        $this->set('knopf', 'blau');
        $this->set('akzentEigen', '');
        $this->set('knopfEigen', '');
        $this->set('spurenListe', []);
        $this->set('sprachen', self::SPRACHEN);
        $this->set('palette', self::PALETTE);
    }

    public function edit()
    {
        $this->set('spurenListe', Werkzeug::liste($this->spuren));
        $this->set('sprachen', self::SPRACHEN);
        $this->set('palette', self::PALETTE);
    }

    /**
     * Plyr wird nur dort geladen, wo auch ein Video steht. Die
     * Bibliothek wiegt gut 110 KB — die gehören nicht auf jede Seite
     * des Stammes, nur weil eine einzige ein Video hat.
     */
    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('css', 'einfach_bdp/plyr');
        $this->requireAsset('javascript', 'einfach_bdp/plyr');
        $this->requireAsset('javascript', 'einfach_bdp/video');
    }

    public function view()
    {
        $video = Werkzeug::datei($this->videofID);

        $this->set('videoUrl', $video ? (string) $video->getURL() : '');
        $this->set('videoTyp', $video ? (string) $video->getMimeType() : 'video/mp4');
        $this->set('posterUrl', Werkzeug::bildUrl($this->posterfID));

        $spuren = [];
        foreach (Werkzeug::liste($this->spuren) as $s) {
            $datei = Werkzeug::datei(Werkzeug::feld($s, 'dateifID', 0));
            if (!$datei) {
                continue;
            }

            $sprache = Werkzeug::feld($s, 'sprache', 'de');
            $beschriftung = trim(Werkzeug::feld($s, 'beschriftung'));

            $spuren[] = [
                'url' => (string) $datei->getURL(),
                'sprache' => $sprache,
                'beschriftung' => $beschriftung !== ''
                    ? $beschriftung
                    : (self::SPRACHEN[$sprache] ?? $sprache),
                'standard' => !empty($s['standard']),
            ];
        }

        /* Genau eine Spur darf `default` tragen. Sind versehentlich
           zwei angehakt, gewinnt die erste — sonst entscheidet der
           Browser, und zwar in jedem Browser anders. */
        $gesetzt = false;
        foreach ($spuren as $i => $s) {
            if ($s['standard'] && !$gesetzt) {
                $gesetzt = true;
            } elseif ($s['standard']) {
                $spuren[$i]['standard'] = false;
            }
        }

        $this->set('spurenListe', $spuren);

        /* Die Farben fertig ausgerechnet in die Ansicht geben. Die
           passende Symbolfarbe hier und nicht im CSS: color-mix und
           Konsorten können keine Kontrastschwelle, und ein fester
           Wert im Stylesheet wäre wieder ein Wert für alle Blöcke. */
        $akzent = self::farbwert($this->akzent, $this->akzentEigen, '#FFD208');
        $knopf = self::farbwert($this->knopf, $this->knopfEigen, '#0E4278');

        $this->set('farbAkzent', $akzent);
        $this->set('farbAkzentSymbol', self::symbolfarbe($akzent));
        $this->set('farbKnopf', $knopf);
        $this->set('farbKnopfSymbol', self::symbolfarbe($knopf));
    }

    public function getSearchableContent()
    {
        return $this->titel . ' ' . strip_tags((string) $this->text);
    }

    public function save($daten)
    {
        parent::save([
            'videofID' => (int) ($daten['videofID'] ?? 0),
            'posterfID' => (int) ($daten['posterfID'] ?? 0),
            'titel' => trim((string) ($daten['titel'] ?? '')),
            'text' => trim((string) ($daten['text'] ?? '')),
            'spuren' => Werkzeug::listeSpeichern($daten['spuren'] ?? [], ['dateifID']),
            'breite' => in_array($daten['breite'] ?? '', ['voll', 'schmal', 'breit'], true)
                ? $daten['breite'] : 'voll',
            'vorladen' => in_array($daten['vorladen'] ?? '', ['none', 'metadata', 'auto'], true)
                ? $daten['vorladen'] : 'metadata',
            'schleife' => empty($daten['schleife']) ? 0 : 1,
            'akzent' => self::kuerzel($daten['akzent'] ?? '', 'gelb'),
            'knopf' => self::kuerzel($daten['knopf'] ?? '', 'blau'),
            'akzentEigen' => self::hex($daten['akzentEigen'] ?? ''),
            'knopfEigen' => self::hex($daten['knopfEigen'] ?? ''),
        ]);
    }

    /**
     * Nur Kürzel, die es in der Palette gibt — oder 'eigen'.
     */
    protected static function kuerzel($wert, $ersatz)
    {
        $wert = (string) $wert;
        if ($wert === 'eigen') {
            return 'eigen';
        }
        foreach (self::PALETTE as $gruppe) {
            if (isset($gruppe[$wert])) {
                return $wert;
            }
        }

        return $ersatz;
    }

    protected static function hex($wert)
    {
        $wert = trim((string) $wert);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $wert) ? strtoupper($wert) : '';
    }
}
