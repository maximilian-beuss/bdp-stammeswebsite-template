<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/** @var array $bilderListe */
$bilderListe = isset($bilderListe) ? $bilderListe : [];

$zeile = function ($i, array $b = []) {
    $n = 'bilder[' . $i . ']';
    $groessen = [
        'auto' => t('Automatisch (nach Seitenverhältnis)'),
        'normal' => t('Normal'),
        'breit' => t('Doppelt breit'),
        'hoch' => t('Doppelt hoch'),
        'gross' => t('Doppelt breit und hoch'),
    ];
    $gewaehlt = Werkzeug::feld($b, 'groesse', 'auto');
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Bild')); ?>
        <div class="v-rep__koerper">
            <div class="v-rep__paar">
                <div>
                    <?php Maske::bildKnopf($n . '[bildfID]', Werkzeug::feld($b, 'bildfID', 0), t('Bild wählen')); ?>
                </div>
                <div>
                    <div class="form-group">
                        <label class="form-label"><?= t('Titel') ?></label>
                        <input type="text" class="form-control" name="<?= $n ?>[titel]"
                               value="<?= h(Werkzeug::feld($b, 'titel')) ?>" maxlength="160">
                        <p class="text-muted small mb-0">
                            <?= t('Dient zugleich als Bildbeschreibung für Screenreader.') ?>
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('Text im Info-Kästchen') ?></label>
                        <textarea class="form-control" rows="2" name="<?= $n ?>[text]"><?= h(Werkzeug::feld($b, 'text')) ?></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label"><?= t('Größe im Raster') ?></label>
                        <select class="form-select" name="<?= $n ?>[groesse]">
                            <?php foreach ($groessen as $wert => $bezeichnung) { ?>
                                <option value="<?= h($wert) ?>"<?= $gewaehlt === $wert ? ' selected' : '' ?>>
                                    <?= h($bezeichnung) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($bilderListe as $i => $b) { $zeile($i, $b); } ?>
    </div>

    <p class="v-rep__leer" data-rep-leer style="display:none">
        <?= t('Noch kein Bild. Unten hinzufügen.') ?>
    </p>

    <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

    <button type="button" class="btn btn-secondary" data-rep-neu>
        <?= t('Bild hinzufügen') ?>
    </button>
</div>

<hr>

<div class="row">
    <div class="col-md-4 form-group">
        <?= $form->label('dichte', t('Kachelgröße')) ?>
        <?= $form->select('dichte', [
            'schmal' => t('Klein, viele Bilder nebeneinander'),
            'normal' => t('Mittel'),
            'weit' => t('Groß, wenige Bilder nebeneinander'),
        ], isset($dichte) ? $dichte : 'normal') ?>
    </div>
    <div class="col-md-4 form-group">
        <div class="form-check mt-4">
            <?= $form->checkbox('infos', 1, isset($infos) ? (bool) $infos : true, ['class' => 'form-check-input']) ?>
            <?= $form->label('infos', t('Info-Kästchen an den Bildern zeigen'), ['class' => 'form-check-label']) ?>
        </div>
    </div>
    <div class="col-md-4 form-group">
        <div class="form-check mt-4">
            <?= $form->checkbox('lupe', 1, isset($lupe) ? (bool) $lupe : true, ['class' => 'form-check-input']) ?>
            <?= $form->label('lupe', t('Klick öffnet das Bild groß'), ['class' => 'form-check-label']) ?>
        </div>
    </div>
</div>
