<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;

$form = Application::getFacadeApplication()->make('helper/form');
$dateiWaehler = Application::getFacadeApplication()->make('helper/concrete/asset_library');

/**
 * @var array $platzhalter
 * @var mixed $bildDatei
 */
?>

<div class="form-group">
    <?= $form->label('titel', t('Überschrift')) ?>
    <?= $form->text('titel', isset($titel) ? $titel : '{Titel}', ['maxlength' => 255]) ?>
</div>

<div class="form-group">
    <?= $form->label('untertitel', t('Untertitel')) ?>
    <?= $form->textarea('untertitel', isset($untertitel) ? $untertitel : '', ['rows' => 3]) ?>
</div>

<div class="form-group">
    <div class="form-check">
        <?= $form->checkbox('krumen', 1, isset($krumen) ? (bool) $krumen : true, ['class' => 'form-check-input']) ?>
        <?= $form->label('krumen', t('Brotkrumen anzeigen (Ortsgruppe &gt; …)'), ['class' => 'form-check-label']) ?>
    </div>
</div>

<div class="form-group">
    <?= $form->label('stil', t('Darstellung')) ?>
    <?= $form->select('stil', [
        'blau' => t('Blaue Fläche'),
        'bild' => t('Hintergrundbild'),
    ], isset($stil) ? $stil : 'blau') ?>
</div>

<div class="form-group">
    <?= $form->label('bildfID', t('Hintergrundbild')) ?>
    <?= $dateiWaehler->image('bildfID', 'bildfID', t('Bild wählen'), isset($bildDatei) ? $bildDatei : null) ?>
    <p class="text-muted small"><?= t('Wird nur bei der Darstellung „Hintergrundbild“ benutzt.') ?></p>
</div>

<hr>

<?php
/*
   Die Platzhaltertabelle steht direkt in der Maske und nicht in einer
   Hilfeseite. Wer den Kopf bearbeitet, hat die Frage „was kann ich hier
   eintragen“ genau jetzt.
*/
?>
<details>
    <summary><strong><?= t('Platzhalter, die du verwenden kannst') ?></strong></summary>
    <table class="table table-sm mt-2">
        <tbody>
        <?php foreach ($platzhalter as $name => $erklaerung) { ?>
            <tr>
                <td style="white-space:nowrap"><code>{<?= h($name) ?>}</code></td>
                <td><?= h($erklaerung) ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <p class="text-muted small">
        <?= t('Groß- und Kleinschreibung ist egal. Ein Platzhalter, den es nicht gibt, bleibt unverändert stehen — so siehst du sofort, wenn du dich vertippt hast.') ?>
    </p>
</details>
