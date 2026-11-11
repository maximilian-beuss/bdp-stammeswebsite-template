<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Der blaue Kopf über einer Unterseite.
 *
 * Er ist ein Bereich und kein festes Stück Template mehr. Darin steht
 * normalerweise ein Block „Seitenkopf“, in dem Titel und Untertitel
 * frei stehen und Platzhalter wie {Titel} oder {Pfad} erlaubt sind.
 *
 * Die alte, feste Ausgabe steht weiter darunter — als Rückfallebene
 * für Seiten, in deren Bereich noch kein Block liegt. Ohne sie hätte
 * eine neu angelegte Seite gar keinen Kopf, und der Fehler fiele erst
 * beim Betrachten auf.
 *
 * Optionale Variablen der Rückfallebene:
 *   $kopfBild  Pfad zu einem Hintergrundbild
 *   $kopfText  Text unter der Überschrift
 */

use Concrete\Core\Page\Page;

$kopfBereich = new Area('Seitenkopf');
$kopfGefuellt = $kopfBereich->getTotalBlocksInArea($c) > 0;

if ($kopfGefuellt || (is_object($c) && $c->isEditMode())) {
    $kopfBereich->display($c);

    if ($kopfGefuellt) {
        return;
    }
}

/* ---------------------------------------------------------------
   Rückfallebene
   --------------------------------------------------------------- */

$seite = Page::getCurrentPage();
$titel = $seite ? $seite->getCollectionName() : '';
$text = isset($kopfText) ? $kopfText : ($seite ? trim((string) $seite->getCollectionDescription()) : '');
if ($text === '' && $seite) {
    $text = trim((string) $seite->getAttribute('kurzbeschreibung'));
}
$berichtsdatum = $seite ? \EinfachBdP\Werkzeug::datumDeutsch($seite->getAttribute('berichtsdatum')) : ['text' => '', 'iso' => ''];
$bild = isset($kopfBild) ? $kopfBild : null;

$krumen = [];
$lauf = $seite;
while ($lauf && !$lauf->isError() && (int) $lauf->getCollectionID() > 1) {
    $eltern = Page::getByID($lauf->getCollectionParentID());
    if (!$eltern || $eltern->isError()) {
        break;
    }
    array_unshift($krumen, $eltern);
    $lauf = $eltern;
}
?>

<div class="v-seitenkopf auf-dunkel<?= $bild ? ' v-seitenkopf--bild' : '' ?>">
    <?php if ($bild) { ?>
        <img class="v-seitenkopf__bild" src="<?= h($bild) ?>" alt="" loading="eager">
    <?php } ?>

    <?php // Ein Anschnitt, am Rand — dasselbe Element wie im Hero ?>
    <span class="v-keil" aria-hidden="true"></span>

    <div class="container">
        <div class="v-seitenkopf__innen">

            <?php if (count($krumen) > 0) { ?>
                <nav class="v-krumen" aria-label="Wo du gerade bist">
                    <ol>
                        <?php foreach ($krumen as $k) { ?>
                            <li><a href="<?= h($k->getCollectionLink()) ?>"><?= h($k->getCollectionName()) ?></a></li>
                        <?php } ?>
                        <li aria-current="page"><?= h($titel) ?></li>
                    </ol>
                </nav>
            <?php } ?>

            <?php if ($berichtsdatum['text'] !== '') { ?>
                <p class="v-seitenkopf__datum">
                    <time class="v-etikett" datetime="<?= h($berichtsdatum['iso']) ?>"><?= h($berichtsdatum['text']) ?></time>
                </p>
            <?php } ?>

            <h1><?= h($titel) ?></h1>
            <?php if ($text !== '') { ?>
                <p class="lead lesebreite"><?= h($text) ?></p>
            <?php } ?>

        </div>
    </div>
</div>
