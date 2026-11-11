<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Gruppenseite: Meute, Sippe, Runde, Thing, Stafü-Team.
 *
 * Die Eckdaten standen früher fest hier drin und zogen ihre Werte aus
 * Seitenattributen. Wer eine Zeile ergänzen wollte, musste diese Datei
 * ändern. Jetzt sind sie ein Block „Eckdaten" im Bereich „Main" —
 * Zeilen frei, Rahmenfarbe automatisch nach der Stufe.
 *
 * Aufbau:
 *   Volle Breite  oben, volle Containerbreite — für gruppeneigene
 *                 Sektionen, die nicht in die Lesebreite passen
 *                 (z. B. eine Vorstellungsleiste oder ein Zitatblock)
 *   Main          Lesespalte, enthält den Eckdaten-Block plus Fließtext
 *   Bilder        unten, über die ganze Breite, mit Grid für Raster
 */

$view->inc('elements/header.php');
$view->inc('elements/seitenkopf.php');
?>

<main id="inhalt" class="v-sektion">
    <?php
    // Volle Breite über der Lesebreite. Erscheint nur, wenn etwas drin
    // steht (oder im Bearbeitungsmodus) — sonst bleibt die Seite so
    // schmal wie zuvor, ohne leere Hülse.
    $bereich = new Area('Volle Breite');
    if ($bereich->getTotalBlocksInArea($c) > 0 || $c->isEditMode()) { ?>
        <div class="container v-breitbereich">
            <?php $bereich->display($c); ?>
        </div>
    <?php } ?>

    <div class="container">
        <div class="lesebreite">
            <?php
            $bereich = new Area('Main');
            $bereich->display($c);
            ?>
        </div>
    </div>

    <?php
    $bereich = new Area('Bilder');
    $bereich->enableGridContainer();
    $bereich->display($c);
    ?>
</main>

<?php $view->inc('elements/footer.php'); ?>
