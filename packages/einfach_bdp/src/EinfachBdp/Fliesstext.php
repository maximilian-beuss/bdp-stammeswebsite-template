<?php

namespace EinfachBdP;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Einfacher Fließtext für Wiederholfelder.
 *
 * Warum kein Rich-Text-Editor: In einer Liste, deren Zeilen erst im
 * Browser entstehen, müsste für jede neue Zeile ein CKEditor
 * nachgeladen und eingehängt werden. Das geht, ist aber eine
 * Fehlerquelle, und in einem Antragsschritt oder einer
 * Artikelbeschreibung braucht niemand Tabellen und Schriftgrößen.
 *
 * Deshalb: reiner Text, mit zwei Regeln, die man in einem Satz
 * erklären kann.
 *
 *   Leerzeile          neuer Absatz
 *   [Text](Ziel)       Link
 *
 * Alles andere wird maskiert. Wer wirklich HTML braucht, nimmt einen
 * Abschnittsblock daneben.
 */
class Fliesstext
{
    public static function zuHtml($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        // Erst maskieren, dann die eigene Auszeichnung einsetzen. In
        // dieser Reihenfolge kann aus dem Eingabetext kein Markup
        // entstehen, das der Autor nicht gemeint hat.
        $text = h($text);

        $html = '';
        foreach (preg_split('/\R{2,}/', $text) as $absatz) {
            $absatz = trim($absatz);
            if ($absatz === '') {
                continue;
            }

            $absatz = self::links($absatz);
            $absatz = nl2br($absatz, false);
            $html .= '<p>' . $absatz . "</p>\n";
        }

        /*
           Seiteninterne Ziele stehen als {CCM:BASE_URL}/pfad oder
           {CCM:CID_42} im Text und werden erst hier zur Adresse. Als
           fertiger Pfad gespeichert gingen sie kaputt, sobald die
           Installation in einem Unterverzeichnis liegt oder die Seite
           verschoben wird.
        */
        if (strpos($html, '{CCM:') !== false) {
            $html = \Concrete\Core\Editor\LinkAbstractor::translateFrom($html);
        }

        return $html;
    }

    /**
     * [Text](Ziel) wird zum Link. Erlaubt sind http, https, mailto und
     * seiteninterne Ziele — javascript: und data: bleiben Text.
     */
    protected static function links($absatz)
    {
        return preg_replace_callback(
            '/\[([^\]]{1,200})\]\(([^)\s]{1,500})\)/',
            function ($treffer) {
                $beschriftung = $treffer[1];
                $ziel = html_entity_decode($treffer[2], ENT_QUOTES, 'UTF-8');

                if (!self::zielErlaubt($ziel)) {
                    return $treffer[0];
                }

                return '<a href="' . h($ziel) . '"'
                    . (self::istExtern($ziel) ? ' rel="noopener external"' : '')
                    . '>' . $beschriftung . '</a>';
            },
            $absatz
        );
    }

    /**
     * Extern ist alles, was auf einen anderen Rechnernamen zeigt als
     * den, unter dem die Seite gerade läuft.
     */
    public static function istExtern($ziel)
    {
        if (!preg_match('#^https?://#i', $ziel)) {
            return false;
        }

        $fremd = parse_url($ziel, PHP_URL_HOST);
        if (!$fremd) {
            return false;
        }

        $eigen = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $eigen = preg_replace('/:\d+$/', '', $eigen);

        return strcasecmp($fremd, $eigen) !== 0;
    }

    protected static function zielErlaubt($ziel)
    {
        if ($ziel === '') {
            return false;
        }

        if (preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $ziel)) {
            return true;
        }

        // Alles mit einem anderen Schema wird abgelehnt.
        return !preg_match('#^[a-z][a-z0-9+.\-]*:#i', $ziel);
    }
}
