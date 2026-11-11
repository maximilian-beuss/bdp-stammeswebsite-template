<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/** @var array $folienListe */
$folienListe = isset($folienListe) ? $folienListe : [];

/**
 * Eine Folienzeile. Wird zweimal gebraucht: für die vorhandenen
 * Folien und als Vorlage im <template> für neu hinzugefügte.
 * $i ist entweder eine laufende Nummer oder der Marker __I__, den
 * repeater.js beim Einfügen ersetzt.
 */
$zeile = function ($i, array $f = []) use ($form) {
    $n = 'folien[' . $i . ']';
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Folie')); ?>
        <div class="v-rep__koerper">
            <div class="v-rep__paar">
                <div>
                    <?php Maske::bildKnopf($n . '[bildfID]', Werkzeug::feld($f, 'bildfID', 0), t('Bild wählen')); ?>
                </div>
                <div>
                    <div class="form-group">
                        <label class="form-label"><?= t('Überschrift') ?></label>
                        <input type="text" class="form-control" name="<?= $n ?>[titel]"
                               value="<?= h(Werkzeug::feld($f, 'titel')) ?>" maxlength="120">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('Text darunter') ?></label>
                        <textarea class="form-control" rows="2" name="<?= $n ?>[text]"><?= h(Werkzeug::feld($f, 'text')) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('Bildbeschreibung für Screenreader') ?></label>
                        <input type="text" class="form-control" name="<?= $n ?>[alt]"
                               value="<?= h(Werkzeug::feld($f, 'alt')) ?>" maxlength="200">
                        <p class="text-muted small mb-0">
                            <?= t('Leer lassen, wenn das Bild nur schmückt und die Überschrift schon alles sagt.') ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php foreach ([1, 2] as $k) { ?>
                <fieldset class="border rounded p-2 mb-2">
                    <legend class="h6"><?= t('Knopf %s', $k) ?></legend>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="form-label"><?= t('Beschriftung') ?></label>
                            <input type="text" class="form-control" name="<?= $n ?>[knopf<?= $k ?>text]"
                                   value="<?= h(Werkzeug::feld($f, 'knopf' . $k . 'text')) ?>" maxlength="60">
                        </div>
                        <div class="col-md-5 form-group">
                            <label class="form-label"><?= t('Ziel (Adresse)') ?></label>
                            <input type="text" class="form-control" name="<?= $n ?>[knopf<?= $k ?>ziel]"
                                   value="<?= h(Werkzeug::feld($f, 'knopf' . $k . 'ziel')) ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="form-label"><?= t('Aussehen') ?></label>
                            <select class="form-select" name="<?= $n ?>[knopf<?= $k ?>stil]">
                                <?php
                                $stile = [
                                    'btn--gelb' => t('Gelb'),
                                    '' => t('Blau'),
                                    'btn--linie' => t('Nur Linie'),
                                ];
                                $gewaehlt = Werkzeug::feld($f, 'knopf' . $k . 'stil', 'btn--gelb');
                                foreach ($stile as $wert => $bezeichnung) { ?>
                                    <option value="<?= h($wert) ?>"<?= $gewaehlt === $wert ? ' selected' : '' ?>>
                                        <?= h($bezeichnung) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </fieldset>
            <?php } ?>
        </div>
    </div>
    <?php
};
?>

<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($folienListe as $i => $f) { $zeile($i, $f); } ?>
    </div>

    <p class="v-rep__leer" data-rep-leer style="display:none">
        <?= t('Noch keine Folie. Unten hinzufügen.') ?>
    </p>

    <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

    <button type="button" class="btn btn-secondary" data-rep-neu>
        <?= t('Folie hinzufügen') ?>
    </button>
</div>

<hr>

<div class="row">
    <div class="col-md-6 form-group">
        <?= $form->label('takt', t('Wechsel alle … Millisekunden')) ?>
        <?= $form->number('takt', isset($takt) ? $takt : 7000, ['min' => 0, 'max' => 60000, 'step' => 500]) ?>
        <p class="text-muted small mb-0">
            <?= t('0 schaltet den selbsttätigen Wechsel ab. Der Wechsel hält ohnehin an, sobald jemand mit der Maus darüber ist oder die Systemeinstellung „Bewegung reduzieren“ gesetzt hat.') ?>
        </p>
    </div>
    <div class="col-md-6 form-group">
        <?= $form->label('hoehe', t('Höhe')) ?>
        <?= $form->select('hoehe', [
            'klein' => t('Flach'),
            'normal' => t('Normal'),
            'gross' => t('Hoch'),
        ], isset($hoehe) ? $hoehe : 'normal') ?>
    </div>
</div>

<?php Maske::platzhalterhilfe(); ?>
