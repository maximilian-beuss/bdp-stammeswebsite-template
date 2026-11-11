<?php

namespace Concrete\Package\EinfachBdp\Block\BdPDateien;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use EinfachBdP\Werkzeug;

/**
 * Eine Liste von Dateien zum Herunterladen.
 *
 * Dateityp und Größe kommen aus dem Dateimanager und werden nicht
 * eingetippt. Das ist nicht nur bequemer, es ist auch das einzige
 * Verfahren, das dauerhaft stimmt: Wer eine Datei austauscht, müsste
 * sonst daran denken, „1,2 MB“ von Hand zu korrigieren.
 *
 * Die Angabe der Größe vor dem Klick ist kein Beiwerk. Wer im
 * Funkloch steht, entscheidet danach, ob er jetzt lädt oder später
 * ✅ WCAG 3.2.5 — der Link kündigt an, was passiert.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPDateien';
    protected $btInterfaceWidth = 720;
    protected $btInterfaceHeight = 560;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Dateien zum Herunterladen');
    }

    public function getBlockTypeDescription()
    {
        return t('Liste von Dateien mit Typ, Größe und Beschreibung. Typ und Größe kommen aus dem Dateimanager.');
    }

    public function add()
    {
        $this->set('zeigeGroesse', 1);
        $this->set('dateiListe', []);
    }

    public function edit()
    {
        $this->set('dateiListe', Werkzeug::liste($this->dateien));
    }

    public function view()
    {
        $dateien = [];

        foreach (Werkzeug::liste($this->dateien) as $d) {
            $version = Werkzeug::datei(Werkzeug::feld($d, 'dateifID', 0));

            if (!$version) {
                continue;
            }

            $name = trim(Werkzeug::feld($d, 'name'));
            $endung = strtoupper((string) $version->getExtension());

            $dateien[] = [
                'name' => $name !== '' ? $name : (string) $version->getFileName(),
                'beschreibung' => trim(Werkzeug::feld($d, 'beschreibung')),
                'url' => (string) $version->getDownloadURL(),
                'typ' => $endung !== '' ? $endung : 'Datei',
                'groesse' => $this->groesse((int) $version->getFullSize()),
            ];
        }

        $this->set('dateiListe', $dateien);
    }

    /**
     * Bytes in etwas, das man vorlesen kann.
     */
    protected function groesse($bytes)
    {
        if ($bytes <= 0) {
            return '';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 0, ',', '.') . ' kB';
        }

        return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
    }

    public function getSearchableContent()
    {
        $teile = [];
        foreach (Werkzeug::liste($this->dateien) as $d) {
            $teile[] = Werkzeug::feld($d, 'name') . ' ' . Werkzeug::feld($d, 'beschreibung');
        }

        return implode(' ', $teile);
    }

    public function save($daten)
    {
        parent::save([
            'dateien' => Werkzeug::listeSpeichern($daten['dateien'] ?? [], ['dateifID']),
            'zeigeGroesse' => empty($daten['zeigeGroesse']) ? 0 : 1,
        ]);
    }
}
