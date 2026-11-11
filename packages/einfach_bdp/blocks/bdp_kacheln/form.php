<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;
use EinfachBdP\Werkzeug;

$form = Application::getFacadeApplication()->make('helper/form');
$seitenwaehler = Application::getFacadeApplication()->make('helper/form/page_selector');
Maske::assets();

/** @var array $kachelListe */
$kachelListe = isset($kachelListe) ? $kachelListe : [];

$zeile = function ($i, array $k = []) {
    $n = 'kacheln[' . $i . ']';
    ?>
    <div class="v-rep__zeile" data-rep-zeile>
        <?php Maske::zeilenkopf(t('Kachel')); ?>
        <div class="v-rep__koerper">
            <div class="v-rep__paar">
                <div>
                    <?php Maske::bildKnopf($n . '[bildfID]', Werkzeug::feld($k, 'bildfID', 0), t('Bild wählen')); ?>
                </div>
                <div>
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label class="form-label"><?= t('Titel') ?></label>
                            <input type="text" class="form-control" name="<?= $n ?>[titel]"
                                   value="<?= h(Werkzeug::feld($k, 'titel')) ?>" maxlength="160">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="form-label"><?= t('Etikett') ?></label>
                            <input type="text" class="form-control" name="<?= $n ?>[etikett]"
                                   value="<?= h(Werkzeug::feld($k, 'etikett')) ?>" maxlength="40"
                                   placeholder="<?= t('optional') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('Text') ?></label>
                        <textarea class="form-control" rows="2" name="<?= $n ?>[text]"><?= h(Werkzeug::feld($k, 'text')) ?></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label"><?= t('Ziel') ?></label>
                        <input type="text" class="form-control" name="<?= $n ?>[ziel]"
                               value="<?= h(Werkzeug::feld($k, 'ziel')) ?>">
                        <p class="text-muted small mb-0">
                            <?= t('Eine Adresse, oder {CCM:BASE_URL}/pfad für eine Seite dieser Website.') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};

$quelle = isset($quelle) ? $quelle : 'unterseiten';
?>

<div class="form-group">
    <?= $form->label('quelle', t('Woher kommen die Kacheln?')) ?>
    <div class="form-check">
        <input type="radio" class="form-check-input" id="quelle-unterseiten" name="quelle"
               value="unterseiten" <?= !in_array($quelle, ['berichte', 'manuell'], true) ? 'checked' : '' ?> data-kachelquelle>
        <label class="form-check-label" for="quelle-unterseiten">
            <strong><?= t('Automatisch aus den Unterseiten') ?></strong><br>
            <span class="text-muted small">
                <?= t('Jede Unterseite wird eine Kachel, mit ihrem Namen, ihrer Kurzbeschreibung und ihrem Seitenbild. Neue Unterseiten erscheinen von allein.') ?>
            </span>
        </label>
    </div>
    <div class="form-check mt-2">
        <input type="radio" class="form-check-input" id="quelle-berichte" name="quelle"
               value="berichte" <?= $quelle === 'berichte' ? 'checked' : '' ?> data-kachelquelle>
        <label class="form-check-label" for="quelle-berichte">
            <strong><?= t('Alle Berichte (Seitentyp „Bericht")') ?></strong><br>
            <span class="text-muted small">
                <?= t('Zeigt alle Seiten vom Typ „Bericht" mit Vorschaubild, Berichtsdatum, Titel und Kurzbeschreibung, neueste zuerst.') ?>
            </span>
        </label>
    </div>
    <div class="form-check mt-2">
        <input type="radio" class="form-check-input" id="quelle-manuell" name="quelle"
               value="manuell" <?= $quelle === 'manuell' ? 'checked' : '' ?> data-kachelquelle>
        <label class="form-check-label" for="quelle-manuell">
            <strong><?= t('Von Hand zusammengestellt') ?></strong><br>
            <span class="text-muted small">
                <?= t('Für Wegweiser, die auch auf andere Seiten zeigen dürfen.') ?>
            </span>
        </label>
    </div>
</div>

<div data-kachelbereich="unterseiten"<?= in_array($quelle, ['berichte', 'manuell'], true) ? ' hidden' : '' ?>>
    <div class="form-group">
        <?= $form->label('elternID', t('Unterseiten von')) ?>
        <?= $seitenwaehler->selectPage('elternID', isset($elternID) ? $elternID : 0) ?>
        <p class="text-muted small mb-0">
            <?= t('Leer lassen für die Unterseiten der Seite, auf der dieser Block steht.') ?>
        </p>
    </div>
</div>

<div data-kachelbereich="manuell"<?= $quelle !== 'manuell' ? ' hidden' : '' ?>>
    <div data-rep>
        <div class="v-rep__zeilen" data-rep-zeilen>
            <?php foreach ($kachelListe as $i => $k) { $zeile($i, $k); } ?>
        </div>

        <p class="v-rep__leer" data-rep-leer style="display:none">
            <?= t('Noch keine Kachel. Unten hinzufügen.') ?>
        </p>

        <template data-rep-vorlage><?php $zeile('__I__'); ?></template>

        <button type="button" class="btn btn-secondary" data-rep-neu>
            <?= t('Kachel hinzufügen') ?>
        </button>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-3 form-group">
        <?= $form->label('spalten', t('Spalten')) ?>
        <?= $form->select('spalten', [
            'auto' => t('Automatisch'),
            '2' => t('Zwei'),
            '3' => t('Drei'),
            '4' => t('Vier'),
        ], isset($spalten) ? $spalten : 'auto') ?>
    </div>
    <div class="col-md-4 form-group">
        <?= $form->label('knopfText', t('Beschriftung des Pfeil-Links')) ?>
        <?= $form->text('knopfText', isset($knopfText) ? $knopfText : 'Mehr erfahren', ['maxlength' => 80]) ?>
        <p class="text-muted small mb-0"><?= t('Leer lassen, um ihn wegzulassen.') ?></p>
    </div>
    <div class="col-md-5 form-group">
        <div class="form-check mt-4">
            <?= $form->checkbox('zeigeBild', 1, isset($zeigeBild) ? (bool) $zeigeBild : true, ['class' => 'form-check-input']) ?>
            <?= $form->label('zeigeBild', t('Bilder anzeigen'), ['class' => 'form-check-label']) ?>
        </div>
        <div class="form-check">
            <?= $form->checkbox('zeigeText', 1, isset($zeigeText) ? (bool) $zeigeText : true, ['class' => 'form-check-input']) ?>
            <?= $form->label('zeigeText', t('Texte anzeigen'), ['class' => 'form-check-label']) ?>
        </div>
    </div>
</div>

<script>
    /* Nur die Felder zeigen, die zur gewählten Quelle gehören. Beide
       gleichzeitig sichtbar zu lassen führt regelmäßig dazu, dass
       jemand Kacheln einträgt und sich wundert, warum sie nicht
       erscheinen. */
    (function () {
        var schalter = document.querySelectorAll('[data-kachelquelle]');
        var bereiche = document.querySelectorAll('[data-kachelbereich]');

        function zeigen() {
            var gewaehlt = 'unterseiten';
            schalter.forEach(function (s) { if (s.checked) { gewaehlt = s.value; } });
            bereiche.forEach(function (b) {
                b.hidden = b.getAttribute('data-kachelbereich') !== gewaehlt;
            });
        }

        schalter.forEach(function (s) { s.addEventListener('change', zeigen); });
        zeigen();
    })();
</script>
