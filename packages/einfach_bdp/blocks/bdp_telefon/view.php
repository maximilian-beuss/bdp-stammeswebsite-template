<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array  $eintraege
 * @var string $sitekey
 * @var bool   $bereit
 * @var string $textVorHtml
 * @var string $textNachHtml
 * @var string $hinweis
 * @var string $ersatzMail
 * @var int    $sechseckAktiviert
 * @var \Concrete\Core\Block\View\BlockView $view
 */

if (count($eintraege) === 0) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p>' . t('Noch keine Nummern. Block bearbeiten.') . '</p>';
    }

    return;
}
?>
<div class="v-telefon"
     data-telefon
     data-telefon-konfig="<?= h($view->action('konfig')) ?>"
     data-telefon-pruefen="<?= h($view->action('pruefen')) ?>"
     data-telefon-nummern="<?= h($view->action('nummern')) ?>"
     <?php if (!empty($sechseckAktiviert)) { ?>data-telefon-sechseck<?php } ?>>

    <?= $textVorHtml ?>

    <div class="v-telefon__mit-js">
        <ul class="v-telefon__liste">
            <?php foreach ($eintraege as $e) { ?>
                <li class="v-telefon__eintrag" data-telefon-eintrag
                    data-name="<?= h($e['name']) ?>"
                    data-stellen="<?= (int) $e['stellen'] ?>">
                    <span class="v-telefon__name">
                        <?= h($e['name']) ?>
                        <?php if ($e['rolle'] !== '') { ?>
                            <span class="v-telefon__rolle"><?= h($e['rolle']) ?></span>
                        <?php } ?>
                    </span>

                    <?php
                    /* Das Kästchen mit der Nummer.

                       Die Vorwahl steht schon da — sie verrät nichts,
                       macht aber aus einem Ladebalken etwas, das
                       erkennbar eine Telefonnummer ist.

                       Der Rest ist leer und wird vom Skript mit
                       Kreisen und Sechsecken gefüllt. Nach dem Lösen
                       des Captchas steht dort die anklickbare Nummer.
                       Ohne Skript bleibt das Feld leer und der
                       E-Mail-Weg darunter greift. */
                    ?>
                    <span class="v-telefon__wert" data-telefon-wert>
                        <?php if ($e['vorwahl'] !== '') { ?>
                            <span class="v-telefon__vorwahl"><?= h($e['vorwahl']) ?></span>
                        <?php } ?>
                        <span class="v-telefon__verdeckt" data-telefon-verdeckt aria-hidden="true"></span>
                        <span class="nur-sr" data-telefon-sr><?= t('Nummer verdeckt. Löse das Captcha, um sie zu sehen.') ?></span>
                    </span>
                </li>
            <?php } ?>
        </ul>

        <?php if ($bereit) { ?>
            <div class="v-telefon__schloss" data-telefon-schloss data-sitekey="<?= h($sitekey) ?>"></div>
            <?php if (trim($hinweis) !== '') { ?>
                <p class="v-telefon__hinweis" data-telefon-hinweis><?= h($hinweis) ?></p>
            <?php } ?>
        <?php } else { ?>
            <?php /* Ohne Schlüsselpaar gäbe es kein Captcha und damit
                     keinen Schutz. Dann bleiben die Nummern weg und der
                     E-Mail-Weg steht da. */ ?>
            <p class="v-telefon__hinweis">
                <?= t('Das Captcha ist noch nicht eingerichtet.') ?>
                <?php if ($ersatzMail !== '') { ?>
                    <a href="mailto:<?= h($ersatzMail) ?>"><?= h($ersatzMail) ?></a>
                <?php } ?>
            </p>
        <?php } ?>

        <p class="v-telefon__meldung" role="status" data-telefon-meldung></p>

        <?= $textNachHtml ?>
    </div>

    <p class="v-telefon__ersatz">
        <?= t('Ohne JavaScript geht das nicht.') ?>
        <?php if ($ersatzMail !== '') { ?>
            <?= t('Schreib uns an') ?>
            <a href="mailto:<?= h($ersatzMail) ?>"><?= h($ersatzMail) ?></a>,
            <?= t('dann bekommst du die Nummern per Mail.') ?>
        <?php } ?>
    </p>
</div>
