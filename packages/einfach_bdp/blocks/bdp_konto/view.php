<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var string $ueberschrift
 * @var string $einleitung
 * @var string $bank
 * @var string $empfaenger
 * @var string $iban
 * @var string $bic
 * @var string $zweckHinweis
 * @var string $codeSvg
 * @var int    $bID
 */

$iban = trim((string) $iban);
$bic = trim((string) $bic);
$empfaenger = trim((string) $empfaenger);

if ($empfaenger === '' || $iban === '') {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-konto__leer">' . t('Noch keine Bankverbindung. Block bearbeiten und Empfänger und IBAN eintragen.') . '</p>';
    }

    return;
}

/* Angezeigt wird die IBAN in Vierergruppen — so liest und vergleicht
   man sie, und genau so steht sie auf jedem Kontoauszug. Kopiert
   wird sie später ohne Leerzeichen. */
$ibanLesbar = trim(chunk_split($iban, 4, ' '));

$id = 'konto-' . (isset($bID) ? (int) $bID : 0);
?>
<div class="v-konto" data-konto>

    <?php if (trim((string) $ueberschrift) !== '') { ?>
        <h2 class="v-konto__titel"><?= h($ueberschrift) ?></h2>
    <?php } ?>

    <?php if (trim((string) $einleitung) !== '') { ?>
        <p class="v-konto__einleitung"><?= nl2br(h($einleitung)) ?></p>
    <?php } ?>

    <dl class="v-konto__daten">
        <?php if (trim((string) $bank) !== '') { ?>
            <div><dt><?= t('Bank') ?></dt><dd><?= h($bank) ?></dd></div>
        <?php } ?>

        <div><dt><?= t('Kontoinhaber') ?></dt><dd><?= h($empfaenger) ?></dd></div>

        <?php
        /* IBAN und BIC sind Knöpfe, die das Fenster öffnen. Ein Knopf
           und kein <a href="#">: Es wird nichts angesteuert, es geht
           etwas auf ✅ WCAG 4.1.2 */
        ?>
        <div>
            <dt><?= t('IBAN') ?></dt>
            <dd>
                <button type="button" class="v-konto__wert" data-konto-auf
                        aria-haspopup="dialog"><?= h($ibanLesbar) ?></button>
            </dd>
        </div>

        <?php if ($bic !== '') { ?>
            <div>
                <dt><?= t('BIC') ?></dt>
                <dd>
                    <button type="button" class="v-konto__wert" data-konto-auf
                            aria-haspopup="dialog"><?= h($bic) ?></button>
                </dd>
            </div>
        <?php } ?>
    </dl>

    <?php if (trim((string) $zweckHinweis) !== '') { ?>
        <p class="v-konto__zweck"><?= nl2br(h($zweckHinweis)) ?></p>
    <?php } ?>

    <p class="v-konto__tipp">
        <?= $codeSvg !== ''
            ? t('Tipp: Klick auf IBAN oder BIC, dann bekommst du einen Code zum Abscannen und die Daten zum Kopieren.')
            : t('Tipp: Klick auf IBAN oder BIC, dann kannst du die Daten kopieren.') ?>
    </p>

    <dialog class="v-konto__fenster" data-konto-fenster aria-labelledby="<?= $id ?>-titel">
        <h3 class="v-konto__fenster-titel" id="<?= $id ?>-titel">
            <?= $codeSvg !== '' ? t('Überweisen ohne Abtippen') : t('Kontodaten kopieren') ?>
        </h3>

        <?php if ($codeSvg !== '') { ?>
            <p class="v-konto__fenster-text">
                <?= t('Öffne deine Banking-App, wähle dort „Überweisung per Foto“ oder „QR-Code scannen“ und halte die Kamera auf den Code. Empfänger, IBAN und BIC stehen dann fertig im Formular. Den Betrag und den Verwendungszweck trägst du selbst ein.') ?>
            </p>

            <?php
            /* Der Code steht direkt im HTML und nicht als Datei: Er
               gehört zu diesem Block, wird aus seinen Feldern
               gerechnet und kann so nicht aus Versehen aus einem
               Zwischenspeicher kommen. */
            ?>
            <div class="v-konto__qr" role="img"
                 aria-label="<?= h(t('GiroCode für eine Überweisung an %s. Dieselben Daten stehen darunter als Text.', $empfaenger)) ?>">
                <?= $codeSvg ?>
            </div>
        <?php } ?>

        <dl class="v-konto__daten v-konto__daten--fenster">
            <?php
            /* Empfänger, IBAN und BIC — jeweils mit eigenem Knopf.
               Der Empfänger war vorher nur Text zum Abtippen, dabei
               ist er das längste der drei Felder. */
            $zeile = function ($beschriftung, $anzeige, $wortlaut) {
                ?>
                <div>
                    <dt><?= h($beschriftung) ?></dt>
                    <dd>
                        <span data-konto-kopierwert<?= $wortlaut ? ' data-konto-wortlaut' : '' ?>><?= h($anzeige) ?></span>
                        <button type="button" class="btn btn--klein" data-konto-kopieren
                                data-konto-name="<?= h($beschriftung) ?>"><?= t('Kopieren') ?></button>
                    </dd>
                </div>
                <?php
            };

            $zeile(t('Empfänger'), $empfaenger, true);
            $zeile(t('IBAN'), $ibanLesbar, false);
            if ($bic !== '') {
                $zeile(t('BIC'), $bic, false);
            }
            ?>
        </dl>

        <p class="v-konto__fenster-schluss">
            <button type="button" class="btn btn--linie" data-konto-zu><?= t('Schließen') ?></button>
            <span class="v-konto__meldung" role="status" data-konto-meldung></span>
        </p>
    </dialog>
</div>
