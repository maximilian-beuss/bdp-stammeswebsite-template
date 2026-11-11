<?php

namespace Concrete\Package\EinfachBdp\Block\BdPHero;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Page\Page;
use EinfachBdP\Platzhalter;
use EinfachBdP\Werkzeug;

/**
 * Der Bildwechsler der Startseite.
 *
 * Vorher standen die Folien in application/config/bdp.php. Wer
 * ein Bild tauschen wollte, musste eine PHP-Datei anfassen. Jetzt ist
 * jede Folie im Block editierbar: Bild, Überschrift, Text und bis zu
 * zwei Knöpfe.
 *
 * Der Takt ist einstellbar und darf 0 sein. Dann wechselt nichts von
 * allein, und die Folien sind nur noch von Hand erreichbar — das ist
 * für Menschen, die von Bewegung abgelenkt werden, die freundlichere
 * Voreinstellung, aber es soll die Redaktion entscheiden.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPHero';
    protected $btInterfaceWidth = 780;
    protected $btInterfaceHeight = 620;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Hero-Bildwechsler');
    }

    public function getBlockTypeDescription()
    {
        return t('Großflächiger Bildwechsler mit Überschrift, Text und Knöpfen je Folie.');
    }

    public function add()
    {
        $this->set('takt', 7000);
        $this->set('hoehe', 'normal');
        $this->set('folienListe', []);
    }

    public function edit()
    {
        $this->set('folienListe', Werkzeug::liste($this->folien));
    }

    public function view()
    {
        $seite = Page::getCurrentPage();
        $folien = [];

        foreach (Werkzeug::liste($this->folien) as $folie) {
            $bild = Werkzeug::bildUrl(Werkzeug::feld($folie, 'bildfID', 0));

            // Eine Folie ohne Bild wäre eine leere blaue Fläche mit
            // Text darauf. Das ist kein Hero, das ist ein Unfall.
            if ($bild === '') {
                continue;
            }

            $knoepfe = [];
            foreach ([1, 2] as $n) {
                $text = trim(Werkzeug::feld($folie, 'knopf' . $n . 'text'));
                $ziel = trim(Werkzeug::feld($folie, 'knopf' . $n . 'ziel'));
                if ($text !== '' && $ziel !== '') {
                    $knoepfe[] = [
                        'text' => Platzhalter::ersetze($text, $seite),
                        // Seiteninterne Ziele stehen als {CCM:…} in der
                        // Datenbank und werden erst hier zur Adresse.
                        'ziel' => Werkzeug::link($ziel),
                        'stil' => Werkzeug::feld($folie, 'knopf' . $n . 'stil', 'btn--gelb'),
                    ];
                }
            }

            $folien[] = [
                'bild' => $bild,
                'alt' => Werkzeug::feld($folie, 'alt'),
                'titel' => Platzhalter::ersetze(Werkzeug::feld($folie, 'titel'), $seite),
                'text' => Platzhalter::ersetze(Werkzeug::feld($folie, 'text'), $seite),
                'knoepfe' => $knoepfe,
            ];
        }

        $this->set('folienListe', $folien);
        $this->set('anzahl', count($folien));
    }

    public function save($daten)
    {
        parent::save([
            'folien' => Werkzeug::listeSpeichern(
                $daten['folien'] ?? [],
                ['bildfID', 'titel', 'text']
            ),
            'takt' => max(0, min(60000, (int) ($daten['takt'] ?? 7000))),
            'hoehe' => in_array($daten['hoehe'] ?? '', ['klein', 'normal', 'gross'], true)
                ? $daten['hoehe'] : 'normal',
        ]);
    }
}
