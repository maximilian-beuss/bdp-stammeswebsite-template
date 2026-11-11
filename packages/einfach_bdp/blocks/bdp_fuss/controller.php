<?php

namespace Concrete\Package\EinfachBdp\Block\BdPFuss;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use EinfachBdP\Platzhalter;
use EinfachBdP\Werkzeug;

/**
 * Die Fußzeile.
 *
 * Sie stand fest im Theme. Eine geänderte Anschrift oder ein neuer
 * Kanal bedeutete: PHP-Datei anfassen. Als Block ist sie pflegbar,
 * ohne dass am Layout etwas kaputtgehen kann — Aufbau und Farben
 * bleiben im Template, nur die Inhalte kommen aus dem Block.
 *
 * Der Block liegt in einem globalen Bereich. Er steht damit auf allen
 * Seiten gleichzeitig und muss nur einmal gepflegt werden.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPFuss';
    protected $btInterfaceWidth = 760;
    protected $btInterfaceHeight = 620;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;   // die Schlusszeile trägt {Jahr}
    protected $btDefaultSet = 'einfach_bdp';

    /** Die drei Symbole, die das Theme kennt. */
    const IKONS = ['mail', 'instagram', 'karte'];

    public function getBlockTypeName()
    {
        return t('Fußzeile');
    }

    public function getBlockTypeDescription()
    {
        return t('Inhalte der Fußzeile: Marken, Anschrift, Kontaktwege, Rechtslinks und Schlusszeile.');
    }

    public function add()
    {
        $this->set('stammZiel', '/');
        $this->set('bdpZiel', 'https://www.pfadfinden.de/');
        $this->set('adresseTitel', 'Das blaue Haus');
        $this->set('adresseZeilen', "Wilhelm-Leuschner-Straße 24\n65824 Schwalbach am Taunus");
        $this->set('anmelden', 1);
        $this->set('schlusszeile', '© {Jahr} Musterstamm · BdP');
        $this->set('kontakteListe', []);
        $this->set('rechtsListe', []);
    }

    public function edit()
    {
        $this->set('kontakteListe', Werkzeug::liste($this->kontakte));
        $this->set('rechtsListe', Werkzeug::liste($this->rechtslinks));
    }

    public function view()
    {
        $kontakte = [];
        foreach (Werkzeug::liste($this->kontakte) as $k) {
            $text = trim(Werkzeug::feld($k, 'text'));
            $ziel = trim(Werkzeug::feld($k, 'ziel'));
            if ($text === '' || $ziel === '') {
                continue;
            }
            $ikon = Werkzeug::feld($k, 'ikon', 'mail');
            $kontakte[] = [
                'text' => $text,
                'ziel' => Werkzeug::link($ziel),
                'ikon' => in_array($ikon, self::IKONS, true) ? $ikon : 'mail',
            ];
        }

        $rechts = [];
        foreach (Werkzeug::liste($this->rechtslinks) as $r) {
            $text = trim(Werkzeug::feld($r, 'text'));
            $ziel = trim(Werkzeug::feld($r, 'ziel'));
            if ($text !== '' && $ziel !== '') {
                $rechts[] = ['text' => $text, 'ziel' => Werkzeug::link($ziel)];
            }
        }

        $zeilen = [];
        foreach (preg_split('/\R/', (string) $this->adresseZeilen) as $zeile) {
            $zeile = trim($zeile);
            if ($zeile !== '') {
                $zeilen[] = $zeile;
            }
        }

        $this->set('kontakteListe', $kontakte);
        $this->set('rechtsListe', $rechts);
        $this->set('adressZeilen', $zeilen);
        $this->set('schlussText', Platzhalter::ersetze($this->schlusszeile));
    }

    public function save($daten)
    {
        parent::save([
            'stammZiel' => trim((string) ($daten['stammZiel'] ?? '/')),
            'bdpZiel' => trim((string) ($daten['bdpZiel'] ?? '')),
            'adresseTitel' => trim((string) ($daten['adresseTitel'] ?? '')),
            'adresseZeilen' => trim((string) ($daten['adresseZeilen'] ?? '')),
            'kontakte' => Werkzeug::listeSpeichern($daten['kontakte'] ?? [], ['text', 'ziel']),
            'rechtslinks' => Werkzeug::listeSpeichern($daten['rechtslinks'] ?? [], ['text', 'ziel']),
            'anmelden' => empty($daten['anmelden']) ? 0 : 1,
            'schlusszeile' => trim((string) ($daten['schlusszeile'] ?? '')),
        ]);
    }
}
