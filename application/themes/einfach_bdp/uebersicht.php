<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Übersichtsseite für einen Bereich (Gruppen, Medien, Über uns …).
 *
 * Die Kacheln der Unterseiten standen früher fest hier drin. Jetzt
 * stehen sie als Block „Kacheln“ im Bereich „Volle Breite“ — mit der
 * Einstellung „Automatisch aus den Unterseiten“ verhält sich das
 * genauso wie vorher, lässt sich aber ergänzen, umsortieren, auf
 * manuelle Kacheln umstellen oder ganz weglassen.
 *
 * Was hier bleibt, ist das Gerüst: Kopf, Lesespalte, breiter Bereich.
 */

$view->inc('elements/header.php');
$view->inc('elements/seitenkopf.php');
?>

<main id="inhalt" class="v-sektion">
    <div class="container">
        <div class="lesebreite">
            <?php
            $bereich = new Area('Main');
            $bereich->display($c);
            ?>
        </div>
    </div>

    <div class="container">
        <?php
        $bereich = new Area('Volle Breite');
        $bereich->display($c);
        ?>
    </div>
</main>

<?php $view->inc('elements/footer.php'); ?>
