<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Girocode;

$form = Application::getFacadeApplication()->make('helper/form');

/**
 * @var string $ueberschrift
 * @var string $einleitung
 * @var string $bank
 * @var string $empfaenger
 * @var string $iban
 * @var string $bic
 * @var string $zweckHinweis
 * @var int    $zeigeCode
 */

$ibanAnzeige = isset($iban) ? trim(chunk_split((string) $iban, 4, ' ')) : '';

/* Vorschau des Codes: Wer die IBAN ändert, sieht beim nächsten
   Öffnen der Maske, dass sich der Code mitgeändert hat. */
$vorschau = (isset($empfaenger) && isset($iban))
    ? Girocode::svg($empfaenger, $iban, isset($bic) ? $bic : '')
    : '';
?>

<div class="row">
    <div class="col-md-6 form-group">
        <?= $form->label('ueberschrift', t('Überschrift')) ?>
        <?= $form->text('ueberschrift', isset($ueberschrift) ? $ueberschrift : t('Kontodaten'), ['maxlength' => 255]) ?>
    </div>
    <div class="col-md-6 form-group">
        <?= $form->label('bank', t('Bank')) ?>
        <?= $form->text('bank', isset($bank) ? $bank : '', ['maxlength' => 120]) ?>
    </div>
</div>

<div class="form-group">
    <?= $form->label('einleitung', t('Einleitender Satz')) ?>
    <?= $form->textarea('einleitung', isset($einleitung) ? $einleitung : '', ['rows' => 2]) ?>
</div>

<hr>

<h5><?= t('Bankverbindung') ?></h5>

<div class="form-group">
    <?= $form->label('empfaenger', t('Kontoinhaber / Empfänger')) ?>
    <?= $form->text('empfaenger', isset($empfaenger) ? $empfaenger : '', ['maxlength' => 255]) ?>
    <p class="text-muted small mb-0">
        <?= t('Genau so, wie er bei der Bank hinterlegt ist. Für den GiroCode höchstens 70 Zeichen — längere Namen kürzen manche Banking-Apps stillschweigend ab.') ?>
    </p>
</div>

<div class="row">
    <div class="col-md-7 form-group">
        <?= $form->label('iban', t('IBAN')) ?>
        <?= $form->text('iban', $ibanAnzeige, ['maxlength' => 45]) ?>
        <p class="text-muted small mb-0">
            <?= t('Leerzeichen darfst du setzen, sie werden beim Speichern entfernt.') ?>
        </p>
    </div>
    <div class="col-md-5 form-group">
        <?= $form->label('bic', t('BIC')) ?>
        <?= $form->text('bic', isset($bic) ? $bic : '', ['maxlength' => 20]) ?>
        <p class="text-muted small mb-0">
            <?= t('Bei deutschen Konten nicht mehr nötig, schadet aber nicht.') ?>
        </p>
    </div>
</div>

<div class="form-group">
    <?= $form->label('zweckHinweis', t('Hinweis zum Verwendungszweck')) ?>
    <?= $form->textarea('zweckHinweis', isset($zweckHinweis) ? $zweckHinweis : '', ['rows' => 2]) ?>
    <p class="text-muted small mb-0">
        <?= t('Steht auf der Seite unter den Kontodaten. Kommt bewusst nicht in den GiroCode — was jemand überweist und wofür, trägt er selbst ein.') ?>
    </p>
</div>

<hr>

<div class="row">
    <div class="col-md-7">
        <div class="form-check">
            <?= $form->checkbox('zeigeCode', 1, isset($zeigeCode) ? (bool) $zeigeCode : true, ['class' => 'form-check-input']) ?>
            <?= $form->label('zeigeCode', t('GiroCode zum Abscannen anzeigen'), ['class' => 'form-check-label']) ?>
        </div>
        <p class="text-muted small">
            <?= t('Der Code wird aus Empfänger, IBAN und BIC gerechnet — er kann also nicht veralten. Änderst du eine dieser Angaben, ändert er sich mit.') ?>
        </p>
        <?php
        $fehler = isset($controller) && method_exists($controller, 'getCodeFehler')
            ? $controller->getCodeFehler() : '';
        if ($vorschau === '' && $fehler !== '') {
            ?>
            <div class="alert alert-warning py-2 px-3 small mb-0"><?= h($fehler) ?></div>
        <?php } ?>
    </div>
    <div class="col-md-5">
        <?php if ($vorschau !== '') { ?>
            <p class="text-muted small mb-1"><?= t('So sieht er zurzeit aus:') ?></p>
            <div style="max-width:150px"><?= $vorschau ?></div>
        <?php } ?>
    </div>
</div>
