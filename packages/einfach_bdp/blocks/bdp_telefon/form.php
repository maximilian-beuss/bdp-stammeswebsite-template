<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/** @var array $nummernListe */
$nummernListe = isset($nummernListe) ? $nummernListe : [];

$zeile = function ($i, array $n = []) {
    $p = 'nummern[' . $i . ']';
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Person')); ?>
        <div class="v-rep__koerper">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label class="form-label"><?= t('Name') ?></label>
                    <input type="text" class="form-control" name="<?= $p ?>[name]"
                           value="<?= h(Werkzeug::feld($n, 'name')) ?>" maxlength="80">
                </div>
                <div class="col-md-4 form-group">
                    <label class="form-label"><?= t('Amt') ?></label>
                    <input type="text" class="form-control" name="<?= $p ?>[rolle]"
                           value="<?= h(Werkzeug::feld($n, 'rolle')) ?>" maxlength="80">
                </div>
                <div class="col-md-5 form-group">
                    <label class="form-label"><?= t('Telefonnummer') ?></label>
                    <input type="text" class="form-control" name="<?= $p ?>[nummer]"
                           value="<?= h(Werkzeug::feld($n, 'nummer')) ?>" maxlength="40"
                           placeholder="+49 171 3920000">
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="alert alert-info">
    <?= t('Name und Amt stehen sichtbar auf der Seite. Die Nummer nicht: Sie bleibt in der Datenbank und geht erst nach einem gelösten Captcha an den Browser.') ?>
</div>

<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($nummernListe as $i => $n) { $zeile($i, $n); } ?>
    </div>

    <p class="v-rep__leer" data-rep-leer style="display:none">
        <?= t('Noch keine Nummer. Unten hinzufügen.') ?>
    </p>

    <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

    <button type="button" class="btn btn-secondary" data-rep-neu>
        <?= t('Person hinzufügen') ?>
    </button>
</div>

<hr>

<h5><?= t('Cloudflare Turnstile') ?></h5>

<p class="text-muted small">
    <?= t('Beide Schlüssel bekommst du im Cloudflare-Dashboard unter Turnstile → Add Site. Trage dort die Domains ein, unter denen die Seite läuft.') ?>
</p>

<div class="row">
    <div class="col-md-6 form-group">
        <?= $form->label('sitekey', t('Sitekey (öffentlich)')) ?>
        <?= $form->text('sitekey', isset($sitekey) ? $sitekey : '', ['maxlength' => 120]) ?>
        <p class="text-muted small mb-0"><?= t('Steht im Quelltext der Seite. Das ist so vorgesehen.') ?></p>
    </div>
    <div class="col-md-6 form-group">
        <?= $form->label('secret', t('Secret Key (geheim)')) ?>
        <?= $form->text('secret', isset($secret) ? $secret : '', ['maxlength' => 120]) ?>
        <p class="text-muted small mb-0"><?= t('Verlässt den Server nie. Nicht weitergeben.') ?></p>
    </div>
</div>

<div class="alert alert-warning">
    <?= t('Bleiben beide Felder leer, gelten die Schlüssel aus application/config/bdp_telefon.php. Dort stehen Cloudflares Testschlüssel — die lassen jedes Token durch und schützen nichts.') ?>
</div>

<hr>

<div class="form-group">
    <?= $form->label('textVor', t('Text über den Nummern')) ?>
    <?= $form->textarea('textVor', isset($textVor) ? $textVor : '', ['rows' => 2]) ?>
</div>

<div class="form-group">
    <?= $form->label('hinweis', t('Kleiner Satz unter dem Captcha')) ?>
    <?= $form->text('hinweis', isset($hinweis) ? $hinweis : '', ['maxlength' => 255]) ?>
</div>

<div class="form-group">
    <?= $form->label('textNach', t('Text unter dem Captcha')) ?>
    <?= $form->textarea('textNach', isset($textNach) ? $textNach : '', ['rows' => 2]) ?>
</div>

<div class="row">
    <div class="col-md-7 form-group">
        <?= $form->label('ersatzMail', t('Adresse für alle, die kein Captcha lösen können')) ?>
        <?= $form->email('ersatzMail', isset($ersatzMail) ? $ersatzMail : '', ['maxlength' => 160]) ?>
    </div>
    <div class="col-md-5 form-group">
        <?= $form->label('freigabeMinuten', t('Freigabe gilt … Minuten')) ?>
        <?= $form->number('freigabeMinuten', isset($freigabeMinuten) ? $freigabeMinuten : 10, ['min' => 1, 'max' => 1440]) ?>
    </div>
</div>

<hr>

<div class="form-check">
    <?= $form->checkbox('sechseckAktiviert', 1, isset($sechseckAktiviert) ? (bool) $sechseckAktiviert : false, ['class' => 'form-check-input']) ?>
    <?= $form->label('sechseckAktiviert', t('Sechseck-Feature: Platzhalter mit Sechsecken statt Kreisen'), ['class' => 'form-check-label']) ?>
    <p class="text-muted small mb-0"><?= t('Standardmäßig deaktiviert. Das Sechseck ist ein Stammessymbol — nur aktivieren, wenn es zum eigenen Auftritt passt.') ?></p>
</div>
