<?php

/**
 * Einstellungen des Themes „einfachBdP”.
 *
 * hero — die Folien des Bildwechslers auf der Startseite.
 *        Reihenfolge = Anzeigereihenfolge. Die Überschriften folgen dem
 *        Slogan-Baukasten des BdP-CD: „Einfach” + ein Wort, mit Punkt.
 *        Gelb ist hier Textfarbe, weil der Grund ein abgedunkeltes Foto
 *        ist. Auf Weiß wäre Wölflingsgelb mit 1,45:1 unzulässig.
 */

$bilder = @include DIR_APPLICATION . '/config/einfachBdP_bilder.php';
if (!is_array($bilder)) {
    $bilder = [];
}

$folie = static function ($nr, $titel, $text, array $links = []) use ($bilder) {
    if (!isset($bilder[$nr])) {
        return null;
    }

    return [
        // DIR_REL ist das Unterverzeichnis, in dem die Installation liegt
        'bild' => DIR_REL . $bilder[$nr]['url'],
        'titel' => $titel,
        'text' => $text,
        'links' => $links,
    ];
};

return [
    'hero' => array_values(array_filter([

        $folie(9, 'Einfach raus.',
            'Morgens aufgewacht, und da war dieser reißende Bach. So etwas plant man nicht, man läuft ihm über den Weg.',
            [
                ['text' => 'Mitmachen', 'ziel' => DIR_REL . '/mitmachen', 'stil' => 'btn--gelb'],
                ['text' => 'Unsere Gruppen', 'ziel' => DIR_REL . '/gruppen', 'stil' => 'btn--hell'],
            ]),

        $folie(26, 'Einfach zusammen.',
            'Der BdP hat Stämme in ganz Deutschland. Auf dem Bundeslager 2022 waren über 5.000 da, wir mittendrin.'),

        $folie(38, 'Einfach unterwegs.',
            'Sonnenaufgang über Tschechien nach einer etwas zu kurzen Nacht. Das Foto ist unbearbeitet, es war wirklich so goldorange.'),

        $folie(1, 'Einfach draußen.',
            'Eine Hochkohte mit Dreibein, drumherum diverse Konstruktionen. Unsere Schwarzzelte bauen wir selbst auf.'),

        $folie(35, 'Einfach singen.',
            'Volkslieder, Pfadfinderlieder und auch mal ein paar Popsongs. Solange Rasmus in die Saiten haut, ist Stimmung da.',
            [
                ['text' => 'Zum Liederschuh', 'ziel' => DIR_REL . '/medien/liederschuh', 'stil' => 'btn--gelb'],
            ]),

    ])),
];
