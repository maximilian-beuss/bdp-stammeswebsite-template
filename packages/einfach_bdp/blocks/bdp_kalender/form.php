<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url;

$form = Application::getFacadeApplication()->make('helper/form');

/** @var array $stufenKatalog */
/** @var array $gewaehlteStufen */
$gewaehlteStufen = isset($gewaehlteStufen) ? $gewaehlteStufen : [];
?>

<div class="alert alert-info">
    <?= t('Die Termine selbst pflegst du im Dashboard unter <strong>einfachBdP → Termine</strong>. Dieser Block bestimmt nur, wie sie hier dargestellt werden.') ?>
</div>

<div class="form-group">
    <?= $form->label('ansicht', t('Ansicht beim Öffnen')) ?>
    <?= $form->select('ansicht', [
        'monat' => t('Monat'),
        'halbjahr' => t('Halbjahr'),
        'jahr' => t('Ganzes Jahr'),
        'liste' => t('Nur Terminliste'),
    ], isset($ansicht) ? $ansicht : 'monat') ?>
</div>

<div class="form-group">
    <label class="form-label"><?= t('Nur diese Stufen zeigen') ?></label>
    <?php foreach ($stufenKatalog as $handle => $stufe) { ?>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="stufe-<?= h($handle) ?>"
                   name="stufen[]" value="<?= h($handle) ?>"
                   <?= in_array($handle, $gewaehlteStufen, true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="stufe-<?= h($handle) ?>">
                <span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:<?= h($stufe['farbe']) ?>;vertical-align:-1px;margin-right:6px"></span>
                <?= h($stufe['name']) ?>
            </label>
        </div>
    <?php } ?>
    <p class="text-muted small mb-0">
        <?= t('Nichts angekreuzt oder alles angekreuzt bedeutet dasselbe: alle Termine.') ?>
    </p>
</div>

<hr>

<div class="form-check">
    <?= $form->checkbox('zeigeFrei', 1, isset($zeigeFrei) ? (bool) $zeigeFrei : true, ['class' => 'form-check-input']) ?>
    <?= $form->label('zeigeFrei', t('Schulferien und Feiertage hinterlegen'), ['class' => 'form-check-label']) ?>
</div>

<div class="form-check">
    <?= $form->checkbox('zeigeUmschalter', 1, isset($zeigeUmschalter) ? (bool) $zeigeUmschalter : true, ['class' => 'form-check-input']) ?>
    <?= $form->label('zeigeUmschalter', t('Besucher dürfen die Ansicht wechseln'), ['class' => 'form-check-label']) ?>
</div>

<div class=”form-check”>
    <?= $form->checkbox('zeigeAbo', 1, isset($zeigeAbo) ? (bool) $zeigeAbo : true, ['class' => 'form-check-input']) ?>
    <?= $form->label('zeigeAbo', t('Knopf „Kalender abonnieren” zeigen'), ['class' => 'form-check-label']) ?>
</div>

<div class=”form-check”>
    <?= $form->checkbox('sechseckAktiviert', 1, isset($sechseckAktiviert) ? (bool) $sechseckAktiviert : false, ['class' => 'form-check-input']) ?>
    <?= $form->label('sechseckAktiviert', t('Sechseck-Feature: Legende mit Sechsecken statt Kreisen'), ['class' => 'form-check-label']) ?>
    <p class=”text-muted small mb-0”><?= t('Standardmäßig deaktiviert. Das Sechseck ist ein Stammessymbol — nur aktivieren, wenn es zum eigenen Auftritt passt.') ?></p>
</div>

<p class="text-muted small mt-3">
    <?= t('Abo-Adresse:') ?> <code><?= h((string) Url::to('/kalender/termine.ics')) ?></code>
</p>
