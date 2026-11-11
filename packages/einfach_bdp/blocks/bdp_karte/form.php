<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;

$form = Application::getFacadeApplication()->make('helper/form');

/**
 * @var string $einbettung
 * @var string $dienst
 * @var string $titel
 * @var string $anschrift
 * @var string $verweis
 * @var int    $hoehe
 * @var string $referrer
 */
?>

<div class="alert alert-info">
    <?= t('Die Karte lädt erst, wenn der Besucher zustimmt. Die Zustimmung merkt sich sein Browser; zurücknehmen kann er sie auf der Seite der Datenschutzerklärung.') ?>
</div>

<div class="form-group">
    <?= $form->label('einbettung', t('Einbettungsadresse')) ?>
    <?= $form->textarea('einbettung', isset($einbettung) ? $einbettung : '', ['rows' => 3]) ?>
    <p class="text-muted small mb-0">
        <?= t('Bei Google Maps: „Teilen“ → „Karte einbetten“ → die Adresse aus dem src-Attribut des iframes. Sie beginnt mit https://www.google.com/maps/embed.') ?>
    </p>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <?= $form->label('titel', t('Beschriftung der Karte')) ?>
        <?= $form->text('titel', isset($titel) ? $titel : '', ['maxlength' => 255]) ?>
        <p class="text-muted small mb-0">
            <?= t('Screenreader lesen sie als Namen des Kartenrahmens vor, z. B. „Karte: Das blaue Haus in Schwalbach“.') ?>
        </p>
    </div>
    <div class="col-md-6 form-group">
        <?= $form->label('dienst', t('Anbieter')) ?>
        <?= $form->text('dienst', isset($dienst) ? $dienst : 'Google Maps', ['maxlength' => 80]) ?>
        <p class="text-muted small mb-0">
            <?= t('Steht im Hinweistext vor der Karte. Muss zu dem passen, was in der Datenschutzerklärung steht.') ?>
        </p>
    </div>
</div>

<div class="form-group">
    <?= $form->label('anschrift', t('Anschrift im Platzhalter')) ?>
    <?= $form->textarea('anschrift', isset($anschrift) ? $anschrift : '', ['rows' => 4]) ?>
    <p class="text-muted small mb-0">
        <?= t('Steht anstelle der Karte, solange niemand zugestimmt hat. Ohne sie ist die Kontaktseite für alle leer, die die Karte nicht laden wollen — deshalb bitte ausfüllen.') ?>
    </p>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <?= $form->label('verweis', t('Verweis zum Anbieter')) ?>
        <?= $form->text('verweis', isset($verweis) ? $verweis : '') ?>
        <p class="text-muted small mb-0">
            <?= t('Optional. Führt direkt zur Karte beim Anbieter, für alle, die nicht einbetten lassen wollen.') ?>
        </p>
    </div>
    <div class="col-md-3 form-group">
        <?= $form->label('hoehe', t('Höhe in Pixeln')) ?>
        <?= $form->number('hoehe', isset($hoehe) ? $hoehe : 420, ['min' => 200, 'max' => 900, 'step' => 10]) ?>
    </div>
    <div class="col-md-3 form-group">
        <?= $form->label('referrer', t('Herkunftsangabe')) ?>
        <?= $form->select('referrer', [
            'strict-origin-when-cross-origin' => t('Nur die Domain'),
            'origin' => t('Nur die Domain, immer'),
            'no-referrer' => t('Gar keine'),
        ], isset($referrer) ? $referrer : 'strict-origin-when-cross-origin') ?>
        <p class="text-muted small mb-0">
            <?= t('„Gar keine“ ist am sparsamsten, aber Google Maps liefert dann nichts aus.') ?>
        </p>
    </div>
</div>
