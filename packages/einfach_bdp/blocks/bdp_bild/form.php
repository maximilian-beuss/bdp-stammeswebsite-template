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
                        <label class="form-label"><?= t('Was ist zu sehen?') ?></label>
                        <input type="text" class="form-control" name="<?= $n ?>[titel]"
                               value="<?= h(Werkzeug::feld($b, 'titel')) ?>" maxlength="200">
                        <p class="text-muted small mb-0">
                            <?= t('Kurz und sachlich. Das ist zugleich die Bildbeschreibung für Screenreader. Leer lassen, wenn das Bild nur schmückt.') ?>
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('Bildunterschrift') ?></label>
                        <textarea class="form-control" rows="2" name="<?= $n ?>[text]"><?= h(Werkzeug::feld($b, 'text')) ?></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label"><?= t('Quelle oder Fotograf') ?></label>
                        <input type="text" class="form-control" name="<?= $n ?>[quelle]"
                               value="<?= h(Werkzeug::feld($b, 'quelle')) ?>" maxlength="160">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="alert alert-info">
    <?= t('Ein Bild ergibt eine einzelne Figur, mehrere ergeben eine Reihe nebeneinander.') ?>
</div>

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
        <?= $form->label('breite', t('Breite')) ?>
        <?= $form->select('breite', [
            'schmal' => t('Schmal, in der Lesespalte'),
            'voll' => t('Volle Breite des Textes'),
            'breit' => t('Breiter als der Text'),
        ], isset($breite) ? $breite : 'voll') ?>
    </div>
    <div class="col-md-4 form-group">
        <?= $form->label('format', t('Zuschnitt')) ?>
        <?= $form->select('format', [
            'frei' => t('So wie das Bild ist'),
            'quer' => t('Quer, 16:9'),
            'hoch' => t('Hochkant, 3:4'),
            'quadrat' => t('Quadratisch'),
        ], isset($format) ? $format : 'frei') ?>
        <p class="text-muted small mb-0">
            <?= t('Ein Zuschnitt beschneidet das Bild mittig. Bei einer Reihe sorgt er dafür, dass alle Bilder gleich hoch sind.') ?>
        </p>
    </div>
    <div class="col-md-4 form-group">
        <div class="form-check mt-4">
            <?= $form->checkbox('zeigeText', 1, isset($zeigeText) ? (bool) $zeigeText : true, ['class' => 'form-check-input']) ?>
            <?= $form->label('zeigeText', t('Bildunterschrift anzeigen'), ['class' => 'form-check-label']) ?>
        </div>
        <p class="text-muted small">
            <?= t('Ausgeschaltet bleibt die Beschreibung für Screenreader erhalten, steht aber nicht sichtbar unter dem Bild.') ?>
        </p>
    </div>
</div>
