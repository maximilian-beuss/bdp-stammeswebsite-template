<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/** @var array $dateiListe */
$dateiListe = isset($dateiListe) ? $dateiListe : [];

$zeile = function ($i, array $d = []) {
    $n = 'dateien[' . $i . ']';
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Datei')); ?>
        <div class="v-rep__koerper">
            <div class="v-rep__paar">
                <div>
                    <?php Maske::bildKnopf($n . '[dateifID]', Werkzeug::feld($d, 'dateifID', 0), t('Datei wählen')); ?>
                </div>
                <div>
                    <div class="form-group">
                        <label class="form-label"><?= t('Name') ?></label>
                        <input type="text" class="form-control" name="<?= $n ?>[name]"
                               value="<?= h(Werkzeug::feld($d, 'name')) ?>" maxlength="200">
                        <p class="text-muted small mb-0">
                            <?= t('Leer lassen, dann steht der Dateiname da.') ?>
                        </p>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label"><?= t('Wozu ist das gut?') ?></label>
                        <textarea class="form-control" rows="2" name="<?= $n ?>[beschreibung]"><?= h(Werkzeug::feld($d, 'beschreibung')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="alert alert-info">
    <?= t('Dateityp und Größe kommen aus dem Dateimanager. Wer eine Datei austauscht, muss also nichts nachtragen.') ?>
</div>

<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($dateiListe as $i => $d) { $zeile($i, $d); } ?>
    </div>

    <p class="v-rep__leer" data-rep-leer style="display:none">
        <?= t('Noch keine Datei. Unten hinzufügen.') ?>
    </p>

    <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

    <button type="button" class="btn btn-secondary" data-rep-neu>
        <?= t('Datei hinzufügen') ?>
    </button>
</div>

<hr>

<div class="form-check">
    <?= $form->checkbox('zeigeGroesse', 1, isset($zeigeGroesse) ? (bool) $zeigeGroesse : true, ['class' => 'form-check-input']) ?>
    <?= $form->label('zeigeGroesse', t('Dateigröße anzeigen'), ['class' => 'form-check-label']) ?>
</div>
