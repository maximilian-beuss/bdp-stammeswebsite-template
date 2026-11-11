<?php

namespace EinfachBdP;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Config;

/**
 * Platzhalter in Texten von Blöcken.
 *
 * Damit muss man den Titel einer Seite nicht zweimal pflegen: Im Feld
 * steht {Titel}, ausgeliefert wird der Seitenname. Ändert sich der Name
 * der Seite, ändert sich der Kopf mit.
 *
 * Geschrieben wird {Name} in geschweiften Klammern, Groß- und
 * Kleinschreibung ist egal. Ein unbekannter Platzhalter bleibt stehen,
 * statt zu verschwinden — so sieht man auf der Seite sofort, dass man
 * sich vertippt hat, statt sich über eine leere Zeile zu wundern.
 */
class Platzhalter
{
    /**
     * Die Liste für die Blockeinstellungen: Name => Erklärung.
     * Genau diese Tabelle wird in den Masken angezeigt.
     */
    public static function katalog()
    {
        return [
            'Titel' => t('Name dieser Seite'),
            'Untertitel' => t('Kurzbeschreibung der Seite (Seiteneigenschaften)'),
            'Berichtsdatum' => t('Datum des Berichts (bei Berichtsseiten)'),
            'Pfad' => t('Baumstruktur bis hierher, z. B. „Ortsgruppe > Kontakt“'),
            'Elternseite' => t('Name der übergeordneten Seite'),
            'Seitenname' => t('Name der Website'),
            'Jahr' => t('Laufendes Jahr, vierstellig'),
            'Datum' => t('Heutiges Datum, z. B. 15.08.2026'),
            'Geaendert' => t('Datum der letzten Änderung dieser Seite'),
            'Url' => t('Adresse dieser Seite'),
        ];
    }

    /**
     * Ersetzt alle Platzhalter in einem Text.
     *
     * @param string    $text
     * @param Page|null $seite Fällt auf die aktuelle Seite zurück
     */
    public static function ersetze($text, ?Page $seite = null)
    {
        $text = (string) $text;
        if ($text === '' || strpos($text, '{') === false) {
            return $text;
        }

        $werte = self::werte($seite);

        return preg_replace_callback('/\{([A-Za-zÄÖÜäöü]+)\}/u', function ($treffer) use ($werte) {
            $schluessel = mb_strtolower($treffer[1]);

            // Unbekannt: unverändert stehen lassen, siehe Klassenkommentar.
            return array_key_exists($schluessel, $werte) ? $werte[$schluessel] : $treffer[0];
        }, $text);
    }

    /**
     * Die aufgelösten Werte, Schlüssel klein geschrieben.
     */
    protected static function werte(?Page $seite = null)
    {
        if ($seite === null) {
            $seite = Page::getCurrentPage();
        }

        $werte = [
            'seitenname' => (string) Config::get('concrete.site', 'Musterstamm'),
            'jahr' => date('Y'),
            'datum' => date('d.m.Y'),
            'titel' => '',
            'untertitel' => '',
            'berichtsdatum' => '',
            'pfad' => '',
            'elternseite' => '',
            'geaendert' => '',
            'url' => '',
        ];

        if (!is_object($seite) || $seite->isError()) {
            return $werte;
        }

        $werte['titel'] = (string) $seite->getCollectionName();
        $untertitel = trim((string) $seite->getCollectionDescription());
        if ($untertitel === '') {
            $untertitel = trim((string) $seite->getAttribute('kurzbeschreibung'));
        }
        $werte['untertitel'] = $untertitel;
        $werte['berichtsdatum'] = Werkzeug::datumDeutsch($seite->getAttribute('berichtsdatum'))['text'];
        $werte['url'] = (string) $seite->getCollectionLink();

        $datum = $seite->getCollectionDateLastModified();
        if ($datum) {
            $werte['geaendert'] = date('d.m.Y', strtotime($datum));
        }

        $kette = self::kette($seite);
        $werte['pfad'] = implode(' > ', $kette);
        $werte['elternseite'] = count($kette) > 1 ? $kette[count($kette) - 2] : '';

        return $werte;
    }

    /**
     * Namen von der Startseite bis zur übergebenen Seite.
     * Wird auch vom Seitenkopf für die Brotkrumen benutzt.
     */
    public static function kette(Page $seite)
    {
        $namen = [];
        $lauf = $seite;
        $bremse = 0;

        while (is_object($lauf) && !$lauf->isError() && $bremse++ < 20) {
            array_unshift($namen, (string) $lauf->getCollectionName());
            if ((int) $lauf->getCollectionID() <= 1) {
                break;
            }
            $lauf = Page::getByID($lauf->getCollectionParentID());
        }

        return $namen;
    }
}
