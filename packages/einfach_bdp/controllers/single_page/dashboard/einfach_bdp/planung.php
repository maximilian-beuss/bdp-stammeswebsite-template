<?php

namespace Concrete\Package\EinfachBdp\Controller\SinglePage\Dashboard\EinfachBdp;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Page\Controller\DashboardPageController;
use Symfony\Component\HttpFoundation\JsonResponse;
use EinfachBdP\Kalender\Speicher;
use EinfachBdP\Kalender\Stufen;

/**
 * Jahresplanung.
 *
 * Der Ablauf, für den das gebaut ist: Man sammelt erst, was im
 * kommenden Jahr stattfinden soll, mit Titel und ungefährer Dauer.
 * Danach zieht man die Vorhaben ins Jahr und schiebt sie so lange hin
 * und her, bis nichts mehr kollidiert — bis das Sommerlager in den
 * Ferien liegt, das Landeslager nicht auf die Stammesfahrt fällt und
 * zwischen zwei Wochenenden noch Luft ist.
 *
 * Solange etwas Entwurf ist, sieht es niemand außer der
 * Stammesführung: weder im Kalender auf der Website noch im iCal-Abo.
 * Das ist der Punkt an der Sache — man soll ausprobieren können, ohne
 * dass bei allen Abonnenten das Telefon vibriert.
 *
 * Die Änderungen laufen über kleine JSON-Aufrufe und nicht über ein
 * Formular mit Absenden. Beim Ausprobieren zieht man ein Vorhaben
 * zwanzigmal hin und her; ein Neuladen der Seite nach jedem Zug wäre
 * unbrauchbar.
 */
class Planung extends DashboardPageController
{
    public function view($jahr = null)
    {
        $jahr = (int) $jahr;

        if ($jahr < 2000 || $jahr > 2100) {
            $jahr = (int) date('Y');
        }

        $this->set('jahr', $jahr);
        $this->set('stufen', Stufen::katalog());
        $this->set('termine', Speicher::jahr($jahr));
        $this->set('ideen', Speicher::ideen());
        $this->set('frei', Speicher::freieTage($jahr . '-01-01', $jahr . '-12-31'));
    }

    /* ==============================================================
       Ideenspeicher
       ============================================================== */

    public function idee_anlegen()
    {
        if (!$this->token->validate('bdp_planung')) {
            return $this->nein($this->token->getErrorMessage());
        }

        $post = $this->request->request;
        $titel = trim((string) $post->get('titel'));

        if ($titel === '') {
            return $this->nein(t('Die Idee braucht einen Titel.'));
        }

        $ideeID = Speicher::ideeSichern([
            'titel' => $titel,
            'tage' => (int) $post->get('tage'),
            'stufe' => (string) $post->get('stufe'),
            'notiz' => (string) $post->get('notiz'),
        ]);

        return $this->ja(['ideeID' => $ideeID]);
    }

    public function idee_loeschen()
    {
        if (!$this->token->validate('bdp_planung')) {
            return $this->nein($this->token->getErrorMessage());
        }

        Speicher::ideeLoeschen((int) $this->request->request->get('ideeID'));

        return $this->ja();
    }

    /**
     * Idee ins Jahr ziehen: wird zum Entwurf mit Datum.
     */
    public function idee_ablegen()
    {
        if (!$this->token->validate('bdp_planung')) {
            return $this->nein($this->token->getErrorMessage());
        }

        $post = $this->request->request;
        $terminID = Speicher::ideeAblegen(
            (int) $post->get('ideeID'),
            (string) $post->get('tag')
        );

        if ($terminID === false) {
            return $this->nein(t('Das hat nicht geklappt.'));
        }

        return $this->ja(['terminID' => $terminID]);
    }

    /* ==============================================================
       Termine im Jahr
       ============================================================== */

    public function verschieben()
    {
        if (!$this->token->validate('bdp_planung')) {
            return $this->nein($this->token->getErrorMessage());
        }

        $post = $this->request->request;

        if (!Speicher::verschieben((int) $post->get('terminID'), (string) $post->get('tag'))) {
            return $this->nein(t('Das Datum war nicht zu gebrauchen.'));
        }

        return $this->ja();
    }

    public function dauer_aendern()
    {
        if (!$this->token->validate('bdp_planung')) {
            return $this->nein($this->token->getErrorMessage());
        }

        $post = $this->request->request;
        $termin = Speicher::einer((int) $post->get('terminID'));

        if (!$termin) {
            return $this->nein(t('Der Termin ist nicht mehr da.'));
        }

        $tage = max(1, min(365, (int) $post->get('tage')));
        $beginn = substr($termin['beginn'], 0, 10);
        $ende = date('Y-m-d', strtotime('+' . ($tage - 1) . ' days', strtotime($beginn)));

        Speicher::sichern([
            'titel' => $termin['titel'],
            'beginn' => $termin['beginn'],
            'ende' => $ende . ' 23:59:00',
            'ganztaegig' => $termin['ganztaegig'],
            'stufe' => $termin['stufe'],
            'ort' => $termin['ort'],
            'beschreibung' => $termin['beschreibung'],
            'link' => $termin['link'],
            'entwurf' => $termin['entwurf'],
        ], (int) $termin['terminID']);

        return $this->ja();
    }

    public function veroeffentlichen()
    {
        if (!$this->token->validate('bdp_planung')) {
            return $this->nein($this->token->getErrorMessage());
        }

        $post = $this->request->request;
        Speicher::entwurfSetzen((int) $post->get('terminID'), !$post->get('entwurf'));

        return $this->ja();
    }

    public function zurueck_in_speicher()
    {
        if (!$this->token->validate('bdp_planung')) {
            return $this->nein($this->token->getErrorMessage());
        }

        if (!Speicher::zurueckInDenSpeicher((int) $this->request->request->get('terminID'))) {
            return $this->nein(t('Der Termin ist nicht mehr da.'));
        }

        return $this->ja();
    }

    public function termin_loeschen()
    {
        if (!$this->token->validate('bdp_planung')) {
            return $this->nein($this->token->getErrorMessage());
        }

        Speicher::loeschen((int) $this->request->request->get('terminID'));

        return $this->ja();
    }

    /**
     * Alle Entwürfe eines Jahres auf einmal veröffentlichen.
     */
    public function alle_veroeffentlichen()
    {
        if (!$this->token->validate('bdp_planung')) {
            $this->flash('error', $this->token->getErrorMessage());
            $this->redirect('/dashboard/einfach_bdp/planung');
        }

        $jahr = (int) $this->request->request->get('jahr');
        $n = 0;

        foreach (Speicher::jahr($jahr) as $t) {
            if (!empty($t['entwurf'])) {
                Speicher::entwurfSetzen((int) $t['terminID'], false);
                ++$n;
            }
        }

        $this->flash('success', t('%s Entwürfe veröffentlicht.', $n));
        $this->redirect('/dashboard/einfach_bdp/planung', $jahr);
    }

    /* ==============================================================
       Antworten
       ============================================================== */

    protected function ja(array $daten = [])
    {
        return new JsonResponse(['ok' => true] + $daten);
    }

    protected function nein($meldung)
    {
        return new JsonResponse(['ok' => false, 'meldung' => (string) $meldung], 400);
    }
}
