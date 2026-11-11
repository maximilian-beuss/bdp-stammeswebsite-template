<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
$dateiWaehler = Application::getFacadeApplication()->make('helper/concrete/asset_library');
Maske::assets();

/** @var array  $topbarLinksListe */
/** @var array  $topbarRechtsListe */
/** @var mixed  $bildDatei */
$topbarLinksListe  = isset($topbarLinksListe)  ? $topbarLinksListe  : [];
$topbarRechtsListe = isset($topbarRechtsListe) ? $topbarRechtsListe : [];

$linkZeile = function ($gruppe, $i, array $e = []) use ($form) {
    $n = $gruppe . '[' . $i . ']';
    $extern = !empty($e['extern']);
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php \EinfachBdP\Maske::zeilenkopf(t('Link')); ?>
        <div class="v-rep__koerper">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label class="form-label"><?= t('Beschriftung') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[text]"
                           value="<?= h(\EinfachBdP\Werkzeug::feld($e, 'text')) ?>" maxlength="80">
                </div>
                <div class="col-md-5 form-group">
                    <label class="form-label"><?= t('Ziel (URL)') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[href]"
                           value="<?= h(\EinfachBdP\Werkzeug::feld($e, 'href')) ?>">
                </div>
                <div class="col-md-3 form-group d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="<?= $n ?>[extern]" value="1" id="<?= $n ?>_extern"
                               <?= $extern ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= $n ?>_extern">
                            <?= t('Extern') ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<h5><?= t('Stamm') ?></h5>
<div class="row">
    <div class="col-md-6 form-group">
        <?= $form->label('stammesname', t('Stammesname')) ?>
        <?= $form->text('stammesname', isset($stammesname) ? $stammesname : 'Musterstamm', ['maxlength' => 100]) ?>
        <p class="text-muted small mb-0"><?= t('Erscheint im Logo und im Browser-Tab.') ?></p>
    </div>
    <div class="col-md-6 form-group">
        <?= $form->label('ort', t('Ort')) ?>
        <?= $form->text('ort', isset($ort) ? $ort : '', ['maxlength' => 100]) ?>
        <p class="text-muted small mb-0"><?= t('Kleiner Zusatz unter dem Namen im Logo.') ?></p>
    </div>
</div>

<hr>

<h5><?= t('Logo') ?></h5>
<div class="form-group">
    <?= $form->label('logoDateifID', t('Logo-Bild')) ?>
    <?= $dateiWaehler->image('logoDateifID', 'logoDateifID', t('Bild wählen'), isset($bildDatei) ? $bildDatei : null) ?>
    <p class="text-muted small mb-0"><?= t('Leer lassen für das Standard-BdP-Logo (blaue Lilie).') ?></p>
</div>
<div class="form-group">
    <?= $form->label('logoAlt', t('Alt-Text für das Logo')) ?>
    <?= $form->text('logoAlt', isset($logoAlt) ? $logoAlt : 'Zurück zur Startseite', ['maxlength' => 255]) ?>
</div>

<hr>

<h5><?= t('Topbar links') ?></h5>
<p class="text-muted small"><?= t('Links in der schmalen blauen Leiste oben. Extern = öffnet in neuem Tab.') ?></p>
<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($topbarLinksListe as $i => $e) { $linkZeile('topbarLinks', $i, $e); } ?>
    </div>
    <p class="v-rep__leer" data-rep-leer style="display:none"><?= t('Keine Links.') ?></p>
    <template data-rep-vorlage><?php $linkZeile('topbarLinks', '__I__'); ?></template>
    <button type="button" class="btn btn-secondary" data-rep-neu><?= t('Link hinzufügen') ?></button>
</div>

<hr>

<h5><?= t('Topbar rechts') ?></h5>
<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($topbarRechtsListe as $i => $e) { $linkZeile('topbarRechts', $i, $e); } ?>
    </div>
    <p class="v-rep__leer" data-rep-leer style="display:none"><?= t('Keine Links.') ?></p>
    <template data-rep-vorlage><?php $linkZeile('topbarRechts', '__I__'); ?></template>
    <button type="button" class="btn btn-secondary" data-rep-neu><?= t('Link hinzufügen') ?></button>
</div>
