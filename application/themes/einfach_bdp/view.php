<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Ansicht für Einzelseiten des Systems (Login, Registrierung, Suche,
 * Fehlerseiten). Hier gibt es keine Bereiche, nur den Inhalt.
 */

$view->inc('elements/header.php');
?>

<div class="v-seitenkopf auf-dunkel">
    <div class="container">
        <div class="v-seitenkopf__innen">
            <h1><?= isset($pageTitle) && $pageTitle ? h($pageTitle) : h($c->getCollectionName()) ?></h1>
        </div>
    </div>
</div>

<main id="inhalt" class="v-sektion">
    <div class="container">
        <div class="lesebreite v-systeminhalt">
            <?php echo $innerContent; ?>
        </div>
    </div>
</main>

<?php $view->inc('elements/footer.php'); ?>
