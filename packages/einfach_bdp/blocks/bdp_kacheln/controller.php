<?php

namespace Concrete\Package\EinfachBdp\Block\BdPKacheln;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\PageList;
use EinfachBdP\Werkzeug;

/**
 * Kacheln mit Bild, Titel, Text und Ziel.
 *
 * Zwei Quellen, und die Wahl dazwischen ist der eigentliche Punkt:
 *
 *   Unterseiten  Die Kacheln entstehen aus den Kindseiten. Legt jemand
 *                eine neue Sippe an, steht sie sofort auf der
 *                Übersicht — niemand muss daran denken, sie zusätzlich
 *                zu verlinken. So war es bisher fest im Seitentemplate
 *                verdrahtet.
 *
 *   Manuell      Frei zusammengestellte Kacheln, die auch auf fremde
 *                Seiten zeigen dürfen. Für Wegweiser wie unten auf
 *                „Mitmachen“ oder „Aktionen“.
 *
 * Vorher konnte man nur das eine haben, und zwar nur durch Anfassen
 * einer PHP-Datei.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPKacheln';
    protected $btInterfaceWidth = 780;
    protected $btInterfaceHeight = 620;
    protected $btCacheBlockRecord = true;
    // Nicht zwischenspeichern: Bei Quelle „Unterseiten“ hinge die
    // Ausgabe sonst an dem Stand, den der Seitenbaum beim ersten
    // Aufruf hatte.
    protected $btCacheBlockOutput = false;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Kacheln');
    }

    public function getBlockTypeDescription()
    {
        return t('Kacheln mit Bild, Titel und Text. Entweder automatisch aus den Unterseiten oder frei zusammengestellt.');
    }

    public function add()
    {
        $this->set('quelle', 'unterseiten');
        $this->set('elternID', 0);
        $this->set('spalten', 'auto');
        $this->set('zeigeBild', 1);
        $this->set('zeigeText', 1);
        $this->set('knopfText', 'Mehr erfahren');
        $this->set('kachelListe', []);
    }

    public function edit()
    {
        $this->set('kachelListe', Werkzeug::liste($this->kacheln));
    }

    public function view()
    {
        if ($this->quelle === 'manuell') {
            $liste = $this->ausListe();
        } elseif ($this->quelle === 'berichte') {
            $liste = Werkzeug::berichte();
        } else {
            $liste = $this->ausUnterseiten();
        }

        $this->set('kachelListe', $liste);
    }

    /**
     * Kacheln aus den Kindseiten.
     */
    protected function ausUnterseiten()
    {
        $elternID = (int) $this->elternID;

        if ($elternID < 1) {
            $seite = Page::getCurrentPage();
            if (!is_object($seite) || $seite->isError()) {
                return [];
            }
            $elternID = (int) $seite->getCollectionID();
        }

        $liste = new PageList();
        $liste->filterByParentID($elternID);
        $liste->sortByDisplayOrder();

        $kacheln = [];
        $sindBerichte = true;

        foreach ($liste->getResults() as $kind) {
            if ($kind->getAttribute('exclude_nav') || $kind->isSystemPage()) {
                continue;
            }

            if ($kind->getPageTypeHandle() !== 'bericht') {
                $sindBerichte = false;
            }

            $kacheln[] = Werkzeug::berichtDaten($kind);
        }

        // Sind alle Unterseiten Berichte, gilt das Berichtsdatum (neueste zuerst).
        if ($sindBerichte && count($kacheln) > 1) {
            usort($kacheln, static function (array $a, array $b) {
                return $b['zeitstempel'] <=> $a['zeitstempel'];
            });
        }

        return $kacheln;
    }

    /**
     * Von Hand zusammengestellte Kacheln.
     */
    protected function ausListe()
    {
        $kacheln = [];

        foreach (Werkzeug::liste($this->kacheln) as $k) {
            $titel = trim(Werkzeug::feld($k, 'titel'));
            if ($titel === '') {
                continue;
            }

            $kacheln[] = [
                'titel' => $titel,
                'text' => trim(Werkzeug::feld($k, 'text')),
                'ziel' => Werkzeug::link(trim(Werkzeug::feld($k, 'ziel'))),
                'bild' => Werkzeug::bildUrl(Werkzeug::feld($k, 'bildfID', 0)),
                'etikett' => trim(Werkzeug::feld($k, 'etikett')),
            ];
        }

        return $kacheln;
    }

    public function getSearchableContent()
    {
        $teile = [];
        foreach (Werkzeug::liste($this->kacheln) as $k) {
            $teile[] = Werkzeug::feld($k, 'titel') . ' ' . Werkzeug::feld($k, 'text');
        }

        return implode(' ', $teile);
    }

    public function save($daten)
    {
        $quelle = $daten['quelle'] ?? 'unterseiten';
        if (!in_array($quelle, ['unterseiten', 'berichte', 'manuell'], true)) {
            $quelle = 'unterseiten';
        }

        parent::save([
            'quelle' => $quelle,
            'kacheln' => Werkzeug::listeSpeichern($daten['kacheln'] ?? [], ['titel']),
            'elternID' => max(0, (int) ($daten['elternID'] ?? 0)),
            'spalten' => in_array($daten['spalten'] ?? '', ['auto', '2', '3', '4'], true)
                ? $daten['spalten'] : 'auto',
            'zeigeBild' => empty($daten['zeigeBild']) ? 0 : 1,
            'zeigeText' => empty($daten['zeigeText']) ? 0 : 1,
            'knopfText' => trim((string) ($daten['knopfText'] ?? '')),
        ]);
    }
}
