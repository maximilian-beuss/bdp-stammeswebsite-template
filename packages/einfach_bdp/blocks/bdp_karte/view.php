<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var string $einbettung
 * @var string $dienst
 * @var string $titel
 * @var string $anschrift
 * @var string $verweis
 * @var int    $hoeheWert
 * @var string $referrer
 */

if (trim((string) $einbettung) === '') {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-einbettung__leer">' . t('Noch keine Kartenadresse. Block bearbeiten und die Einbettungsadresse eintragen.') . '</p>';
    }

    return;
}

$name = trim((string) $titel) !== '' ? $titel : t('Karte');
?>
<?php
/* data-einwilligung-quelle und nicht data-einbettung: Auf den
   alten Attributnamen hörte auch die inzwischen entfernte Mechanik
   im Theme, und dann bearbeiteten zwei Skripte denselben Kasten. */
?>
<div class="v-einbettung v-karte"
     data-einwilligung="karte"
     data-einwilligung-quelle="<?= h($einbettung) ?>"
     data-titel="<?= h($name) ?>"
     data-hoehe="<?= (int) $hoeheWert ?>"
     data-referrer="<?= h($referrer) ?>"
     style="--v-karte-hoehe: <?= (int) $hoeheWert ?>px">

    <?php
    /* Kurz halten. Der Platzhalter steht auf der Kontaktseite, wo
       jemand eine Adresse sucht — nicht auf der Datenschutzseite.
       Ein Satz, zwei Knöpfe, fertig; alles Weitere steht verlinkt
       in der Datenschutzerklärung. */
    ?>
    <div class="v-einbettung__platzhalter" data-einwilligung-platzhalter>
        <?php if (trim((string) $anschrift) !== '') { ?>
            <address class="v-karte__anschrift"><?= nl2br(h($anschrift)) ?></address>
        <?php } ?>

        <p class="v-einbettung__knoepfe">
            <?php
            /* Ohne JavaScript bleibt der Knopf verborgen — er könnte
               nichts laden. Der Link daneben funktioniert immer
               ✅ WCAG 3.2.4 */
            ?>
            <button type="button" class="btn btn--linie v-einbettung__laden"
                    data-einwilligung-ja hidden>
                <?= t('Karte laden') ?>
            </button>

            <?php if (trim((string) $verweis) !== '') { ?>
                <a class="btn btn--linie" href="<?= h($verweis) ?>"
                   target="_blank" rel="noopener external">
                    <?= t('In %s öffnen', h($dienst)) ?>
                    <span class="v-einbettung__neu"><?= t('(neuer Tab)') ?></span>
                </a>
            <?php } ?>
        </p>

        <p class="v-einbettung__hinweis">
            <?= t('Beim Laden geht deine IP-Adresse an %s.', h($dienst)) ?>
            <a href="<?= h((string) \Concrete\Core\Support\Facade\Url::to('/datenschutz')) ?>"><?= t('Datenschutz') ?></a>
        </p>
    </div>
</div>
