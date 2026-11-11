<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array  $bilderListe
 * @var bool   $istReihe
 * @var string $breite
 * @var string $format
 * @var int    $zeigeText
 */

if (count($bilderListe) === 0) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-bilder__leer">' . t('Noch kein Bild. Block bearbeiten.') . '</p>';
    }

    return;
}

$aussen = 'v-bilder v-bilder--' . h($breite)
    . ($istReihe ? ' v-bilder--reihe' : '')
    . ($format !== 'frei' ? ' v-bilder--' . h($format) : '');
?>
<div class="<?= $aussen ?>">
    <?php foreach ($bilderListe as $b) { ?>
        <figure class="v-bilder__stueck">
            <img src="<?= h($b['url']) ?>"
                 <?= $b['breite'] > 0 ? 'width="' . (int) $b['breite'] . '"' : '' ?>
                 <?= $b['hoehe'] > 0 ? 'height="' . (int) $b['hoehe'] . '"' : '' ?>
                 alt="<?= h($b['alt']) ?>"
                 loading="lazy">

            <?php if ($zeigeText && ($b['text'] !== '' || $b['quelle'] !== '')) { ?>
                <figcaption>
                    <?= h($b['text']) ?>
                    <?php if ($b['quelle'] !== '') { ?>
                        <cite><?= h($b['quelle']) ?></cite>
                    <?php } ?>
                </figcaption>
            <?php } ?>
        </figure>
    <?php } ?>
</div>
