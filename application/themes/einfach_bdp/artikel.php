<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Artikelseite: blauer Seitenkopf, danach der Inhalt in einer begrenzten
 * Lesespalte. Für alles, was überwiegend aus Fließtext besteht.
 *
 * Die Spalte ist mit 50rem etwas breiter als die reine Lesebreite von
 * 68 ch, damit ein Bild seitlich mitlaufen kann und der Text daneben
 * lesbar bleibt. Über die volle Containerbreite von 1200 px zu setzen
 * wäre für Fließtext deutlich zu weit: das wären rund 130 Zeichen je
 * Zeile.
 *
 * Seiten, die die ganze Breite brauchen (Terminliste, Downloads,
 * Pfadi-Alphabet, Galerie), bleiben auf full.php.
 */

$view->inc('elements/header.php');
$view->inc('elements/seitenkopf.php');
?>

<main id="inhalt" class="v-sektion">
    <div class="container">
        <div class="v-lesespalte">
            <?php
            $bereich = new Area('Main');
            $bereich->display($c);
            ?>
        </div>
    </div>

    <?php
    /*
       Alles, was breiter sein soll als die Lesespalte: Raster, Tabellen,
       Bilderreihen. Der Container bleibt, damit der Inhalt nicht am
       Fensterrand klebt — „volle Breite“ heißt volle Containerbreite,
       nicht randlos.
    */
    $bereich = new Area('Volle Breite');
    if ($bereich->getTotalBlocksInArea($c) > 0 || $c->isEditMode()) { ?>
        <div class="container v-breitbereich">
            <?php $bereich->display($c); ?>
        </div>
    <?php } ?>
</main>

<?php $view->inc('elements/footer.php'); ?>
