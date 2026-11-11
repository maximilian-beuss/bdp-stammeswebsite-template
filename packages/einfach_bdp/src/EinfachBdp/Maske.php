<?php

namespace EinfachBdP;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Bausteine für die Blockmasken.
 *
 * Die Masken der Listen-Blöcke sehen alle gleich aus. Statt das Markup
 * fünfmal zu kopieren, steht es hier einmal.
 */
class Maske
{
    protected static $geladen = false;

    /**
     * Stylesheet und Skript der Wiederholfelder. Mehrfacher Aufruf
     * schadet nicht — zwei Blöcke derselben Sorte auf einer Seite sind
     * im Bearbeitungsmodus durchaus möglich.
     */
    public static function assets()
    {
        if (self::$geladen) {
            return;
        }
        self::$geladen = true;

        $basis = DIR_REL . '/packages/einfach_bdp';
        echo '<link rel="stylesheet" href="' . $basis . '/css/repeater.css?v=1">' . "\n";
        echo '<script src="' . $basis . '/js/repeater.js?v=2"></script>' . "\n";
    }

    /**
     * Bildauswahl für eine Zeile eines Wiederholfelds.
     *
     * @param string $name     Feldname, z. B. folien[__I__][bildfID]
     * @param int    $fID      bisher gewählte Datei
     * @param string $leerText Beschriftung, solange nichts gewählt ist
     */
    public static function bildKnopf($name, $fID, $leerText = 'Bild wählen')
    {
        $datei = Werkzeug::datei($fID);
        $miniatur = '';

        if ($datei) {
            $url = $datei->getThumbnailURL('file_manager_listing');
            $miniatur = '<img src="' . h($url) . '" alt="">';
        }

        $html = '<div data-rep-bildfeld>';
        $html .= '<button type="button" class="v-rep__bild" data-rep-bild'
            . ' data-leer-text="' . h($leerText) . '">'
            . ($miniatur !== '' ? $miniatur : h($leerText))
            . '</button>';
        $html .= '<input type="hidden" name="' . h($name) . '" value="' . (int) $fID . '" data-rep-bild-wert>';
        $html .= '<button type="button" class="btn btn-sm btn-link px-0" data-rep-bild-weg>'
            . t('Bild entfernen') . '</button>';
        $html .= '</div>';

        echo $html;
    }

    /**
     * Kopfzeile einer Zeile: Nummer, Titelvorschau, Werkzeuge.
     */
    public static function zeilenkopf($bezeichnung)
    {
        echo '<div class="v-rep__kopf">'
            . '<button type="button" class="v-rep__titel" data-rep-klapp>'
            . h($bezeichnung) . ' <span data-rep-nummer></span>'
            . '</button>'
            . '<span class="v-rep__werkzeug">'
            . '<button type="button" class="btn btn-sm btn-outline-secondary" data-rep-hoch title="' . t('Nach oben') . '">&uarr;</button>'
            . '<button type="button" class="btn btn-sm btn-outline-secondary" data-rep-runter title="' . t('Nach unten') . '">&darr;</button>'
            . '<button type="button" class="btn btn-sm btn-outline-danger" data-rep-weg title="' . t('Entfernen') . '">&times;</button>'
            . '</span>'
            . '</div>';
    }

    /**
     * Aufklappbare Tabelle der Platzhalter.
     */
    public static function platzhalterhilfe()
    {
        echo '<details class="v-rep__hilfe mt-3"><summary><strong>'
            . t('Platzhalter, die du verwenden kannst') . '</strong></summary>';
        echo '<table class="table table-sm mt-2"><tbody>';
        foreach (Platzhalter::katalog() as $name => $erklaerung) {
            echo '<tr><td><code>{' . h($name) . '}</code></td><td>' . h($erklaerung) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '<p class="text-muted small">'
            . t('Groß- und Kleinschreibung ist egal. Ein Platzhalter, den es nicht gibt, bleibt unverändert stehen — so siehst du sofort, wenn du dich vertippt hast.')
            . '</p></details>';
    }
}
