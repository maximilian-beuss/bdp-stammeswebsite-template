<?php

namespace Concrete\Package\EinfachBdp\Block\BdPAufnahme;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use EinfachBdP\Fliesstext;
use EinfachBdP\Werkzeug;

/**
 * Der Aufnahmeantrag als Schrittfolge.
 *
 * Das Problem, das er löst: Der Antrag führt an mehreren Stellen auf
 * fremde Seiten — das Antragsformular des Bundes, ein Microsoft-Formular
 * für die Fotoerlaubnis. Wer dort landet, ist weg. Auf der alten Seite
 * stand alles untereinander, und wer nach dem Formular zurückkam, fand
 * die Stelle nicht wieder, an der er war.
 *
 * Deshalb:
 *   1. Ein Schritt nach dem anderen, mit sichtbarem Fortschritt. Man
 *      sieht, wie viel noch kommt.
 *   2. Vor jedem Weg nach draußen ein kurzer Zwischenruf: „Du bist
 *      gleich wieder hier, danach geht es mit Schritt 4 weiter.“
 *   3. Der erreichte Schritt liegt im localStorage. Wer zurückkommt,
 *      steht wieder da, wo er aufgehört hat.
 *
 * Ohne JavaScript stehen einfach alle Schritte untereinander. Das ist
 * dann die alte Seite — unschön, aber vollständig ✅ WCAG: kein Inhalt
 * hängt an einem Skript.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPAufnahme';
    protected $btInterfaceWidth = 820;
    protected $btInterfaceHeight = 660;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Aufnahmeantrag');
    }

    public function getBlockTypeDescription()
    {
        return t('Mehrschrittiger Antrag mit Fortschrittsanzeige und Rückkehr-Hinweis vor externen Links.');
    }

    public function add()
    {
        $this->set('hinausTitel', 'Du verlässt kurz unsere Seite');
        $this->set('hinausText', "Der nächste Schritt läuft auf einer fremden Seite. Sie öffnet sich in einem neuen Fenster, diese Seite hier bleibt offen.\n\nWenn du dort fertig bist, komm einfach hierher zurück. Wir merken uns, wo du warst.");
        $this->set('merken', 1);
        $this->set('schritteListe', []);
    }

    public function edit()
    {
        $this->set('schritteListe', Werkzeug::liste($this->schritte));
    }

    public function view()
    {
        $schritte = [];

        foreach (Werkzeug::liste($this->schritte) as $s) {
            $titel = trim(Werkzeug::feld($s, 'titel'));
            if ($titel === '') {
                continue;
            }

            $ziel = Werkzeug::link(trim(Werkzeug::feld($s, 'knopfZiel')));
            $dateiUrl = Werkzeug::bildUrl(Werkzeug::feld($s, 'dateifID', 0));

            // Eine hinterlegte Datei sticht das Adressfeld: wer beides
            // ausfüllt, meinte mit ziemlicher Sicherheit die Datei.
            if ($dateiUrl !== '') {
                $ziel = $dateiUrl;
            }

            $schritte[] = [
                'titel' => $titel,
                'text' => Fliesstext::zuHtml(Werkzeug::feld($s, 'text')),
                'bild' => Werkzeug::bildUrl(Werkzeug::feld($s, 'bildfID', 0)),
                'knopfText' => trim(Werkzeug::feld($s, 'knopfText')),
                'knopfZiel' => $ziel,
                // Ein Download ist kein Weg nach draußen, auch wenn er
                // technisch eine andere Adresse hat.
                'extern' => $dateiUrl === '' && Fliesstext::istExtern($ziel),
                'einbettung' => trim(Werkzeug::feld($s, 'einbettung')),
                'abschluss' => !empty($s['abschluss']),
            ];
        }

        $this->set('schritteListe', $schritte);
        $this->set('anzahl', count($schritte));
        $this->set('hinausTextHtml', Fliesstext::zuHtml($this->hinausText));
        // Der Speicherschlüssel hängt am Block, nicht an der Seite:
        // so stört ein zweiter Antragsblock den ersten nicht.
        $this->set('merkschluessel', 'bdp-antrag-' . (int) $this->bID);
    }

    public function getSearchableContent()
    {
        $teile = [];
        foreach (Werkzeug::liste($this->schritte) as $s) {
            $teile[] = Werkzeug::feld($s, 'titel') . ' ' . Werkzeug::feld($s, 'text');
        }

        return implode(' ', $teile);
    }

    public function save($daten)
    {
        parent::save([
            'schritte' => Werkzeug::listeSpeichern($daten['schritte'] ?? [], ['titel', 'text']),
            'hinausTitel' => trim((string) ($daten['hinausTitel'] ?? '')),
            'hinausText' => trim((string) ($daten['hinausText'] ?? '')),
            'merken' => empty($daten['merken']) ? 0 : 1,
        ]);
    }
}
