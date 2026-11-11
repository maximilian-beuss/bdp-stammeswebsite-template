<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$app  = Application::getFacadeApplication();
$form = $app->make('helper/form');
$token = $app->make('token');
$dateiWaehler = $app->make('helper/concrete/asset_library');

Maske::assets();

/** @var string $stammesname */
/** @var string $ort */
/** @var int    $logoDateifID */
/** @var string $logoAlt */
/** @var array  $topbarLinksListe */
/** @var array  $topbarRechtsListe */
/** @var mixed  $bildDatei */

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
                           value="<?= h(Werkzeug::feld($e, 'text')) ?>" maxlength="80">
                </div>
                <div class="col-md-5 form-group">
                    <label class="form-label"><?= t('Ziel (URL)') ?></label>
                    <input type="text" class="form-control" name="<?= $n ?>[href]"
                           value="<?= h(Werkzeug::feld($e, 'href')) ?>">
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

<div class="ccm-dashboard-content-full">

    <?php if ($controller->get('success')) { ?>
        <div class="alert alert-success"><?= h($controller->get('success')) ?></div>
    <?php } ?>

    <form method="post" action="<?= (string) Url::to('/dashboard/einfach_bdp/stamm', 'sichern') ?>">
        <?= $token->output('bdp_stamm_sichern') ?>

        <h4><?= t('Stamm') ?></h4>

        <div class="row">
            <div class="col-md-6 form-group">
                <?= $form->label('stammesname', t('Stammesname')) ?>
                <?= $form->text('stammesname', $stammesname, ['maxlength' => 100]) ?>
                <p class="text-muted small mb-0"><?= t('Erscheint im Logo und im Browser-Tab.') ?></p>
            </div>
            <div class="col-md-6 form-group">
                <?= $form->label('ort', t('Ort')) ?>
                <?= $form->text('ort', $ort, ['maxlength' => 100]) ?>
                <p class="text-muted small mb-0"><?= t('Kleiner Zusatz unter dem Namen im Logo.') ?></p>
            </div>
        </div>

        <hr>

        <h4><?= t('Logo') ?></h4>
        <div class="form-group">
            <?= $form->label('logoDateifID', t('Logo-Bild')) ?>
            <?= $dateiWaehler->image('logoDateifID', 'logoDateifID', t('Bild wählen'), $bildDatei) ?>
            <p class="text-muted small mb-0"><?= t('Leer lassen für das Standard-BdP-Logo (blaue Lilie).') ?></p>
        </div>
        <div class="form-group">
            <?= $form->label('logoAlt', t('Alt-Text für das Logo')) ?>
            <?= $form->text('logoAlt', $logoAlt, ['maxlength' => 255]) ?>
        </div>

        <hr>

        <h4><?= t('Topbar links') ?></h4>
        <p class="text-muted small"><?= t('Links in der schmalen blauen Leiste oben links. Extern = öffnet in neuem Tab.') ?></p>
        <div data-rep>
            <div class="v-rep__zeilen" data-rep-zeilen>
                <?php foreach ($topbarLinksListe as $i => $e) { $linkZeile('topbarLinks', $i, $e); } ?>
            </div>
            <p class="v-rep__leer" data-rep-leer style="display:none"><?= t('Keine Links.') ?></p>
            <template data-rep-vorlage><?php $linkZeile('topbarLinks', '__I__'); ?></template>
            <button type="button" class="btn btn-secondary btn-sm" data-rep-neu><?= t('Link hinzufügen') ?></button>
        </div>

        <hr>

        <h4><?= t('Topbar rechts') ?></h4>
        <p class="text-muted small"><?= t('Links in der schmalen blauen Leiste oben rechts.') ?></p>
        <div data-rep>
            <div class="v-rep__zeilen" data-rep-zeilen>
                <?php foreach ($topbarRechtsListe as $i => $e) { $linkZeile('topbarRechts', $i, $e); } ?>
            </div>
            <p class="v-rep__leer" data-rep-leer style="display:none"><?= t('Keine Links.') ?></p>
            <template data-rep-vorlage><?php $linkZeile('topbarRechts', '__I__'); ?></template>
            <button type="button" class="btn btn-secondary btn-sm" data-rep-neu><?= t('Link hinzufügen') ?></button>
        </div>

        <hr>

        <button type="submit" class="btn btn-primary"><?= t('Speichern') ?></button>

    </form>

</div>
