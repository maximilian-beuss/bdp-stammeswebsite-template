<?php

namespace Concrete\Package\EinfachBdp;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Block\BlockType\Set as BlockTypeSet;
use Concrete\Core\Package\Package;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single as SinglePage;
use Concrete\Core\Routing\RouterInterface;
use Concrete\Core\Support\Facade\Application;

/**
 * Paket “einfachBdP” — die Redaktionswerkzeuge der Ortsgruppe.
 *
 * Zweck: Die Seiten bestanden aus großen HTML-Blöcken. Wer keinen
 * HTML-Quelltext lesen kann, konnte die Website faktisch nicht
 * pflegen. Dieses Paket ersetzt die HTML-Klötze durch Blöcke mit
 * ordentlichen Eingabemasken.
 *
 * Ablage der Daten
 * ----------------
 * Wiederholbare Listen (Hero-Folien, Antragsschritte, Produkte,
 * Galeriebilder, Telefonnummern) liegen als JSON in einer Spalte des
 * jeweiligen Blocks. Das spart je Block eine Kindtabelle, und da die
 * Listen nur im Block selbst gelesen werden, gibt es nichts, was man
 * über SQL suchen müsste.
 *
 * Termine sind die Ausnahme: sie stehen in einer eigenen Tabelle
 * (BdPTermine). Sie werden von mehreren Stellen gelesen — vom
 * Kalenderblock, von der iCal-Ausgabe und von der Dashboard-Maske —
 * und müssen nach Datum sortierbar sein.
 */
class Controller extends Package
{
    protected $pkgHandle = 'einfach_bdp';
    protected $appVersionRequired = '9.0.0';
    protected $pkgVersion = '1.9.0';
    protected $pkgAutoloaderRegistries = [
        'src/EinfachBdp' => '\EinfachBdP',
    ];

    /**
     * Blocktypen des Pakets in der Reihenfolge, in der sie im
     * Hinzufügen-Menü stehen sollen.
     */
    const BLOCKTYPEN = [
        'bdp_kopfzeile',
        'bdp_seitenkopf',
        'bdp_hero',
        'bdp_abschnitt',
        'bdp_bild',
        'bdp_video',
        'bdp_kacheln',
        'bdp_eckdaten',
        'bdp_dateien',
        'bdp_glossar',
        'bdp_galerie',
        'bdp_kalender',
        'bdp_karte',
        'bdp_einwilligung',
        'bdp_konto',
        'bdp_aufnahme',
        'bdp_kaemmerei',
        'bdp_telefon',
        'bdp_fuss',
    ];

    public function getPackageName()
    {
        return t('einfachBdP');
    }

    public function getPackageDescription()
    {
        return t('Blöcke und Werkzeuge für BdP-Ortsgruppen: Seitenkopf, Hero, Galerie, Kalender, Aufnahmeantrag, Kämmerei und Telefonnummern.');
    }

    public function install()
    {
        $paket = parent::install();
        $this->installiereInhalte($paket);
    }

    public function upgrade()
    {
        parent::upgrade();
        $this->installiereInhalte($this->getPackageEntity());
    }

    /**
     * Blocktypen und Dashboard-Seite anlegen. Wird auch beim Upgrade
     * durchlaufen, damit neu hinzugekommene Blöcke ohne Neuinstallation
     * erscheinen.
     */
    protected function installiereInhalte($paket)
    {
        $satz = BlockTypeSet::getByHandle('einfach_bdp');
        if (!is_object($satz)) {
            $satz = BlockTypeSet::add('einfach_bdp', t('einfachBdP'), $paket);
        }

        foreach (self::BLOCKTYPEN as $handle) {
            $bt = BlockType::getByHandle($handle);
            if (!is_object($bt)) {
                $bt = BlockType::installBlockType($handle, $paket);
            }
            if (is_object($bt) && is_object($satz)) {
                $satz->addBlockType($bt);
            }
        }

        $this->einzelseite($paket, '/dashboard/einfach_bdp', t('einfachBdP'), t('Termine und Einstellungen'));
        $this->einzelseite($paket, '/dashboard/einfach_bdp/termine', t('Termine'), t('Termine des Stammeskalenders pflegen'));
        $this->einzelseite($paket, '/dashboard/einfach_bdp/planung', t('Jahresplanung'), t('Termine eines Jahres per Ziehen anordnen'));
        $this->einzelseite($paket, '/dashboard/einfach_bdp/stamm', t('Stamm'), t('Stammesname, Ort, Logo und Topbar-Links'));
    }

    /**
     * Legt eine Einzelseite an, falls sie fehlt.
     *
     * SinglePage::add gibt eine vorhandene Seite unverändert zurück,
     * kennt aber kein getByPath — deshalb die Prüfung über Page.
     */
    protected function einzelseite($paket, $pfad, $name, $beschreibung)
    {
        $vorhanden = Page::getByPath($pfad);

        if (is_object($vorhanden) && !$vorhanden->isError()) {
            return $vorhanden;
        }

        $seite = SinglePage::add($pfad, $paket);

        if (is_object($seite) && !$seite->isError()) {
            $seite->update(['cName' => $name, 'cDescription' => $beschreibung]);
        }

        return $seite;
    }

    /**
     * Die iCal-Ausgabe. Liegt bewusst außerhalb des Blocks: ein
     * Kalenderabo darf nicht davon abhängen, auf welcher Seite gerade
     * ein Kalenderblock steht.
     */
    public function on_start()
    {
        /** @var RouterInterface $router */
        $router = $this->app->make(RouterInterface::class);
        $router->get('/kalender/termine.ics', '\EinfachBdP\Kalender\Ausgabe::ical');
        $router->get('/kalender/daten', '\EinfachBdP\Kalender\Ausgabe::daten');

        $this->videoAssets();
    }

    /**
     * Plyr und seine Anbindung als Assets anmelden.
     *
     * Angemeldet heißt nicht geladen: Der Videoblock fordert sie in
     * registerViewAssets() an, und nur dann landen sie im Quelltext.
     *
     * minify und combine bleiben aus. Plyr ist bereits minimiert, und
     * video.js leitet den Pfad zum Symbol-Sprite aus seiner eigenen
     * Adresse ab — in einer zusammengefassten Datei stimmte der nicht
     * mehr.
     */
    protected function videoAssets()
    {
        $liste = AssetList::getInstance();
        $einstellungen = [
            'version' => '3.7.8',
            'position' => Asset::ASSET_POSITION_FOOTER,
            'minify' => false,
            'combine' => false,
        ];

        $liste->register('css', 'einfach_bdp/plyr', 'css/plyr.css', [
            'version' => '3.7.8',
            'position' => Asset::ASSET_POSITION_HEADER,
            'minify' => false,
            'combine' => false,
        ], $this);

        $liste->register('javascript', 'einfach_bdp/plyr', 'js/plyr.js', $einstellungen, $this);
        $liste->register('javascript', 'einfach_bdp/video', 'js/video.js', [
            'version' => '1.0',
            'position' => Asset::ASSET_POSITION_FOOTER,
            'minify' => false,
            'combine' => false,
        ], $this);

        /* Die Einwilligungen. Eigene Datei und nicht in theme.js:
           Karte und Widerrufsliste sind zwei Blöcke desselben
           Pakets, und das Skript soll mit ihnen wandern. */
        $liste->register('javascript', 'einfach_bdp/einwilligung', 'js/einwilligung.js', [
            'version' => '1.0',
            'position' => Asset::ASSET_POSITION_FOOTER,
            'minify' => false,
            'combine' => false,
        ], $this);

        $liste->register('javascript', 'einfach_bdp/konto', 'js/konto.js', [
            'version' => '1.0',
            'position' => Asset::ASSET_POSITION_FOOTER,
            'minify' => false,
            'combine' => false,
        ], $this);
    }
}
