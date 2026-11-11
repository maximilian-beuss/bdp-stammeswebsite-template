<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;

$form = Application::getFacadeApplication()->make('helper/form');

/**
 * @var string $ueberschrift
 * @var string $text
 */
?>

<div class="alert alert-info">
    <?= t('Dieser Block gehört auf die Seite der Datenschutzerklärung. Er listet auf, welchen eingebetteten Inhalten der Besucher zugestimmt hat, und lässt ihn die Zustimmung zurücknehmen.') ?>
</div>

<div class="form-group">
    <?= $form->label('ueberschrift', t('Überschrift')) ?>
    <?= $form->text('ueberschrift', isset($ueberschrift) ? $ueberschrift : '', ['maxlength' => 255]) ?>
</div>

<div class="form-group">
    <?= $form->label('text', t('Text darunter')) ?>
    <?= $form->textarea('text', isset($text) ? $text : '', ['rows' => 4]) ?>
    <p class="text-muted small mb-0">
        <?= t('Die Liste der Einwilligungen und die Knöpfe kommen von selbst dazu.') ?>
    </p>
</div>
