<?php

namespace Concrete\Package\EinfachBdp\Block\BdPEckdaten;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Page\Page;
use EinfachBdP\Kalender\Stufen;
use EinfachBdP\Werkzeug;

/**
 * Die Eckdaten einer Gruppe: Alter, Gruppenstunde, Treffpunkt,
 * Ansprechperson.
 *
 * Vorher standen sie fest im Seitentemplate und zogen ihre Werte aus
 * Seitenattributen. Wer eine Zeile ergänzen wollte — etwa „Mitbringen“
 * — musste gruppe.php ändern und ein neues Attribut anlegen.
 *
 * Als Block sind die Zeilen frei: Bezeichnung und Wert, so viele wie
 * gebraucht werden.
 *
 * Die Farbe kommt normalerweise aus der Stufe der Seite, damit eine
 * Meutenseite gelb umrandet ist und eine Sippenseite blau, ohne dass
 * jemand daran denken muss.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPEckdaten';
    protected $btInterfaceWidth = 700;
    protected $btInterfaceHeight = 540;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;   // die Stufe hängt an der Seite
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Eckdaten');
    }

    public function getBlockTypeDescription()
    {
        return t('Kurze Faktenliste im farbigen Rahmen, für Alter, Gruppenstunde und Treffpunkt einer Gruppe.');
    }

    public function add()
    {
        $this->set('stufe', 'auto');
        $this->set('zeilenListe', [
            ['bezeichnung' => 'Alter', 'wert' => ''],
            ['bezeichnung' => 'Gruppenstunde', 'wert' => ''],
            ['bezeichnung' => 'Treffpunkt', 'wert' => ''],
            ['bezeichnung' => 'Ansprechperson', 'wert' => ''],
        ]);
        $this->set('stufenKatalog', Stufen::katalog());
    }

    public function edit()
    {
        $this->set('zeilenListe', Werkzeug::liste($this->zeilen));
        $this->set('stufenKatalog', Stufen::katalog());
    }

    public function view()
    {
        $zeilen = [];

        foreach (Werkzeug::liste($this->zeilen) as $z) {
            $bezeichnung = trim(Werkzeug::feld($z, 'bezeichnung'));
            $wert = trim(Werkzeug::feld($z, 'wert'));

            // Eine Zeile ohne Wert ist keine Information, sondern eine
            // Lücke. Sie fällt weg statt leer dazustehen.
            if ($bezeichnung === '' || $wert === '') {
                continue;
            }

            $zeilen[] = ['bezeichnung' => $bezeichnung, 'wert' => $wert];
        }

        $stufe = $this->stufe === 'auto' ? $this->stufeAusSeite() : $this->stufe;

        $this->set('zeilenListe', $zeilen);
        $this->set('stufeHandle', $stufe);
        $this->set('stufeFarbe', Stufen::farbe($stufe));
        $this->set('stufeName', Stufen::name($stufe));
    }

    /**
     * Rät die Stufe aus dem Seitenattribut „gruppe_stufe“.
     *
     * Dort steht Klartext („Meute“, „Sippe“, „Runde“), nicht das
     * Kürzel des Kalenders. Übersetzt wird über den Wortanfang; was
     * nicht passt, landet bei „alle“ und bekommt damit Grün.
     */
    protected function stufeAusSeite()
    {
        $seite = Page::getCurrentPage();

        if (!is_object($seite) || $seite->isError()) {
            return 'alle';
        }

        $text = mb_strtolower(trim((string) $seite->getAttribute('gruppe_stufe')));

        if ($text === '') {
            return 'alle';
        }

        if (strpos($text, 'meute') !== false || strpos($text, 'wölf') !== false
            || strpos($text, 'woelf') !== false) {
            return 'woelflinge';
        }

        if (strpos($text, 'sippe') !== false || strpos($text, 'pfadfinder') !== false) {
            return 'pfadfinder';
        }

        if (strpos($text, 'runde') !== false || strpos($text, 'thing') !== false
            || strpos($text, 'rover') !== false || strpos($text, 'ranger') !== false) {
            return 'ranger';
        }

        return 'alle';
    }

    public function getSearchableContent()
    {
        $teile = [];
        foreach (Werkzeug::liste($this->zeilen) as $z) {
            $teile[] = Werkzeug::feld($z, 'bezeichnung') . ' ' . Werkzeug::feld($z, 'wert');
        }

        return implode(' ', $teile);
    }

    public function save($daten)
    {
        $stufe = $daten['stufe'] ?? 'auto';

        parent::save([
            'zeilen' => Werkzeug::listeSpeichern($daten['zeilen'] ?? [], ['bezeichnung', 'wert']),
            'stufe' => ($stufe === 'auto' || Stufen::gibt($stufe)) ? $stufe : 'auto',
        ]);
    }
}
