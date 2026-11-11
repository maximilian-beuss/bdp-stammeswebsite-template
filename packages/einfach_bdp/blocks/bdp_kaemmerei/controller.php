<?php

namespace Concrete\Package\EinfachBdp\Block\BdPKaemmerei;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use EinfachBdP\Werkzeug;

/**
 * Die Artikelliste der Kämmerei.
 *
 * Auf der alten Website war das eine handgebaute Tabelle. Jede neue
 * Wanderhose bedeutete: Tabelle im Quelltext finden, Zeile kopieren,
 * Zelle einfügen, Bild verlinken — und wenn dabei ein </td> verloren
 * ging, war die halbe Seite kaputt.
 *
 * Hier ist ein Artikel eine Zeile in der Maske. Angeordnet wird
 * automatisch: gleich hohe Karten in einem Raster, das mitwächst.
 *
 * Die Rubrik ist ein freies Textfeld und keine feste Auswahl. Die
 * Kämmerei soll eine neue Rubrik aufmachen können, ohne dass jemand
 * Code anfassen muss. Gleich geschriebene Rubriken landen zusammen,
 * die Reihenfolge ergibt sich aus dem ersten Auftreten.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPKaemmerei';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 640;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Kämmerei-Artikel');
    }

    public function getBlockTypeDescription()
    {
        return t('Artikel der Kämmerei mit Bild, Beschreibung, Preis und Rubrik. Wird automatisch als Raster angeordnet.');
    }

    public function add()
    {
        $this->set('gruppieren', 1);
        $this->set('bestellMail', 'kaemmerei@musterstamm.de');
        $this->set('artikelListe', []);
    }

    public function edit()
    {
        $this->set('artikelListe', Werkzeug::liste($this->artikel));
        $this->set('rubriken', $this->rubrikenSammeln());
    }

    /**
     * Bereits vergebene Rubriken, für die Vorschlagsliste in der Maske.
     */
    protected function rubrikenSammeln()
    {
        $namen = [];
        foreach (Werkzeug::liste($this->artikel) as $a) {
            $r = trim(Werkzeug::feld($a, 'rubrik'));
            if ($r !== '' && !in_array($r, $namen, true)) {
                $namen[] = $r;
            }
        }

        return $namen;
    }

    public function view()
    {
        $gruppen = [];

        foreach (Werkzeug::liste($this->artikel) as $a) {
            $name = trim(Werkzeug::feld($a, 'name'));
            if ($name === '') {
                continue;
            }

            $rubrik = $this->gruppieren ? trim(Werkzeug::feld($a, 'rubrik')) : '';
            if (!isset($gruppen[$rubrik])) {
                $gruppen[$rubrik] = [];
            }

            $gruppen[$rubrik][] = [
                'name' => $name,
                'bild' => Werkzeug::bildUrl(Werkzeug::feld($a, 'bildfID', 0)),
                'beschreibung' => trim(Werkzeug::feld($a, 'beschreibung')),
                'preis' => trim(Werkzeug::feld($a, 'preis')),
                'hinweis' => trim(Werkzeug::feld($a, 'hinweis')),
                'zustand' => Werkzeug::feld($a, 'zustand', 'neu'),
            ];
        }

        $this->set('gruppen', $gruppen);
        $this->set('mail', trim((string) $this->bestellMail));
    }

    public function getSearchableContent()
    {
        $teile = [];
        foreach (Werkzeug::liste($this->artikel) as $a) {
            $teile[] = Werkzeug::feld($a, 'name') . ' ' . Werkzeug::feld($a, 'beschreibung');
        }

        return implode(' ', $teile);
    }

    public function save($daten)
    {
        $mail = trim((string) ($daten['bestellMail'] ?? ''));
        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $mail = '';
        }

        parent::save([
            'artikel' => Werkzeug::listeSpeichern($daten['artikel'] ?? [], ['name']),
            'gruppieren' => empty($daten['gruppieren']) ? 0 : 1,
            'bestellMail' => $mail,
        ]);
    }
}
