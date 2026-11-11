<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/** @var array $zeilenListe */
/** @var array $stufenKatalog */
$zeilenListe = isset($zeilenListe) ? $zeilenListe : [];

$zeile = function ($i, array $z = []) {
    $n = 'zeilen[' . $i . ']';
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Zeile')); ?>
        <div class="v-rep__koerper">
            <div class="row">
                <div class="col-md-4 form-group mb-0">
                    <label class="form-label"><?= t('Bezeichnung') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[bezeichnung]"
                           value="<?= h(Werkzeug::feld($z, 'bezeichnung')) ?>" maxlength="60">
                </div>
                <div class="col-md-8 form-group mb-0">
                    <label class="form-label"><?= t('Wert') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[wert]"
                           value="<?= h(Werkzeug::feld($z, 'wert')) ?>" maxlength="200">
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="alert alert-info">
    <?= t('Zeilen ohne Wert erscheinen nicht. Du kannst also alle vorbereiten und nur die ausfüllen, die es gibt.') ?>
</div>

<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($zeilenListe as $i => $z) { $zeile($i, $z); } ?>
    </div>

    <p class="v-rep__leer" data-rep-leer style="display:none">
        <?= t('Noch keine Zeile. Unten hinzufügen.') ?>
    </p>

    <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

    <button type="button" class="btn btn-secondary" data-rep-neu>
        <?= t('Zeile hinzufügen') ?>
    </button>
</div>

<hr>

<div class="form-group">
    <?= $form->label('stufe', t('Farbe des Rahmens')) ?>
    <?php
    $auswahl = ['auto' => t('Automatisch nach der Stufe dieser Seite')];
    foreach ($stufenKatalog as $handle => $s) {
        $auswahl[$handle] = $s['name'];
    }
    ?>
    <?= $form->select('stufe', $auswahl, isset($stufe) ? $stufe : 'auto') ?>
    <p class="text-muted small mb-0">
        <?= t('Automatisch heißt: Der Rahmen richtet sich nach dem Seitenattribut „Altersstufe“. Meute wird gelb, Sippe blau, Runde und Thing rot.') ?>
    </p>
</div>
