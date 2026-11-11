<?php

namespace Concrete\Package\EinfachBdp\Controller\SinglePage\Dashboard\EinfachBdp;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Page\Controller\DashboardPageController;
use EinfachBdP\Kalender\Feiertage;
use EinfachBdP\Kalender\Ferienquelle;
use EinfachBdP\Kalender\Speicher;
use EinfachBdP\Kalender\Stufen;

/**
 * Terminpflege im Dashboard.
 *
 * Bewusst eine einzige Seite mit Liste und Formular nebeneinander statt
 * einer Klickstrecke über mehrere Masken: Wer zehn Termine eines
 * Halbjahrs einträgt, will nicht zehnmal navigieren.
 */
class Termine extends DashboardPageController
{
    public function view()
    {
        $this->set('termine', Speicher::alle());
        $this->set('stufen', Stufen::katalog());
        $this->set('frei', Speicher::freieTage(date('Y-01-01', strtotime('-1 year')), date('Y-12-31', strtotime('+3 years'))));
        $this->set('bearbeiten', null);
        $this->set('jahr', (int) date('Y'));

        $this->set('laender', Ferienquelle::laender());
        $this->set('land', Ferienquelle::land());
        $this->set('protokoll', Ferienquelle::protokoll());
    }

    /**
     * Formular mit einem vorhandenen Termin füllen.
     */
    public function bearbeiten($terminID = 0)
    {
        $this->view();
        $this->set('bearbeiten', Speicher::einer($terminID));
    }

    public function sichern()
    {
        if (!$this->token->validate('bdp_termin_sichern')) {
            $this->error->add($this->token->getErrorMessage());
        }

        $post = $this->request->request;

        $titel = trim((string) $post->get('titel'));
        $beginnTag = trim((string) $post->get('beginnTag'));
        $endeTag = trim((string) $post->get('endeTag'));
        $ganztaegig = (bool) $post->get('ganztaegig');
        $beginnZeit = trim((string) $post->get('beginnZeit'));
        $endeZeit = trim((string) $post->get('endeZeit'));

        if ($titel === '') {
            $this->error->add(t('Der Termin braucht einen Titel.'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $beginnTag)) {
            $this->error->add(t('Der Beginn braucht ein Datum.'));
        }

        if ($endeTag === '') {
            $endeTag = $beginnTag;
        }

        if ($this->error->has()) {
            $this->view();
            $this->set('bearbeiten', $post->all());

            return;
        }

        // Ganztägig heißt: von 00:00 des ersten bis 23:59 des letzten
        // Tages. So greift der Überschneidungstest in Speicher::zeitraum
        // auch für den letzten Tag.
        if ($ganztaegig) {
            $beginn = $beginnTag . ' 00:00:00';
            $ende = $endeTag . ' 23:59:00';
        } else {
            $beginn = $beginnTag . ' ' . ($beginnZeit !== '' ? $beginnZeit : '00:00') . ':00';
            $ende = $endeTag . ' ' . ($endeZeit !== '' ? $endeZeit : '23:59') . ':00';
        }

        Speicher::sichern([
            'titel' => $titel,
            'beginn' => $beginn,
            'ende' => $ende,
            'ganztaegig' => $ganztaegig,
            'stufe' => (string) $post->get('stufe'),
            'ort' => (string) $post->get('ort'),
            'beschreibung' => (string) $post->get('beschreibung'),
            'link' => (string) $post->get('link'),
        ], (int) $post->get('terminID'));

        $this->flash('success', t('Termin gespeichert.'));
        $this->redirect('/dashboard/einfach_bdp/termine');
    }

    public function loeschen($terminID = 0, $token = '')
    {
        if (!$this->token->validate('bdp_termin_loeschen', $token)) {
            $this->flash('error', $this->token->getErrorMessage());
            $this->redirect('/dashboard/einfach_bdp/termine');
        }

        Speicher::loeschen($terminID);
        $this->flash('success', t('Termin gelöscht.'));
        $this->redirect('/dashboard/einfach_bdp/termine');
    }

    /**
     * Feiertage eines Jahres berechnen und eintragen.
     */
    public function feiertage()
    {
        if (!$this->token->validate('bdp_feiertage')) {
            $this->flash('error', $this->token->getErrorMessage());
            $this->redirect('/dashboard/einfach_bdp/termine');
        }

        $jahr = (int) $this->request->request->get('jahr');
        if ($jahr < 2000 || $jahr > 2100) {
            $jahr = (int) date('Y');
        }

        $n = Feiertage::eintragen($jahr);
        $this->flash('success', t('%s Feiertage für %s eingetragen.', $n, $jahr));
        $this->redirect('/dashboard/einfach_bdp/termine');
    }

    /**
     * Ferien und Feiertage bei mehr-schulferien.de holen.
     *
     * Der Abruf läuft hier und nicht beim Anzeigen des Kalenders: Auf
     * einer öffentlichen Seite hätte eine hängende fremde Schnittstelle
     * die Ladezeit verlängert, und niemand hätte den Fehler gesehen.
     * Im Dashboard sitzt jemand davor, der etwas damit anfangen kann.
     */
    public function abrufen()
    {
        if (!$this->token->validate('bdp_ferien_abrufen')) {
            $this->flash('error', $this->token->getErrorMessage());
            $this->redirect('/dashboard/einfach_bdp/termine');
        }

        $post = $this->request->request;
        $land = (string) $post->get('land');
        $laender = Ferienquelle::laender();

        if (!isset($laender[$land])) {
            $land = Ferienquelle::land();
        }

        $wechsel = $land !== Ferienquelle::land();
        Ferienquelle::landSetzen($land);

        // Beim Wechsel des Bundeslandes müssen die alten Zeiträume weg —
        // sonst lägen bayerische und hessische Ferien übereinander.
        if ($wechsel) {
            Ferienquelle::apiDatenLeeren();
        }

        $jahr = (int) date('Y');
        $ergebnisse = Ferienquelle::jahreSichern(
            [$jahr - 1, $jahr, $jahr + 1, $jahr + 2],
            $land,
            (bool) $post->get('erzwingen') || $wechsel
        );

        $geholt = 0;
        $fehler = [];

        foreach ($ergebnisse as $j => $e) {
            if ($e['status'] === 'geholt') {
                $geholt += $e['anzahl'];
            } elseif ($e['status'] === 'fehler') {
                $fehler[] = $j . ': ' . $e['meldung'];
            }
        }

        if ($fehler) {
            $this->flash('error', t('Nicht alles hat geklappt. %s', implode(' · ', $fehler)));
        } else {
            $this->flash('success', t('%s Zeiträume für %s übernommen.', $geholt, $laender[$land]));
        }

        $this->redirect('/dashboard/einfach_bdp/termine');
    }

    /**
     * Schulferien von Hand eintragen — für Zeiträume, die die
     * Schnittstelle nicht kennt, etwa bewegliche Ferientage des
     * einzelnen Schulamts.
     */
    public function ferien()
    {
        if (!$this->token->validate('bdp_ferien')) {
            $this->flash('error', $this->token->getErrorMessage());
            $this->redirect('/dashboard/einfach_bdp/termine');
        }

        $post = $this->request->request;
        $titel = trim((string) $post->get('ferienTitel'));
        $von = trim((string) $post->get('ferienVon'));
        $bis = trim((string) $post->get('ferienBis'));

        if ($titel === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $von)) {
            $this->flash('error', t('Ferien brauchen einen Namen und ein Startdatum.'));
            $this->redirect('/dashboard/einfach_bdp/termine');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $bis) || $bis < $von) {
            $bis = $von;
        }

        Speicher::freiSetzen($titel, $von, $bis, 'ferien');
        $this->flash('success', t('Ferien eingetragen.'));
        $this->redirect('/dashboard/einfach_bdp/termine');
    }
}
