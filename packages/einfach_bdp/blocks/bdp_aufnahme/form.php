<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/** @var array $schritteListe */
$schritteListe = isset($schritteListe) ? $schritteListe : [];

$zeile = function ($i, array $s = []) {
    $n = 'schritte[' . $i . ']';
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Schritt')); ?>
        <div class="v-rep__koerper">

            <div class="form-group">
                <label class="form-label"><?= t('Überschrift des Schritts') ?></label>
                <input type="text" class="form-control" name="<?= $n ?>[titel]"
                       value="<?= h(Werkzeug::feld($s, 'titel')) ?>" maxlength="160">
            </div>

            <div class="form-group">
                <label class="form-label"><?= t('Text') ?></label>
                <textarea class="form-control" rows="6" name="<?= $n ?>[text]"><?= h(Werkzeug::feld($s, 'text')) ?></textarea>
                <p class="text-muted small mb-0">
                    <?= t('Leerzeile macht einen neuen Absatz. Einen Link schreibst du als [Beschriftung](Adresse).') ?>
                </p>
            </div>

            <div class="row">
                <div class="col-md-5 form-group">
                    <label class="form-label"><?= t('Knopf: Beschriftung') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[knopfText]"
                           value="<?= h(Werkzeug::feld($s, 'knopfText')) ?>" maxlength="80">
                </div>
                <div class="col-md-7 form-group">
                    <label class="form-label"><?= t('Knopf: Ziel') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[knopfZiel]"
                           value="<?= h(Werkzeug::feld($s, 'knopfZiel')) ?>">
                    <p class="text-muted small mb-0">
                        <?= t('Führt das Ziel auf eine fremde Seite, kommt vorher automatisch der Rückkehr-Hinweis.') ?>
                    </p>
                </div>
            </div>

            <div class="v-rep__paar">
                <div>
                    <label class="form-label"><?= t('Bild (optional)') ?></label>
                    <?php Maske::bildKnopf($n . '[bildfID]', Werkzeug::feld($s, 'bildfID', 0), t('Bild wählen')); ?>
                </div>
                <div>
                    <div class="form-group">
                        <label class="form-label"><?= t('Datei zum Herunterladen (optional)') ?></label>
                        <?php Maske::bildKnopf($n . '[dateifID]', Werkzeug::feld($s, 'dateifID', 0), t('Datei wählen')); ?>
                        <p class="text-muted small mb-0">
                            <?= t('Ist hier eine Datei gewählt, führt der Knopf dorthin und das Feld „Ziel“ wird nicht benutzt.') ?>
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?= t('Formular einbetten (Adresse des iframes)') ?></label>
                        <input type="text" class="form-control" name="<?= $n ?>[einbettung]"
                               value="<?= h(Werkzeug::feld($s, 'einbettung')) ?>">
                        <p class="text-muted small mb-0">
                            <?= t('Lädt erst nach Zustimmung der Besucherin, wegen des Datenschutzes.') ?>
                        </p>
                    </div>

                    <div class="form-check">
                        <input type="hidden" name="<?= $n ?>[abschluss]" value="0">
                        <input type="checkbox" class="form-check-input" value="1"
                               name="<?= $n ?>[abschluss]"
                               <?= Werkzeug::feld($s, 'abschluss') ? 'checked' : '' ?>>
                        <label class="form-check-label"><?= t('Schlussschritt (mit Konfetti)') ?></label>
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
        <?php foreach ($schritteListe as $i => $s) { $zeile($i, $s); } ?>
    </div>

    <p class="v-rep__leer" data-rep-leer style="display:none">
        <?= t('Noch kein Schritt. Unten hinzufügen.') ?>
    </p>

    <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

    <button type="button" class="btn btn-secondary" data-rep-neu>
        <?= t('Schritt hinzufügen') ?>
    </button>
</div>

<hr>

<h5><?= t('Hinweis vor dem Weg nach draußen') ?></h5>

<div class="form-group">
    <?= $form->label('hinausTitel', t('Überschrift')) ?>
    <?= $form->text('hinausTitel', isset($hinausTitel) ? $hinausTitel : '', ['maxlength' => 160]) ?>
</div>

<div class="form-group">
    <?= $form->label('hinausText', t('Text')) ?>
    <?= $form->textarea('hinausText', isset($hinausText) ? $hinausText : '', ['rows' => 4]) ?>
</div>

<div class="form-group">
    <div class="form-check">
        <?= $form->checkbox('merken', 1, isset($merken) ? (bool) $merken : true, ['class' => 'form-check-input']) ?>
        <?= $form->label('merken', t('Erreichten Schritt im Browser merken'), ['class' => 'form-check-label']) ?>
    </div>
    <p class="text-muted small mb-0">
        <?= t('Wer von einem fremden Formular zurückkommt, steht dann wieder an derselben Stelle. Gespeichert wird nur die Schrittnummer, im Browser der Besucherin.') ?>
    </p>
</div>
