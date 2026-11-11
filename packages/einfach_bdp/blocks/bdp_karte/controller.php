<?php

namespace Concrete\Package\EinfachBdp\Block\BdPKarte;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;

/**
 * Eine Karte, die erst nach Einwilligung lädt.
 *
 * Vorher stand die Karte als handgeschriebenes <div data-einbettung>
 * mitten im Text des Abschnittsblocks „Adresse“. Wer die Anschrift
 * ändern wollte, bekam eine 400 Zeichen lange Google-URL mit ins
 * Bearbeitungsfenster und musste aufpassen, sie nicht zu zerlegen.
 *
 * Warum überhaupt eine Einwilligung
 * ---------------------------------
 * Ein eingebetteter Kartenrahmen baut beim Laden eine Verbindung zu
 * Google auf und überträgt dabei die IP-Adresse des Besuchers. Das
 * ist eine Datenübermittlung, für die es eine Rechtsgrundlage
 * braucht; hier ist es die Einwilligung nach Art. 6 Abs. 1 lit. a
 * DSGVO. Deshalb liegt vor der Karte ein Platzhalter, und erst ein
 * Klick lädt sie.
 *
 * Die Entscheidung merkt sich der Browser im localStorage — ein
 * Cookie wäre es auch getan, aber localStorage geht nicht bei jeder
 * Anfrage an den Server mit. Zurücknehmen kann man sie auf der Seite
 * der Datenschutzerklärung, dort steht der Block „Einwilligungen“.
 *
 * Ohne JavaScript bleibt der Platzhalter stehen und zeigt Anschrift
 * und einen gewöhnlichen Link zum Kartendienst ✅ — die Seite
 * verliert dadurch keine Information.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPKarte';
    protected $btInterfaceWidth = 700;
    protected $btInterfaceHeight = 560;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Karte');
    }

    public function getBlockTypeDescription()
    {
        return t('Eine eingebettete Karte, die erst nach Einwilligung des Besuchers geladen wird.');
    }

    public function add()
    {
        $this->set('einbettung', '');
        $this->set('dienst', 'Google Maps');
        $this->set('titel', '');
        $this->set('anschrift', '');
        $this->set('verweis', '');
        $this->set('hoehe', 420);
        $this->set('referrer', 'strict-origin-when-cross-origin');
    }

    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('javascript', 'einfach_bdp/einwilligung');
    }

    public function view()
    {
        $this->set('hoeheWert', max(200, min(900, (int) $this->hoehe)));
    }

    public function getSearchableContent()
    {
        return $this->titel . ' ' . strip_tags((string) $this->anschrift);
    }

    public function save($daten)
    {
        $referrer = (string) ($daten['referrer'] ?? '');
        $erlaubt = ['no-referrer', 'origin', 'strict-origin-when-cross-origin'];

        parent::save([
            'einbettung' => trim((string) ($daten['einbettung'] ?? '')),
            'dienst' => trim((string) ($daten['dienst'] ?? '')) ?: 'Google Maps',
            'titel' => trim((string) ($daten['titel'] ?? '')),
            'anschrift' => trim((string) ($daten['anschrift'] ?? '')),
            'verweis' => trim((string) ($daten['verweis'] ?? '')),
            'hoehe' => max(200, min(900, (int) ($daten['hoehe'] ?? 420))),
            'referrer' => in_array($referrer, $erlaubt, true) ? $referrer : 'strict-origin-when-cross-origin',
        ]);
    }
}
