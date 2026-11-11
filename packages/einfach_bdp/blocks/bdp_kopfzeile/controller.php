<?php

namespace Concrete\Package\EinfachBdp\Block\BdPKopfzeile;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Page\Page;
use EinfachBdP\Werkzeug;

/**
 * Kopfzeile: Topbar, Logo, Stammesname und Ort.
 *
 * Diese Felder lagen zuvor in application/config/stamm.php — einer
 * PHP-Datei, die nur per FTP zu ändern war. Als globaler Block sind
 * sie über die normale Bearbeitungsoberfläche zugänglich.
 *
 * Der Block liegt im globalen Bereich „Kopfzeile", der in header.php
 * eingebunden wird. Global heißt: einmal gepflegt, überall geändert.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPKopfzeile';
    protected $btInterfaceWidth = 760;
    protected $btInterfaceHeight = 640;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Kopfzeile');
    }

    public function getBlockTypeDescription()
    {
        return t('Stammesname, Ort, Logo und Topbar-Links der oberen Navigation.');
    }

    public function add()
    {
        $this->set('stammesname', 'Musterstamm');
        $this->set('ort', 'Musterort');
        $this->set('logoDateifID', 0);
        $this->set('logoAlt', 'Zurück zur Startseite');
        $this->set('topbarLinksListe', [
            ['text' => 'Mitmachen', 'href' => '/mitmachen', 'extern' => ''],
            ['text' => 'Instagram', 'href' => 'https://www.instagram.com/musterstamm/', 'extern' => '1'],
        ]);
        $this->set('topbarRechtsListe', [
            ['text' => 'Zum BdP', 'href' => 'https://www.pfadfinden.de/', 'extern' => '1'],
        ]);
        $this->set('bildDatei', null);
    }

    public function edit()
    {
        $this->set('topbarLinksListe', Werkzeug::liste($this->topbarLinks));
        $this->set('topbarRechtsListe', Werkzeug::liste($this->topbarRechts));
        $this->set('bildDatei', Werkzeug::datei($this->logoDateifID));
    }

    public function view()
    {
        $links = [];
        foreach (Werkzeug::liste($this->topbarLinks) as $eintrag) {
            $text = trim(Werkzeug::feld($eintrag, 'text'));
            $href = trim(Werkzeug::feld($eintrag, 'href'));
            if ($text !== '' && $href !== '') {
                $links[] = [
                    'text' => $text,
                    'href' => Werkzeug::link($href),
                    'extern' => !empty($eintrag['extern']),
                ];
            }
        }

        $rechts = [];
        foreach (Werkzeug::liste($this->topbarRechts) as $eintrag) {
            $text = trim(Werkzeug::feld($eintrag, 'text'));
            $href = trim(Werkzeug::feld($eintrag, 'href'));
            if ($text !== '' && $href !== '') {
                $rechts[] = [
                    'text' => $text,
                    'href' => Werkzeug::link($href),
                    'extern' => !empty($eintrag['extern']),
                ];
            }
        }

        $logoUrl = Werkzeug::bildUrl((int) $this->logoDateifID);

        // Wenn kein Logo im Dateimanager gewählt, die BdP-Lilie aus dem Theme
        if ($logoUrl === '') {
            $seite = Page::getCurrentPage();
            $theme = is_object($seite) && !$seite->isError() ? $seite->getCollectionThemeObject() : null;
            $themePfad = is_object($theme) ? $theme->getThemeURL() : DIR_REL . '/application/themes/einfach_bdp';
            $logoUrl = $themePfad . '/images/bdp-klilie-blau-gelb.svg';
        }

        $this->set('topbarLinksListe', $links);
        $this->set('topbarRechtsListe', $rechts);
        $this->set('logoUrl', $logoUrl);
        $this->set('logoAltText', trim((string) $this->logoAlt) ?: 'Zurück zur Startseite');
        $this->set('stammesname', trim((string) $this->stammesname) ?: 'Musterstamm');
        $this->set('ort', trim((string) $this->ort));
    }

    public function save($daten)
    {
        parent::save([
            'stammesname' => trim((string) ($daten['stammesname'] ?? 'Musterstamm')),
            'ort' => trim((string) ($daten['ort'] ?? '')),
            'logoDateifID' => max(0, (int) ($daten['logoDateifID'] ?? 0)),
            'logoAlt' => trim((string) ($daten['logoAlt'] ?? 'Zurück zur Startseite')),
            'topbarLinks' => Werkzeug::listeSpeichern($daten['topbarLinks'] ?? [], ['text', 'href']),
            'topbarRechts' => Werkzeug::listeSpeichern($daten['topbarRechts'] ?? [], ['text', 'href']),
        ]);
    }
}
