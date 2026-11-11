<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/** @var array $eintragListe */
$eintragListe = isset($eintragListe) ? $eintragListe : [];

$zeile = function ($i, array $e = []) {
    $n = 'eintraege[' . $i . ']';
    ?>
    <div class="v-rep__zeile ist-zu" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Begriff')); ?>
        <div class="v-rep__koerper">
            <div class="form-group">
                <label class="form-label"><?= t('Begriff') ?></label>
                <input type="text" class="form-control" name="<?= $n ?>[begriff]"
                       value="<?= h(Werkzeug::feld($e, 'begriff')) ?>" maxlength="120">
            </div>
            <div class="form-group mb-0">
                <label class="form-label"><?= t('Erklärung') ?></label>
                <textarea class="form-control" rows="3" name="<?= $n ?>[erklaerung]"><?= h(Werkzeug::feld($e, 'erklaerung')) ?></textarea>
                <p class="text-muted small mb-0">
                    <?= t('Leerzeile macht einen neuen Absatz. Einen Link schreibst du als [Beschriftung](Adresse).') ?>
                </p>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="alert alert-info">
    <?= t('Die Reihenfolge musst du nicht beachten: Sortiert wird alphabetisch, und die Buchstabengruppen samt Sprungleiste entstehen von allein. Zum Aufklappen auf die Zeile klicken.') ?>
</div>

<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($eintragListe as $i => $e) { $zeile($i, $e); } ?>
    </div>

    <p class="v-rep__leer" data-rep-leer style="display:none">
        <?= t('Noch kein Begriff. Unten hinzufügen.') ?>
    </p>

    <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

    <button type="button" class="btn btn-secondary" data-rep-neu>
        <?= t('Begriff hinzufügen') ?>
    </button>
</div>

<hr>

<div class="form-check">
    <?= $form->checkbox('zeigeSuche', 1, isset($zeigeSuche) ? (bool) $zeigeSuche : true, ['class' => 'form-check-input']) ?>
    <?= $form->label('zeigeSuche', t('Suchfeld anzeigen'), ['class' => 'form-check-label']) ?>
</div>
<div class="form-check">
    <?= $form->checkbox('zeigeAlphabet', 1, isset($zeigeAlphabet) ? (bool) $zeigeAlphabet : true, ['class' => 'form-check-input']) ?>
    <?= $form->label('zeigeAlphabet', t('Buchstabenleiste anzeigen'), ['class' => 'form-check-label']) ?>
</div>
