<?php

namespace Concrete\Package\EinfachBdp\Block\BdPBild;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use EinfachBdP\Werkzeug;

/**
 * Bild oder Bildreihe mit Bildunterschrift.
 *
 * Vorher stand so etwas als handgeschriebenes <figure> im Inhaltsblock.
 * Wer ein Bild tauschen wollte, musste im Quelltext den richtigen
 * img-Pfad finden, und wer eine Bildunterschrift ergänzen wollte, ein
 * figcaption an die richtige Stelle setzen.
 *
 * Ein Bild ergibt eine Figur, mehrere ergeben eine Reihe. Das ist keine
 * zusätzliche Einstellung, sondern folgt aus der Zahl der Bilder — es
 * gibt keinen Fall, in dem man drei Bilder einträgt und sie
 * untereinander stehen sollen; dafür nimmt man drei Blöcke.
 *
 * Für ein Bild, das neben dem Text stehen soll, ist dieser Block nicht
 * zuständig: Das kann der Abschnittsblock, weil dort Bild und Text
 * zusammengehören und der Umfluss auf den Abschnitt begrenzt bleibt.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPBild';
    protected $btInterfaceWidth = 740;
    protected $btInterfaceHeight = 560;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Bild');
    }

    public function getBlockTypeDescription()
    {
        return t('Ein Bild oder eine Reihe von Bildern, jeweils mit Bildunterschrift und Quellenangabe.');
    }

    public function add()
    {
        $this->set('breite', 'voll');
        $this->set('format', 'frei');
        $this->set('zeigeText', 1);
        $this->set('bilderListe', []);
    }

    public function edit()
    {
        $this->set('bilderListe', Werkzeug::liste($this->bilder));
    }

    public function view()
    {
        $bilder = [];

        foreach (Werkzeug::liste($this->bilder) as $b) {
            $datei = Werkzeug::datei(Werkzeug::feld($b, 'bildfID', 0));
            if (!$datei) {
                continue;
            }

            $titel = trim(Werkzeug::feld($b, 'titel'));

            $bilder[] = [
                'url' => (string) $datei->getURL(),
                'breite' => (int) $datei->getAttribute('width'),
                'hoehe' => (int) $datei->getAttribute('height'),
                'titel' => $titel,
                'text' => trim(Werkzeug::feld($b, 'text')),
                'quelle' => trim(Werkzeug::feld($b, 'quelle')),
                // Der Alternativtext ist der Titel. Steht keiner da,
                // bleibt alt leer — ein Bild ohne Beschreibung als
                // „Bild“ anzukündigen hilft niemandem ✅ WCAG 1.1.1
                'alt' => $titel,
            ];
        }

        $this->set('bilderListe', $bilder);
        $this->set('istReihe', count($bilder) > 1);
    }

    public function getSearchableContent()
    {
        $teile = [];
        foreach (Werkzeug::liste($this->bilder) as $b) {
            $teile[] = Werkzeug::feld($b, 'titel') . ' ' . Werkzeug::feld($b, 'text');
        }

        return implode(' ', $teile);
    }

    public function save($daten)
    {
        parent::save([
            'bilder' => Werkzeug::listeSpeichern($daten['bilder'] ?? [], ['bildfID']),
            'breite' => in_array($daten['breite'] ?? '', ['voll', 'schmal', 'breit'], true)
                ? $daten['breite'] : 'voll',
            'format' => in_array($daten['format'] ?? '', ['frei', 'quer', 'hoch', 'quadrat'], true)
                ? $daten['format'] : 'frei',
            'zeigeText' => empty($daten['zeigeText']) ? 0 : 1,
        ]);
    }
}
