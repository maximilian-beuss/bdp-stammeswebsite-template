<?php

namespace Concrete\Package\EinfachBdp\Controller\SinglePage\Dashboard\EinfachBdp;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Stack\Stack;
use EinfachBdP\Werkzeug;

/**
 * Stammeseinstellungen: Stammesname, Ort, Logo und Topbar-Links.
 *
 * Liest und schreibt direkt den bdp_kopfzeile-Block im globalen Bereich
 * „Kopfzeile" — keine eigene Tabelle, keine zweite Config-Datei.
 */
class Stamm extends DashboardPageController
{
    public function view()
    {
        $ctrl = $this->getKopfzeileController();

        $this->set('stammesname',      $ctrl ? (string) $ctrl->stammesname   : 'Musterstamm');
        $this->set('ort',              $ctrl ? (string) $ctrl->ort            : '');
        $this->set('logoDateifID',     $ctrl ? (int)    $ctrl->logoDateifID   : 0);
        $this->set('logoAlt',          $ctrl ? (string) $ctrl->logoAlt        : 'Zurück zur Startseite');
        $this->set('topbarLinksListe', Werkzeug::liste($ctrl ? $ctrl->topbarLinks  : '[]'));
        $this->set('topbarRechtsListe',Werkzeug::liste($ctrl ? $ctrl->topbarRechts : '[]'));
        $this->set('bildDatei',        Werkzeug::datei($ctrl ? (int) $ctrl->logoDateifID : 0));
    }

    public function sichern()
    {
        if (!$this->token->validate('bdp_stamm_sichern')) {
            $this->error->add($this->token->getErrorMessage());
        }

        if ($this->error->has()) {
            $this->view();
            return;
        }

        $post = $this->request->request;

        $daten = [
            'stammesname'  => trim((string) $post->get('stammesname', 'Musterstamm')),
            'ort'          => trim((string) $post->get('ort', '')),
            'logoDateifID' => max(0, (int) $post->get('logoDateifID', 0)),
            'logoAlt'      => trim((string) $post->get('logoAlt', 'Zurück zur Startseite')),
            'topbarLinks'  => Werkzeug::listeSpeichern($post->all()['topbarLinks'] ?? [], ['text', 'href']),
            'topbarRechts' => Werkzeug::listeSpeichern($post->all()['topbarRechts'] ?? [], ['text', 'href']),
        ];

        $block = $this->getKopfzeileBlock();

        if ($block) {
            $block->update($daten);
        } else {
            $this->error->add(t('Kein Kopfzeile-Block gefunden. Bitte zuerst den Block im globalen Bereich „Kopfzeile" anlegen.'));
            $this->view();
            return;
        }

        $this->flash('success', t('Einstellungen gespeichert.'));
        $this->redirect('/dashboard/einfach_bdp/stamm');
    }

    private function getKopfzeileBlock(): ?\Concrete\Core\Block\Block
    {
        $stack = Stack::getByName('Kopfzeile');
        if (!$stack || $stack->isError()) {
            return null;
        }

        foreach ($stack->getBlocks('Stacks') as $bl) {
            if ($bl->getBlockTypeHandle() === 'bdp_kopfzeile') {
                return $bl;
            }
        }

        return null;
    }

    private function getKopfzeileController()
    {
        $block = $this->getKopfzeileBlock();
        return $block ? $block->getController() : null;
    }
}
