<?php

namespace Concrete\Package\EinfachBdp\Block\BdPKalender;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Support\Facade\Url;
use EinfachBdP\Kalender\Speicher;
use EinfachBdP\Kalender\Stufen;

/**
 * Der Stammeskalender.
 *
 * Die Termine liegen nicht im Block, sondern in einer eigenen Tabelle
 * und werden im Dashboard unter “einfachBdP -> Termine” gepflegt. Der
 * Block bestimmt nur, wie sie dargestellt werden. Deshalb kann man
 * denselben Kalender auf mehreren Seiten unterschiedlich zeigen —
 * etwa auf der Startseite als kurze Liste und unter „Termine“ als
 * Monatsraster — ohne die Termine doppelt zu pflegen.
 *
 * Die Ansichten baut kalender.js aus dem JSON von /kalender/daten.
 * Serverseitig kommt eine vollständige Terminliste mit, damit die
 * Seite ohne JavaScript nicht leer ist ✅ und damit Suchmaschinen
 * die Termine finden.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPKalender';
    protected $btInterfaceWidth = 640;
    protected $btInterfaceHeight = 520;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Kalender');
    }

    public function getBlockTypeDescription()
    {
        return t('Stammeskalender mit Monats-, Halbjahres-, Jahres- und Listenansicht, Stufenfarben und iCal-Abo.');
    }

    public function add()
    {
        $this->set('ansicht', 'monat');
        $this->set('stufen', '');
        $this->set('zeigeFrei', 1);
        $this->set('zeigeAbo', 1);
        $this->set('zeigeUmschalter', 1);
        $this->set('sechseckAktiviert', 0);
        $this->set('stufenKatalog', Stufen::katalog());
    }

    public function edit()
    {
        $this->set('stufenKatalog', Stufen::katalog());
        $this->set('gewaehlteStufen', $this->stufenAlsListe());
        $this->set('sechseckAktiviert', (int) $this->sechseckAktiviert);
    }

    protected function stufenAlsListe()
    {
        $roh = trim((string) $this->stufen);

        return $roh === '' ? [] : array_values(array_filter(explode(',', $roh)));
    }

    public function view()
    {
        $stufen = $this->stufenAlsListe();

        // Ein Jahr zurück und zwei nach vorn: deckt jede Ansicht ab,
        // ohne beim Blättern nachladen zu müssen.
        $von = date('Y-01-01', strtotime('-1 year'));
        $bis = date('Y-12-31', strtotime('+1 year'));

        $termine = Speicher::zeitraum($von, $bis, $stufen);

        $ical = (string) Url::to('/kalender/termine.ics');

        $this->set('termine', $termine);
        $this->set('kommend', $this->kommende($termine));
        $this->set('stufenKatalog', Stufen::katalog());
        $this->set('gewaehlteStufen', $stufen);
        $this->set('datenUrl', (string) Url::to('/kalender/daten'));
        $this->set('icalUrl', $ical);
        $this->set('sechseckAktiviert', (int) $this->sechseckAktiviert);

        /*
           Die drei Abo-Wege zeigen alle auf denselben Feed.

           Google braucht die Adresse als Parameter cid; es holt den
           Feed danach selbst und in eigenem Takt ab.

           webcal:// ist kein eigenes Protokoll, sondern derselbe
           HTTP-Abruf mit anderem Schema — Apple Kalender und Outlook
           nehmen es als Signal „das ist ein Abo, kein Download“. Nur
           der Schemateil wird ersetzt, der Rest der Adresse bleibt.
        */
        $this->set('googleUrl',
            'https://calendar.google.com/calendar/r?cid=' . rawurlencode($ical));
        $this->set('webcalUrl', preg_replace('#^https?://#i', 'webcal://', $ical));
    }

    /**
     * Termine für die Ausgabe ohne JavaScript.
     *
     * Derselbe Zeitraum wie in der Listenansicht: einen Monat zurück
     * und alles, was kommt. Wer ohne JavaScript liest, soll dasselbe
     * sehen wie alle anderen.
     */
    protected function kommende(array $termine)
    {
        $grenze = date('Y-m-d', strtotime('-1 month'));
        $raus = [];

        foreach ($termine as $t) {
            if (substr($t['ende'], 0, 10) >= $grenze) {
                $raus[] = $t;
            }
        }

        return $raus;
    }

    public function save($daten)
    {
        $stufen = [];
        foreach ((array) ($daten['stufen'] ?? []) as $s) {
            if (Stufen::gibt($s)) {
                $stufen[] = $s;
            }
        }

        // Alle vier gewählt heißt dasselbe wie keine Einschränkung.
        if (count($stufen) === count(Stufen::katalog())) {
            $stufen = [];
        }

        parent::save([
            'ansicht' => in_array($daten['ansicht'] ?? '', ['monat', 'halbjahr', 'jahr', 'liste'], true)
                ? $daten['ansicht'] : 'monat',
            'stufen' => implode(',', $stufen),
            'zeigeFrei' => empty($daten['zeigeFrei']) ? 0 : 1,
            'zeigeAbo' => empty($daten['zeigeAbo']) ? 0 : 1,
            'zeigeUmschalter' => empty($daten['zeigeUmschalter']) ? 0 : 1,
            'sechseckAktiviert' => empty($daten['sechseckAktiviert']) ? 0 : 1,
        ]);
    }
}
