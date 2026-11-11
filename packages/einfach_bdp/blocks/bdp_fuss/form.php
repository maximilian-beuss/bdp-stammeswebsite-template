<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/** @var array $kontakteListe */
/** @var array $rechtsListe */
$kontakteListe = isset($kontakteListe) ? $kontakteListe : [];
$rechtsListe = isset($rechtsListe) ? $rechtsListe : [];

$ikons = [
    'mail' => t('Briefumschlag'),
    'instagram' => t('Instagram'),
    'karte' => t('Kartennadel'),
];

$kontaktZeile = function ($i, array $k = []) use ($ikons) {
    $n = 'kontakte[' . $i . ']';
    $gewaehlt = Werkzeug::feld($k, 'ikon', 'mail');
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Kontaktweg')); ?>
        <div class="v-rep__koerper">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label class="form-label"><?= t('Symbol') ?></label>
                    <select class="form-select" name="<?= $n ?>[ikon]">
                        <?php foreach ($ikons as $wert => $bezeichnung) { ?>
                            <option value="<?= h($wert) ?>"<?= $gewaehlt === $wert ? ' selected' : '' ?>>
                                <?= h($bezeichnung) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label class="form-label"><?= t('Beschriftung') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[text]"
                           value="<?= h(Werkzeug::feld($k, 'text')) ?>" maxlength="120">
                </div>
                <div class="col-md-5 form-group">
                    <label class="form-label"><?= t('Ziel') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[ziel]"
                           value="<?= h(Werkzeug::feld($k, 'ziel')) ?>">
                </div>
            </div>
        </div>
    </div>
    <?php
};

$rechtsZeile = function ($i, array $r = []) {
    $n = 'rechtslinks[' . $i . ']';
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Link')); ?>
        <div class="v-rep__koerper">
            <div class="row">
                <div class="col-md-5 form-group">
                    <label class="form-label"><?= t('Beschriftung') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[text]"
                           value="<?= h(Werkzeug::feld($r, 'text')) ?>" maxlength="120">
                </div>
                <div class="col-md-7 form-group">
                    <label class="form-label"><?= t('Ziel') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[ziel]"
                           value="<?= h(Werkzeug::feld($r, 'ziel')) ?>">
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<h5><?= t('Marken') ?></h5>
<div class="row">
    <div class="col-md-6 form-group">
        <?= $form->label('stammZiel', t('Stammeslogo führt zu')) ?>
        <?= $form->text('stammZiel', isset($stammZiel) ? $stammZiel : '/', ['maxlength' => 255]) ?>
    </div>
    <div class="col-md-6 form-group">
        <?= $form->label('bdpZiel', t('BdP-Marke führt zu')) ?>
        <?= $form->text('bdpZiel', isset($bdpZiel) ? $bdpZiel : '', ['maxlength' => 255]) ?>
        <p class="text-muted small mb-0"><?= t('Leer lassen, um die BdP-Marke wegzulassen.') ?></p>
    </div>
</div>

<hr>

<h5><?= t('Anschrift') ?></h5>
<div class="form-group">
    <?= $form->label('adresseTitel', t('Erste Zeile, hervorgehoben')) ?>
    <?= $form->text('adresseTitel', isset($adresseTitel) ? $adresseTitel : '', ['maxlength' => 160]) ?>
</div>
<div class="form-group">
    <?= $form->label('adresseZeilen', t('Weitere Zeilen')) ?>
    <?= $form->textarea('adresseZeilen', isset($adresseZeilen) ? $adresseZeilen : '', ['rows' => 3]) ?>
    <p class="text-muted small mb-0"><?= t('Eine Zeile je Zeile.') ?></p>
</div>

<hr>

<h5><?= t('Kontaktwege') ?></h5>
<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($kontakteListe as $i => $k) { $kontaktZeile($i, $k); } ?>
    </div>
    <p class="v-rep__leer" data-rep-leer style="display:none"><?= t('Noch kein Kontaktweg.') ?></p>
    <template data-rep-vorlage><?php $kontaktZeile('__I__'); ?></template>
    <button type="button" class="btn btn-secondary" data-rep-neu><?= t('Kontaktweg hinzufügen') ?></button>
</div>

<hr>

<h5><?= t('Schlusszeile') ?></h5>
<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($rechtsListe as $i => $r) { $rechtsZeile($i, $r); } ?>
    </div>
    <p class="v-rep__leer" data-rep-leer style="display:none"><?= t('Noch kein Link.') ?></p>
    <template data-rep-vorlage><?php $rechtsZeile('__I__'); ?></template>
    <button type="button" class="btn btn-secondary" data-rep-neu><?= t('Link hinzufügen') ?></button>
</div>

<div class="form-group mt-3">
    <div class="form-check">
        <?= $form->checkbox('anmelden', 1, isset($anmelden) ? (bool) $anmelden : true, ['class' => 'form-check-input']) ?>
        <?= $form->label('anmelden', t('Anmelde-Link anzeigen'), ['class' => 'form-check-label']) ?>
    </div>
</div>

<div class="form-group">
    <?= $form->label('schlusszeile', t('Text rechts in der Schlusszeile')) ?>
    <?= $form->textarea('schlusszeile', isset($schlusszeile) ? $schlusszeile : '', ['rows' => 2]) ?>
</div>

<?php Maske::platzhalterhilfe(); ?>
