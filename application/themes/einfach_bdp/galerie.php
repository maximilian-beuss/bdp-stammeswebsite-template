<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Galerieseite: voller Breite, Bilder im Masonry-artigen Raster.
 * Jedes Bild trägt sein Info-Kästchen, siehe css/main.css, Abschnitt 09.
 */

$view->inc('elements/header.php');
$view->inc('elements/seitenkopf.php');
?>

<main id="inhalt" class="v-sektion">
    <div class="container">
        <div class="lesebreite" style="margin-bottom:var(--sp-12)">
            <?php
            $bereich = new Area('Main');
            $bereich->display($c);
            ?>
        </div>
    </div>

    <div class="container-weit">
        <?php
        $bereich = new Area('Galerie');
        $bereich->display($c);
        ?>
    </div>
</main>

<?php $view->inc('elements/footer.php'); ?>
