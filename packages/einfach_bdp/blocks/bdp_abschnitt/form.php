<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use EinfachBdP\Maske;

$app = Application::getFacadeApplication();
$form = $app->make('helper/form');
$editor = $app->make('editor');
Maske::assets();
?>

<div class="row">
    <div class="col-md-8 form-group">
        <?= $form->label('ueberschrift', t('Überschrift')) ?>
        <?= $form->text('ueberschrift', isset($ueberschrift) ? $ueberschrift : '', ['maxlength' => 255]) ?>
    </div>
    <div class="col-md-4 form-group">
        <?= $form->label('ebene', t('Ebene')) ?>
        <?= $form->select('ebene', [
            'h2' => t('Hauptüberschrift'),
            'h3' => t('Unterüberschrift'),
            'keine' => t('Keine Überschrift'),
        ], isset($ebene) ? $ebene : 'h2') ?>
    </div>
</div>

<div class="form-group">
    <?= $form->label('text', t('Text')) ?>
    <?= $editor->outputBlockEditModeEditor('text', isset($textFuerEditor) ? $textFuerEditor : '') ?>
</div>

<hr>

<h5><?= t('Bild in diesem Abschnitt') ?></h5>
<p class="text-muted small">
    <?= t('Für ein Bild, um das der Text herumfließen soll. Der Umfluss bleibt auf diesen Abschnitt begrenzt. Brauchst du ein Bild für sich allein oder eine Bildreihe, nimm den Block „Bild“.') ?>
</p>

<div class="v-rep__paar">
    <div>
        <?php Maske::bildKnopf('bildfID', isset($bildfID) ? $bildfID : 0, t('Bild wählen')); ?>
    </div>
    <div>
        <div class="form-group">
            <?= $form->label('bildStil', t('Anordnung')) ?>
            <?= $form->select('bildStil', [
                'kein' => t('Kein Bild'),
                'rechts' => t('Rechts, Text fließt herum'),
                'links' => t('Links, Text fließt herum'),
                'oben' => t('Über dem Abschnitt, volle Breite'),
                'unten' => t('Unter dem Abschnitt, volle Breite'),
            ], isset($bildStil) ? $bildStil : 'kein') ?>
        </div>
        <div class="form-group mb-0">
            <?= $form->label('bildText', t('Bildunterschrift')) ?>
            <?= $form->text('bildText', isset($bildText) ? $bildText : '', ['maxlength' => 255]) ?>
            <p class="text-muted small mb-0">
                <?= t('Dient zugleich als Bildbeschreibung für Screenreader. Leer lassen, wenn das Bild nur schmückt.') ?>
            </p>
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6 form-group">
        <?= $form->label('flaeche', t('Fläche')) ?>
        <?= $form->select('flaeche', [
            'weiss' => t('Weiß'),
            'gedaempft' => t('Hellgrau abgesetzt'),
            'sand' => t('Helles Gelb, warm abgesetzt'),
            'gelb' => t('Wölflingsgelb, blauer Text'),
            'blau' => t('Pfadiblau, heller Text'),
        ], isset($flaeche) ? $flaeche : 'weiss') ?>
        <p class="text-muted small mb-0">
            <?= t('Abschnitte hintereinander ruhig abwechseln — eine Seite aus lauter weißen Abschnitten wirkt schnell steril. Zwei farbige direkt nacheinander sind dagegen unruhig.') ?>
        </p>
    </div>

    <div class="col-md-6 form-group">
        <?= $form->label('ikon', t('Ikon neben der Überschrift')) ?>
        <select class="form-select" id="ikon" name="ikon">
            <option value=""><?= t('Kein Ikon') ?></option>
            <?php
            $gruppenNamen = [
                'unterwegs' => t('Unterwegs'),
                'lager' => t('Lager'),
                'stamm' => t('Stamm und Natur'),
                'zeichen' => t('Zeichen'),
            ];
            foreach ((isset($ikonGruppen) ? $ikonGruppen : []) as $schluessel => $gruppe) { ?>
                <optgroup label="<?= h($gruppenNamen[$schluessel] ?? $schluessel) ?>">
                    <?php foreach ($gruppe as $kuerzel => $name) { ?>
                        <option value="<?= h($kuerzel) ?>"<?= (isset($ikon) && $ikon === $kuerzel) ? ' selected' : '' ?>>
                            <?= h($name) ?>
                        </option>
                    <?php } ?>
                </optgroup>
            <?php } ?>
        </select>
        <p class="text-muted small mb-0">
            <?= t('Aus dem BdP-Zeichensatz. Es muss zum Text passen — ein Ikon, das nur schmückt, ist laut CD nicht vorgesehen. Die Farbe ergibt sich aus der Fläche.') ?>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <?= $form->label('ankerName', t('Sprungmarke')) ?>
        <?= $form->text('ankerName', isset($ankerName) ? $ankerName : '', ['maxlength' => 80]) ?>
        <p class="text-muted small mb-0">
            <?= t('Optional. Erlaubt einen Link direkt auf diesen Abschnitt, z. B. #beitrag. Nur Kleinbuchstaben, Ziffern und Bindestriche.') ?>
        </p>
    </div>
</div>

<?php Maske::platzhalterhilfe(); ?>
