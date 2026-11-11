<?php

/**
 * Stammes-Konfiguration — statischer Fallback
 *
 * Stammesname, Ort, Logo und Topbar-Links werden jetzt über den Block
 * „bdp_kopfzeile" im CMS-Bereich „Kopfzeile" verwaltet (Dashboard →
 * Seiten → Bereiche → Kopfzeile). Diese Datei greift nur noch, wenn
 * dort kein Block eingetragen ist.
 *
 * LOGO
 *   Lege eine SVG- oder PNG-Datei unter application/themes/einfach_bdp/images/
 *   ab und trage hier den Dateinamen ein. Leer lassen für das BdP-Standardlogo
 *   (bdp-klilie-blau-gelb.svg).
 *
 * TOPBAR
 *   Jeder Eintrag ist ein Array mit 'text', 'href' und optional
 *   'extern' (true = öffnet in neuem Tab mit rel="noopener external").
 *   Maximal vier Links pro Seite empfohlen. Leer lassen für keine Topbar.
 */

return [

    // Anzeigename des Stamms — erscheint im Logo und im Browser-Tab
    'stammesname' => 'Musterstamm',

    // Stadt oder Ort — steht klein unter dem Namen im Logo
    'ort' => 'Musterort',

    // Logo-Dateiname unter application/themes/einfach_bdp/images/
    // '' = BdP-Lilie als Standardlogo
    'logo_datei' => '',

    // Alt-Text für das Logo-Bild
    'logo_alt' => 'Zurück zur Startseite',

    // Links in der Topbar links (max. 3 empfohlen)
    'topbar_links' => [
        ['text' => 'Mitmachen', 'href' => '/mitmachen'],
        ['text' => 'Instagram',  'href' => 'https://www.instagram.com/musterstamm/', 'extern' => true],
    ],

    // Links in der Topbar rechts (max. 2 empfohlen)
    'topbar_rechts' => [
        ['text' => 'Zum BdP', 'href' => 'https://www.pfadfinden.de/', 'extern' => true],
    ],
];
