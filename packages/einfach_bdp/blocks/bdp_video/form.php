<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
Maske::assets();

/**
 * @var array  $spurenListe
 * @var array  $sprachen
 * @var array  $palette
 * @var string $akzent
 * @var string $knopf
 * @var string $akzentEigen
 * @var string $knopfEigen
 * @var int    $videofID
 * @var int    $posterfID
 * @var string $titel
 * @var string $text
 * @var string $breite
 * @var string $vorladen
 * @var int    $schleife
 */
$spurenListe = isset($spurenListe) ? $spurenListe : [];
$sprachen = isset($sprachen) ? $sprachen : ['de' => 'Deutsch'];
$palette = isset($palette) ? $palette : [];

$zeile = function ($i, array $s = []) use ($sprachen) {
    $n = 'spuren[' . $i . ']';
    $sprache = Werkzeug::feld($s, 'sprache', 'de');
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Untertitel')); ?>
        <div class="v-rep__koerper">
            <div class="v-rep__paar">
                <div>
                    <?php Maske::bildKnopf($n . '[dateifID]', Werkzeug::feld($s, 'dateifID', 0), t('VTT-Datei wählen')); ?>
                </div>
                <div>
                    <div class="row g-2">
                        <div class="col-6 form-group">
                            <label class="form-label"><?= t('Sprache') ?></label>
                            <select class="form-select" name="<?= $n ?>[sprache]">
                                <?php foreach ($sprachen as $kuerzel => $name) { ?>
                                    <option value="<?= h($kuerzel) ?>"<?= $sprache === $kuerzel ? ' selected' : '' ?>>
                                        <?= h($name) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-6 form-group">
                            <label class="form-label"><?= t('Beschriftung') ?></label>
                            <input type="text" class="form-control" name="<?= $n ?>[beschriftung]"
                                   value="<?= h(Werkzeug::feld($s, 'beschriftung')) ?>" maxlength="80"
                                   placeholder="<?= h($sprachen[$sprache] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" value="1"
                               id="spur-standard-<?= h((string) $i) ?>"
                               name="<?= $n ?>[standard]"<?= !empty($s['standard']) ? ' checked' : '' ?>>
                        <label class="form-check-label" for="spur-standard-<?= h((string) $i) ?>">
                            <?= t('Von Anfang an einblenden') ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="v-rep__paar">
    <div>
        <h5><?= t('Video') ?></h5>
        <?php Maske::bildKnopf('videofID', isset($videofID) ? $videofID : 0, t('Videodatei wählen')); ?>
        <p class="text-muted small">
            <?= t('MP4 mit H.264 spielt überall. Große Dateien vorher verkleinern — was hier liegt, lädt jeder Besucher.') ?>
        </p>
    </div>
    <div>
        <h5><?= t('Standbild') ?></h5>
        <?php Maske::bildKnopf('posterfID', isset($posterfID) ? $posterfID : 0, t('Standbild wählen')); ?>
        <p class="text-muted small">
            <?= t('Zu sehen, bevor jemand auf Abspielen drückt. Ohne Standbild zeigt der Browser das erste Bild des Videos — meist eine schwarze Fläche.') ?>
        </p>
    </div>
</div>

<hr>

<div class="form-group">
    <?= $form->label('titel', t('Worum geht es im Video?')) ?>
    <?= $form->text('titel', isset($titel) ? $titel : '', ['maxlength' => 255]) ?>
    <p class="text-muted small mb-0">
        <?= t('Dient Screenreadern als Beschriftung des Spielers. Kurz und sachlich, z. B. “Der Musterstamm stellt sich vor”.') ?>
    </p>
</div>

<div class="form-group">
    <?= $form->label('text', t('Bildunterschrift')) ?>
    <?= $form->textarea('text', isset($text) ? $text : '', ['rows' => 2]) ?>
    <p class="text-muted small mb-0">
        <?= t('Steht sichtbar unter dem Spieler. Kann leer bleiben.') ?>
    </p>
</div>

<hr>

<h5><?= t('Untertitel') ?></h5>
<p class="text-muted small">
    <?= t('WebVTT-Dateien (.vtt) aus der Dateiverwaltung. Untertitel sind nicht Zierrat: ohne sie ist das Video für Gehörlose und in lauter Umgebung unbrauchbar.') ?>
</p>

<div data-rep>
    <div class="v-rep__zeilen" data-rep-zeilen>
        <?php foreach ($spurenListe as $i => $s) { $zeile($i, $s); } ?>
    </div>

    <p class="v-rep__leer" data-rep-leer style="display:none">
        <?= t('Noch keine Untertitel.') ?>
    </p>

    <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

    <button type="button" class="btn btn-secondary" data-rep-neu>
        <?= t('Untertitel hinzufügen') ?>
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
        <?= $form->label('vorladen', t('Vorladen')) ?>
        <?= $form->select('vorladen', [
            'metadata' => t('Nur Länge und Maße'),
            'none' => t('Nichts, erst beim Abspielen'),
            'auto' => t('Der Browser entscheidet'),
        ], isset($vorladen) ? $vorladen : 'metadata') ?>
        <p class="text-muted small mb-0">
            <?= t('„Nur Länge und Maße“ ist der richtige Wert. Der Spieler kennt damit die Laufzeit, lädt aber noch keine Megabyte auf fremde Datentarife.') ?>
        </p>
    </div>
    <div class="col-md-4 form-group">
        <div class="form-check mt-4">
            <?= $form->checkbox('schleife', 1, isset($schleife) ? (bool) $schleife : false, ['class' => 'form-check-input']) ?>
            <?= $form->label('schleife', t('In Schleife abspielen'), ['class' => 'form-check-label']) ?>
        </div>
        <p class="text-muted small">
            <?= t('Nur für kurze Clips ohne Ton sinnvoll.') ?>
        </p>
    </div>
</div>

<hr>

<h5><?= t('Farben des Spielers') ?></h5>
<p class="text-muted small">
    <?= t('Die Bedienleiste liegt auf einem dunklen Verlauf über dem Video. Helle Farben sind dort gut zu sehen, dunkle kaum. Die Farbe der Symbole rechnet der Block selbst aus — auf einer hellen Fläche werden sie schwarz, auf einer dunklen weiß.') ?>
</p>

<?php
/**
 * Ein Farbfeld: Auswahl aus dem CD plus freier Wert.
 *
 * Der freie Wert steht immer da und ist nicht erst nach Umschalten
 * auf „Eigene Farbe“ zu sehen. Ein Feld, das aufpoppt, ist ein Feld,
 * das man beim Ausfüllen übersieht — und der Farbwähler des Browsers
 * zeigt nebenbei, was gerade eingestellt ist.
 */
$farbfeld = function ($name, $beschriftung, $hinweis, $wert, $eigen, $ersatz) use ($palette) {
    $gruppen = [
        'primaer' => t('Primärfarben'),
        'akzent' => t('Akzentfarben — laut CD für Ikons und Striche'),
        'neutral' => t('Neutral'),
    ];
    ?>
    <div class="col-md-6 form-group">
        <label class="form-label" for="<?= h($name) ?>"><?= h($beschriftung) ?></label>
        <select class="form-select" id="<?= h($name) ?>" name="<?= h($name) ?>">
            <?php foreach ($palette as $schluessel => $farben) { ?>
                <optgroup label="<?= h($gruppen[$schluessel] ?? $schluessel) ?>">
                    <?php foreach ($farben as $kuerzel => $farbe) { ?>
                        <option value="<?= h($kuerzel) ?>"<?= $wert === $kuerzel ? ' selected' : '' ?>>
                            <?= h($farbe[0]) ?> · <?= h($farbe[1]) ?>
                        </option>
                    <?php } ?>
                </optgroup>
            <?php } ?>
            <option value="eigen"<?= $wert === 'eigen' ? ' selected' : '' ?>><?= t('Eigene Farbe') ?></option>
        </select>

        <div class="input-group input-group-sm mt-2">
            <span class="input-group-text"><?= t('Eigene Farbe') ?></span>
            <input type="color" class="form-control form-control-color"
                   name="<?= h($name) ?>Eigen"
                   value="<?= h($eigen !== '' ? $eigen : $ersatz) ?>">
        </div>
        <p class="text-muted small mb-0"><?= h($hinweis) ?></p>
    </div>
    <?php
};
?>

<div class="row">
    <?php
    $farbfeld('akzent', t('Fortschritt, Lautstärke und Fokusring'),
        t('Gut sichtbar auf dunklem Grund — Wölflingsgelb ist die Voreinstellung.'),
        isset($akzent) ? $akzent : 'gelb',
        isset($akzentEigen) ? $akzentEigen : '', '#FFD208');

    $farbfeld('knopf', t('Großer Abspielknopf'),
        t('Die Fläche in der Bildmitte. Pfadiblau ist die Voreinstellung.'),
        isset($knopf) ? $knopf : 'blau',
        isset($knopfEigen) ? $knopfEigen : '', '#0E4278');
    ?>
</div>
