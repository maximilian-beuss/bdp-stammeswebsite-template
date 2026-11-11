<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array $dateiListe
 * @var int   $zeigeGroesse
 */

if (count($dateiListe) === 0) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-bilder__leer">' . t('Noch keine Datei. Block bearbeiten.') . '</p>';
    }

    return;
}
?>
<ul class="v-downloads">
    <?php foreach ($dateiListe as $d) { ?>
        <li>
            <?php
            /* Typ und Größe stehen im zugänglichen Namen des Links.
               Sichtbar sind sie ohnehin, aber wer den Link per
               Tastatur ansteuert, hört sonst nur „Packliste“ und
               weiß nicht, dass ein 7-MB-PDF dahinterhängt
               ✅ WCAG 3.2.5 */
            $ansage = $d['name'] . ', ' . $d['typ']
                . ($zeigeGroesse && $d['groesse'] !== '' ? ', ' . $d['groesse'] : '');
            ?>
            <a class="v-download" href="<?= h($d['url']) ?>"
               aria-label="<?= h($ansage) ?>">
                <span class="v-download__typ" aria-hidden="true"><?= h($d['typ']) ?></span>
                <span class="v-download__text">
                    <span class="v-download__name"><?= h($d['name']) ?></span>
                    <?php if ($d['beschreibung'] !== '') { ?>
                        <span class="v-download__meta"><?= h($d['beschreibung']) ?></span>
                    <?php } ?>
                </span>
                <?php if ($zeigeGroesse && $d['groesse'] !== '') { ?>
                    <span class="v-download__groesse" aria-hidden="true"><?= h($d['groesse']) ?></span>
                <?php } ?>
            </a>
        </li>
    <?php } ?>
</ul>
