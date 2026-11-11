<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array  $gruppen  Rubrik => Liste von Artikeln
 * @var string $mail
 */

if (count($gruppen) === 0) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-kaemmerei__leer">' . t('Noch keine Artikel. Block bearbeiten und Artikel hinzufügen.') . '</p>';
    }

    return;
}
?>
<?php foreach ($gruppen as $rubrik => $artikel) { ?>
    <?php if ($rubrik !== '') { ?>
        <h3 class="mit-strich"><?= h($rubrik) ?></h3>
    <?php } ?>

    <ul class="v-waren">
        <?php foreach ($artikel as $a) { ?>
            <li class="v-ware<?= $a['zustand'] === 'gebraucht' ? ' v-ware--gebraucht' : '' ?>">

                <div class="v-ware__bild">
                    <?php if ($a['bild'] !== '') { ?>
                        <img src="<?= h($a['bild']) ?>" alt="<?= h($a['name']) ?>" loading="lazy">
                    <?php } else { ?>
                        <?php /* Kein Bild: die Fläche bleibt, damit die Karten
                                 im Raster gleich hoch bleiben. Das Sechseck
                                 ist rein dekorativ. */ ?>
                        <span class="v-ware__ersatz" aria-hidden="true"></span>
                    <?php } ?>
                </div>

                <div class="v-ware__text">
                    <h4 class="v-ware__name"><?= h($a['name']) ?></h4>

                    <?php if ($a['zustand'] === 'gebraucht') { ?>
                        <span class="v-etikett v-etikett--gebraucht"><?= t('gebraucht') ?></span>
                    <?php } ?>

                    <?php if ($a['beschreibung'] !== '') { ?>
                        <p class="v-ware__info"><?= h($a['beschreibung']) ?></p>
                    <?php } ?>

                    <?php if ($a['hinweis'] !== '') { ?>
                        <p class="v-ware__hinweis"><?= h($a['hinweis']) ?></p>
                    <?php } ?>

                    <div class="v-ware__fuss">
                        <?php if ($a['preis'] !== '') { ?>
                            <span class="v-ware__preis"><?= h($a['preis']) ?></span>
                        <?php } ?>
                        <?php if ($mail !== '') { ?>
                            <a class="v-ware__bestellen"
                               href="mailto:<?= h($mail) ?>?subject=<?= rawurlencode('Kämmerei: ' . $a['name']) ?>">
                                <?= t('Anfragen') ?>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </li>
        <?php } ?>
    </ul>
<?php } ?>
