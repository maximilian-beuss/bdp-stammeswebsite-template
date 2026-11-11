<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var string $ueberschrift
 * @var string $text
 */

$ueberschrift = trim((string) $ueberschrift);
$text = trim((string) $text);
?>
<div class="v-einwilligung" data-einwilligung-verwaltung>

    <?php if ($ueberschrift !== '') { ?>
        <h2 class="v-einwilligung__titel"><?= h($ueberschrift) ?></h2>
    <?php } ?>

    <?php if ($text !== '') { ?>
        <p class="v-einwilligung__text"><?= nl2br(h($text)) ?></p>
    <?php } ?>

    <?php
    /* Die Liste baut das Skript. Bis dahin steht hier der Satz für
       alle, die kein JavaScript haben — für sie gibt es nichts zu
       widerrufen, weil ohne Skript auch nichts nachgeladen wurde. */
    ?>
    <ul class="v-einwilligung__liste" data-einwilligung-liste hidden></ul>

    <p class="v-einwilligung__ohnejs" data-einwilligung-ohnejs>
        <?= t('Dein Browser führt kein JavaScript aus. Dann lädt diese Website auch keine externen Inhalte nach — es gibt nichts zurückzunehmen.') ?>
    </p>

    <p class="v-einwilligung__meldung" role="status" data-einwilligung-meldung></p>
</div>
