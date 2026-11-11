<?php

namespace Concrete\Package\EinfachBdp\Block\BdPSeitenkopf;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Page\Page;
use EinfachBdP\Platzhalter;
use EinfachBdP\Werkzeug;

/**
 * Der blaue Kopf über jeder Unterseite.
 *
 * Vorher stand er fest im Seitentemplate und zeigte immer den
 * Seitennamen und die Kurzbeschreibung. Wer den Untertitel ändern
 * wollte, musste die Seiteneigenschaften finden — und wer dort etwas
 * anderes stehen haben wollte als in der Suchmaschinen-Beschreibung,
 * hatte Pech.
 *
 * Als Block ist beides frei, und über Platzhalter ({Titel},
 * {Untertitel}, {Pfad} …) bleibt es trotzdem an die Seite gekoppelt,
 * wenn man das will.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPSeitenkopf';
    protected $btInterfaceWidth = 640;
    protected $btInterfaceHeight = 560;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;   // Platzhalter hängen an der Seite
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Seitenkopf');
    }

    public function getBlockTypeDescription()
    {
        return t('Blaue Kopffläche mit Brotkrumen, Titel und Untertitel. Titel und Untertitel dürfen Platzhalter enthalten.');
    }

    public function add()
    {
        $this->set('titel', '{Titel}');
        $this->set('untertitel', '{Untertitel}');
        $this->set('krumen', 1);
        $this->set('bildfID', 0);
        $this->set('stil', 'blau');
        $this->maskeVorbereiten();
    }

    public function edit()
    {
        $this->maskeVorbereiten();
    }

    protected function maskeVorbereiten()
    {
        $this->set('platzhalter', Platzhalter::katalog());
        $this->set('bildDatei', Werkzeug::datei($this->bildfID));
    }

    public function view()
    {
        $seite = Page::getCurrentPage();

        $this->set('titelText', Platzhalter::ersetze($this->titel, $seite));
        $this->set('untertitelText', Platzhalter::ersetze($this->untertitel, $seite));
        $this->set('bildUrl', Werkzeug::bildUrl($this->bildfID));
        $this->set('krumenListe', $this->krumen ? $this->krumenBauen($seite) : []);
    }

    /**
     * Brotkrumen als Liste aus Name und Link. Die aktuelle Seite steht
     * am Ende ohne Link.
     */
    protected function krumenBauen($seite)
    {
        if (!is_object($seite) || $seite->isError()) {
            return [];
        }

        $krumen = [];
        $lauf = $seite;
        $bremse = 0;

        while (is_object($lauf) && !$lauf->isError() && $bremse++ < 20) {
            array_unshift($krumen, [
                'name' => (string) $lauf->getCollectionName(),
                'link' => (string) $lauf->getCollectionLink(),
                'hier' => (int) $lauf->getCollectionID() === (int) $seite->getCollectionID(),
            ]);
            if ((int) $lauf->getCollectionID() <= 1) {
                break;
            }
            $lauf = Page::getByID($lauf->getCollectionParentID());
        }

        // Nur die Startseite: dann sind Brotkrumen sinnlos.
        return count($krumen) > 1 ? $krumen : [];
    }

    public function save($daten)
    {
        parent::save([
            'titel' => trim((string) ($daten['titel'] ?? '')),
            'untertitel' => trim((string) ($daten['untertitel'] ?? '')),
            'krumen' => empty($daten['krumen']) ? 0 : 1,
            'bildfID' => (int) ($daten['bildfID'] ?? 0),
            'stil' => in_array($daten['stil'] ?? '', ['blau', 'bild', 'hell'], true) ? $daten['stil'] : 'blau',
        ]);
    }
}
