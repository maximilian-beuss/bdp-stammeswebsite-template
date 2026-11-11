<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Standard-Inhaltsseite: blauer Seitenkopf, danach der Inhalt in einer
 * begrenzten Lesespalte. Für Artikelseiten die richtige Wahl.
 *
 * Die Spalte ist etwas breiter als die reine Lesebreite von 68 ch, damit
 * ein Bild daneben Platz hat und der Text daneben trotzdem lesbar bleibt.
 * Über die volle Containerbreite von 1200 px zu setzen wäre für Fließtext
 * deutlich zu weit.
 */

$view->inc('elements/header.php');
$view->inc('elements/seitenkopf.php');
?>

<main id="inhalt" class="v-sektion">
    <div class="container">
        <?php
        $bereich = new Area('Seitenkopf Inhalt');
        $bereich->enableGridContainer();
        $bereich->display($c);
        ?>

        <div class="v-lesespalte">
            <?php
            $bereich = new Area('Main');
            $bereich->display($c);
            ?>
        </div>
    </div>

    <?php
    // Siehe artikel.php: Containerbreite statt randlos.
    $bereich = new Area('Volle Breite');
    if ($bereich->getTotalBlocksInArea($c) > 0 || $c->isEditMode()) { ?>
        <div class="container v-breitbereich">
            <?php $bereich->display($c); ?>
        </div>
    <?php } ?>
</main>

<?php $view->inc('elements/footer.php'); ?>
