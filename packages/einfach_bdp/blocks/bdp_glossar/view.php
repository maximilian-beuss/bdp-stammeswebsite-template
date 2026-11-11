<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array $gruppen   Buchstabe => Einträge
 * @var int   $anzahl
 * @var array $alphabet
 * @var int   $zeigeSuche
 * @var int   $zeigeAlphabet
 */

if ($anzahl === 0) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-bilder__leer">' . t('Noch kein Begriff. Block bearbeiten.') . '</p>';
    }

    return;
}

$kennung = 'glossar-' . (isset($bID) ? (int) $bID : 0);
?>

<?php if ($zeigeSuche) { ?>
    <div class="v-suche">
        <label class="nur-sr" for="<?= h($kennung) ?>-suche"><?= t('Im Glossar suchen') ?></label>
        <input type="search" id="<?= h($kennung) ?>-suche" data-glossar-suche
               placeholder="<?= t('Begriff eingeben, z. B. Kohte') ?>">
    </div>
    <p class="klein" data-glossar-treffer role="status"></p>
<?php } ?>

<?php if ($zeigeAlphabet) { ?>
    <?php
    /* Buchstaben ohne Eintrag bleiben stehen, aber ohne Link. Sie
       ganz wegzulassen ließe die Leiste bei jeder Änderung anders
       aussehen; so bleibt sie ruhig und zeigt zugleich, wo es nichts
       gibt. aria-disabled statt disabled, weil ein <a> das Attribut
       nicht kennt ✅ */
    ?>
    <nav class="v-alphabet" aria-label="<?= t('Zu einem Buchstaben springen') ?>">
        <?php foreach ($alphabet as $b) { ?>
            <?php if (isset($gruppen[$b])) { ?>
                <a href="#<?= h($kennung) ?>-<?= h($b) ?>"><?= h($b) ?></a>
            <?php } else { ?>
                <a aria-disabled="true" tabindex="-1"><?= h($b) ?></a>
            <?php } ?>
        <?php } ?>
    </nav>
<?php } ?>

<?php foreach ($gruppen as $buchstabe => $eintraege) { ?>
    <section class="v-glossar__gruppe" aria-labelledby="<?= h($kennung) ?>-<?= h($buchstabe) ?>">
        <h2 class="v-glossar__buchstabe" id="<?= h($kennung) ?>-<?= h($buchstabe) ?>">
            <?= h($buchstabe) ?>
        </h2>

        <?php foreach ($eintraege as $e) { ?>
            <div class="v-glossar__eintrag">
                <h3 class="v-glossar__begriff"><?= h($e['begriff']) ?></h3>
                <div class="v-glossar__text"><?= $e['erklaerung'] ?></div>
            </div>
        <?php } ?>
    </section>
<?php } ?>
