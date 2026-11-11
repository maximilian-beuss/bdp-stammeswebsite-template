<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Volle Breite: Seitenkopf, danach ein Inhaltsbereich ohne
 * Lesebreiten-Begrenzung — für Übersichtsseiten mit Kacheln,
 * Galerien oder breiten Bildern.
 */

$view->inc('elements/header.php');
$view->inc('elements/seitenkopf.php');
?>

<main id="inhalt" class="v-sektion">
    <?php
    $bereich = new Area('Main');
    $bereich->enableGridContainer();
    $bereich->display($c);
    ?>
</main>

<?php $view->inc('elements/footer.php'); ?>
