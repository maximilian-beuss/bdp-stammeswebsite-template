<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Hauptnavigation.
 *
 * Baut zwei Ebenen direkt aus dem Seitenbaum: Ebene 1 als waagerechte
 * Leiste, Ebene 2 als Karte darunter. Der aktive Punkt bekommt die
 * gestrichelte gelbe Linie — das CD-Element „gestrichelte Linie“.
 * Seiten mit gesetztem Attribut „exclude_nav“ bleiben außen vor.
 */

use Concrete\Core\Page\Page;
use Concrete\Core\Page\PageList;

$aktuelleSeite = Page::getCurrentPage();
$aktuellerPfad = $aktuelleSeite && !$aktuelleSeite->isError() ? (string) $aktuelleSeite->getCollectionPath() : '';

$kinderVon = static function ($elternID) {
    $liste = new PageList();
    $liste->filterByParentID($elternID);
    $liste->sortByDisplayOrder();

    $ergebnis = [];
    foreach ($liste->getResults() as $seite) {
        if ($seite->getAttribute('exclude_nav')) {
            continue;
        }
        if ($seite->isSystemPage()) {
            continue;
        }
        $ergebnis[] = $seite;
    }

    return $ergebnis;
};

$startseite = Page::getByID(Page::getHomePageID());
$hauptpunkte = $kinderVon($startseite->getCollectionID());

/*
   Für Angemeldete klappt die Navigation aus.

   Grund: Die Unterpunkte liegen als Karte über dem Inhalt und
   erscheinen beim Überfahren. In der angemeldeten Ansicht legt
   Concrete eigene Ebenen darüber — die Werkzeugleiste, im
   Bearbeitungsmodus zusätzlich Bereichsrahmen und Blockmenüs. Deren
   z-index lässt sich vom Theme aus nicht sinnvoll unterbieten oder
   überbieten: Mal liegt das Menü über dem Blockmenü, mal darunter,
   und in beiden Fällen ist eines davon nicht bedienbar.

   Statt daran zu drehen, verschwindet das Problem: Wer angemeldet
   ist, sieht den ganzen Seitenbaum offen. Nichts klappt auf, nichts
   überlagert etwas, und man kommt mit einem Klick auf jede Seite.

   Bewusst an der Anmeldung und nicht am Bearbeitungsmodus: Auch beim
   bloßen Durchsehen der Seite hängt die Werkzeugleiste oben, und man
   springt beim Redigieren ständig zwischen Seiten hin und her. Für
   Besucher bleibt alles, wie es war.
*/
$angemeldet = \Concrete\Core\Support\Facade\Application::getFacadeApplication()
    ->make('Concrete\Core\User\User')->isRegistered();
?>

<nav class="v-nav<?= $angemeldet ? ' v-nav--baum' : '' ?>" id="hauptnavigation" aria-label="Hauptnavigation">

    <?php if ($angemeldet) { ?>
        <p class="v-nav__notiz">
            In der Redaktionsansicht ist die gesamte Navigation eingeblendet.
        </p>
    <?php } ?>

    <ul class="v-nav__liste">
        <?php foreach ($hauptpunkte as $punkt) {
            $pfad = (string) $punkt->getCollectionPath();
            $unterpunkte = $kinderVon($punkt->getCollectionID());
            $istAktiv = $aktuellerPfad === $pfad
                || ($pfad !== '' && strpos($aktuellerPfad, $pfad . '/') === 0);
            ?>
            <li class="v-nav__punkt">
                <a class="v-nav__link<?= $istAktiv ? ' ist-aktiv' : '' ?>"
                   href="<?= h($punkt->getCollectionLink()) ?>"
                   <?= $aktuellerPfad === $pfad ? 'aria-current="page"' : '' ?>>
                    <?= h($punkt->getCollectionName()) ?>
                </a>

                <?php if ($unterpunkte) { ?>
                    <?php // Nur Überschriften. Die Seitenbeschreibung steht auf der
                          // Zielseite und auf den Kacheln der Übersicht; im Menü
                          // hat sie nur den Blick aufgehalten. ?>
                    <ul class="v-nav__unter">
                        <?php foreach ($unterpunkte as $unter) { ?>
                            <li>
                                <a href="<?= h($unter->getCollectionLink()) ?>"
                                   <?= $aktuellerPfad === (string) $unter->getCollectionPath() ? 'aria-current="page"' : '' ?>>
                                    <span class="v-nav__unter-titel"><?= h($unter->getCollectionName()) ?></span>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </li>
        <?php } ?>
    </ul>
</nav>
