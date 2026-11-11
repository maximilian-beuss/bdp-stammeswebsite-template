<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array  $kachelListe
 * @var string $spalten
 * @var int    $zeigeBild
 * @var int    $zeigeText
 * @var string $knopfText
 */

if (count($kachelListe) === 0) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-raster__leer">' . t('Keine Kacheln. Block bearbeiten — oder es gibt noch keine Unterseiten.') . '</p>';
    }

    return;
}
?>
<div class="v-raster<?= $spalten !== 'auto' ? ' v-raster--' . (int) $spalten : '' ?>">
    <?php foreach ($kachelListe as $k) { ?>
        <?php
        /* Die ganze Kachel ist der Link. Der Knopf unten ist deshalb
           kein zweiter Link, sondern nur die sichtbare Zusage, dass
           hier etwas passiert — sonst hätte jede Kachel zwei Ziele
           mit demselben Zweck in der Tabreihenfolge ✅ WCAG 2.4.4 */
        $hatZiel = $k['ziel'] !== '';
        $tag = $hatZiel ? 'a' : 'div';
        ?>
        <<?= $tag ?> class="v-kachel v-auftritt"<?= $hatZiel ? ' href="' . h($k['ziel']) . '"' : '' ?>>
            <?php if ($zeigeBild && $k['bild'] !== '') { ?>
                <div class="v-kachel__bild">
                    <img src="<?= h($k['bild']) ?>" alt="" loading="lazy">
                </div>
            <?php } ?>

            <div class="v-kachel__inhalt">
                <?php if (!empty($k['datumIso']) && $k['etikett'] !== '') { ?>
                    <time class="v-etikett" datetime="<?= h($k['datumIso']) ?>"><?= h($k['etikett']) ?></time>
                <?php } elseif ($k['etikett'] !== '') { ?>
                    <span class="v-etikett"><?= h($k['etikett']) ?></span>
                <?php } ?>

                <h2 class="v-kachel__titel"><?= h($k['titel']) ?></h2>

                <?php if ($zeigeText && $k['text'] !== '') { ?>
                    <p class="v-kachel__text"><?= h($k['text']) ?></p>
                <?php } ?>

                <?php if ($hatZiel && trim($knopfText) !== '') { ?>
                    <span class="v-kachel__fuss pfeil-link"><?= h($knopfText) ?></span>
                <?php } ?>
            </div>
        </<?= $tag ?>>
    <?php } ?>
</div>
