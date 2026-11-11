<?php

namespace Concrete\Package\EinfachBdp\Block\BdPGalerie;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use EinfachBdP\Werkzeug;

/**
 * Bilderraster.
 *
 * Anordnung
 * ---------
 * Die Bilder liegen in einem CSS-Grid mit grid-auto-flow: dense.
 * „dense“ heißt: entsteht durch ein breites Bild eine Lücke, zieht der
 * Browser ein späteres, schmales Bild vor und füllt sie. Genau das war
 * gefordert — keine Leerstellen, ohne dass jemand die Reihenfolge von
 * Hand austüfteln muss.
 *
 * Wie viel Platz ein Bild bekommt, entscheidet normalerweise sein
 * Seitenverhältnis: was breiter als 16:10 ist, bekommt zwei Spalten,
 * was hochkant ist, zwei Zeilen. Die Redaktion kann das je Bild
 * übersteuern, wenn ein Motiv unbedingt groß sein soll.
 *
 * Dass „dense“ die Lesereihenfolge von der Anzeigereihenfolge trennen
 * kann, ist hier unkritisch: es sind Bilder ohne inhaltliche Abfolge.
 * Bei Text wäre es ein Zugänglichkeitsproblem ✅ WCAG 1.3.2.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPGalerie';
    protected $btInterfaceWidth = 780;
    protected $btInterfaceHeight = 620;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Fotogalerie');
    }

    public function getBlockTypeDescription()
    {
        return t('Bilder in einem platzsparenden Raster, mit Reihenfolge, Titeln und Texten.');
    }

    public function add()
    {
        $this->set('dichte', 'normal');
        $this->set('infos', 1);
        $this->set('lupe', 1);
        $this->set('bilderListe', []);
    }

    public function edit()
    {
        $this->set('bilderListe', Werkzeug::liste($this->bilder));
    }

    public function view()
    {
        $bilder = [];

        foreach (Werkzeug::liste($this->bilder) as $eintrag) {
            $datei = Werkzeug::datei(Werkzeug::feld($eintrag, 'bildfID', 0));
            if (!$datei) {
                continue;
            }

            $breite = (int) $datei->getAttribute('width');
            $hoehe = (int) $datei->getAttribute('height');
            $groesse = Werkzeug::feld($eintrag, 'groesse', 'auto');

            if ($groesse === 'auto') {
                $groesse = $this->ausSeitenverhaeltnis($breite, $hoehe);
            }

            $titel = trim(Werkzeug::feld($eintrag, 'titel'));
            $text = trim(Werkzeug::feld($eintrag, 'text'));

            $bilder[] = [
                'url' => (string) $datei->getURL(),
                'breite' => $breite,
                'hoehe' => $hoehe,
                'titel' => $titel,
                'text' => $text,
                // Der Alternativtext ist der Titel. Steht keiner da,
                // bleibt alt leer — ein Bild ohne Beschreibung als
                // „Bild“ anzukündigen hilft niemandem ✅ WCAG 1.1.1
                'alt' => $titel,
                'groesse' => $groesse,
            ];
        }

        $this->set('bilderListe', $bilder);
    }

    protected function ausSeitenverhaeltnis($breite, $hoehe)
    {
        if ($breite < 1 || $hoehe < 1) {
            return 'normal';
        }

        $verhaeltnis = $breite / $hoehe;

        if ($verhaeltnis >= 1.6) {
            return 'breit';
        }
        if ($verhaeltnis <= 0.72) {
            return 'hoch';
        }

        return 'normal';
    }

    public function save($daten)
    {
        parent::save([
            'bilder' => Werkzeug::listeSpeichern($daten['bilder'] ?? [], ['bildfID']),
            'dichte' => in_array($daten['dichte'] ?? '', ['schmal', 'normal', 'weit'], true)
                ? $daten['dichte'] : 'normal',
            'infos' => empty($daten['infos']) ? 0 : 1,
            'lupe' => empty($daten['lupe']) ? 0 : 1,
        ]);
    }
}
