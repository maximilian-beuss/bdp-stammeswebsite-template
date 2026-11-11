<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/** @var array $artikelListe */
/** @var array $rubriken */
$artikelListe = isset($artikelListe) ? $artikelListe : [];
$rubriken = isset($rubriken) ? $rubriken : [];

$zeile = function ($i, array $a = []) use ($rubriken) {
    $n = 'artikel[' . $i . ']';
    $zustand = Werkzeug::feld($a, 'zustand', 'neu');
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Artikel')); ?>
        <div class="v-rep__koerper">
            <div class="v-rep__paar">
                <div>
                    <?php Maske::bildKnopf($n . '[bildfID]', Werkzeug::feld($a, 'bildfID', 0), t('Bild wählen')); ?>
                </div>
                <div>
                    <div class="row">
                        <div class="col-md-7 form-group">
                            <label class="form-label"><?= t('Name') ?></label>
                            <input type="text" class="form-control" name="<?= $n ?>[name]"
                                   value="<?= h(Werkzeug::feld($a, 'name')) ?>" maxlength="160">
                        </div>
                        <div class="col-md-5 form-group">
                            <label class="form-label"><?= t('Rubrik') ?></label>
                            <input type="text" class="form-control" name="<?= $n ?>[rubrik]"
                                   list="v-kaemmerei-rubriken"
                                   value="<?= h(Werkzeug::feld($a, 'rubrik')) ?>" maxlength="80">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?= t('Beschreibung') ?></label>
                        <textarea class="form-control" rows="2" name="<?= $n ?>[beschreibung]"><?= h(Werkzeug::feld($a, 'beschreibung')) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="form-label"><?= t('Preis') ?></label>
                            <input type="text" class="form-control" name="<?= $n ?>[preis]"
                                   value="<?= h(Werkzeug::feld($a, 'preis')) ?>" maxlength="40"
                                   placeholder="<?= t('z. B. 28 €') ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="form-label"><?= t('Zustand') ?></label>
                            <select class="form-select" name="<?= $n ?>[zustand]">
                                <option value="neu"<?= $zustand === 'neu' ? ' selected' : '' ?>><?= t('Neu') ?></option>
                                <option value="gebraucht"<?= $zustand === 'gebraucht' ? ' selected' : '' ?>><?= t('Gebraucht') ?></option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="form-label"><?= t('Zusatz') ?></label>
                            <input type="text" class="form-control" name="<?= $n ?>[hinweis]"
                                   value="<?= h(Werkzeug::feld($a, 'hinweis')) ?>" maxlength="120"
                                   placeholder="<?= t('z. B. Verhandlungsbasis') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<datalist id="v-kaemmerei-rubriken">
    <?php foreach ($rubriken as $r) { ?>
        <option value="<?= h($r) ?>"></option>
    <?php } ?>
</datalist>

<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($artikelListe as $i => $a) { $zeile($i, $a); } ?>
    </div>

    <p class="v-rep__leer" data-rep-leer style="display:none">
        <?= t('Noch kein Artikel. Unten hinzufügen.') ?>
    </p>

    <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

    <button type="button" class="btn btn-secondary" data-rep-neu>
        <?= t('Artikel hinzufügen') ?>
    </button>
</div>

<hr>

<div class="row">
    <div class="col-md-7 form-group">
        <?= $form->label('bestellMail', t('Anfragen gehen an')) ?>
        <?= $form->email('bestellMail', isset($bestellMail) ? $bestellMail : '', ['maxlength' => 160]) ?>
        <p class="text-muted small mb-0">
            <?= t('Leer lassen, wenn an den Artikeln kein Anfrage-Link stehen soll.') ?>
        </p>
    </div>
    <div class="col-md-5 form-group">
        <div class="form-check mt-4">
            <?= $form->checkbox('gruppieren', 1, isset($gruppieren) ? (bool) $gruppieren : true, ['class' => 'form-check-input']) ?>
            <?= $form->label('gruppieren', t('Nach Rubriken gruppieren'), ['class' => 'form-check-label']) ?>
        </div>
    </div>
</div>
