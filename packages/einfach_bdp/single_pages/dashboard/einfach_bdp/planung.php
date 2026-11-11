<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url;

$app = Application::getFacadeApplication();
$form = $app->make('helper/form');
$token = $app->make('token');

/**
 * @var int   $jahr
 * @var array $stufen
 * @var array $termine
 * @var array $ideen
 * @var array $frei
 */

$monate = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

$entwuerfe = 0;
foreach ($termine as $t) {
    if (!empty($t['entwurf'])) {
        ++$entwuerfe;
    }
}

/* Die Daten für das Skript. Als JSON in einem script-Element und nicht
   als data-Attribute: Es sind verschachtelte Listen, und die in
   Attribute zu quetschen wäre nur schwerer zu lesen. */
$daten = [
    'jahr' => $jahr,
    'stufen' => $stufen,
    'termine' => array_map(function ($t) {
        return [
            'id' => (int) $t['terminID'],
            'titel' => $t['titel'],
            'von' => substr($t['beginn'], 0, 10),
            'bis' => substr($t['ende'], 0, 10),
            'stufe' => $t['stufe'],
            'entwurf' => (bool) $t['entwurf'],
            'ort' => $t['ort'],
            'beschreibung' => $t['beschreibung'],
        ];
    }, $termine),
    'ideen' => array_map(function ($i) {
        return [
            'id' => (int) $i['ideeID'],
            'titel' => $i['titel'],
            'tage' => (int) $i['tage'],
            'stufe' => $i['stufe'],
            'notiz' => $i['notiz'],
        ];
    }, $ideen),
    'frei' => array_map(function ($f) {
        return ['titel' => $f['titel'], 'von' => $f['beginn'], 'bis' => $f['ende'], 'art' => $f['art']];
    }, $frei),
    'basis' => (string) Url::to('/dashboard/einfach_bdp/planung'),
    'token' => $token->generate('bdp_planung'),
];
?>

<div class="ccm-dashboard-content-full v-plan" data-plan>

    <script type="application/json" data-plan-daten><?= json_encode($daten, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

    <div class="v-plan__kopf">
        <div class="v-plan__jahr">
            <a class="btn btn-sm btn-outline-secondary"
               href="<?= (string) Url::to('/dashboard/einfach_bdp/planung', $jahr - 1) ?>">&larr;</a>
            <strong><?= (int) $jahr ?></strong>
            <a class="btn btn-sm btn-outline-secondary"
               href="<?= (string) Url::to('/dashboard/einfach_bdp/planung', $jahr + 1) ?>">&rarr;</a>
        </div>

        <p class="v-plan__stand">
            <?= t('%s Termine im Jahr', count($termine)) ?>
            <?php if ($entwuerfe > 0) { ?>
                · <span class="v-plan__entwurfzahl"><?= t('%s Entwürfe', $entwuerfe) ?></span>
            <?php } ?>
        </p>

        <?php if ($entwuerfe > 0) { ?>
            <form method="post" action="<?= (string) Url::to('/dashboard/einfach_bdp/planung', 'alle_veroeffentlichen') ?>">
                <?= $token->output('bdp_planung') ?>
                <input type="hidden" name="jahr" value="<?= (int) $jahr ?>">
                <button type="submit" class="btn btn-sm btn-primary">
                    <?= t('Alle %s Entwürfe veröffentlichen', $entwuerfe) ?>
                </button>
            </form>
        <?php } ?>

        <?php
        /* Der Zeilenregler. Ein input[type=range] und kein eigener
           Schieber: Tastatur, Screenreader und Touch bringt der
           Browser mit, und der Wertebereich steht im Markup. */
        ?>
        <p class="v-plan__zeile">
            <label for="plan-zeile"><?= t('Zeilenhöhe') ?></label>
            <input type="range" id="plan-zeile" data-plan-zeile
                   min="0.4" max="2" step="0.05" value="1.35">
            <output class="v-plan__zeile-wert" for="plan-zeile" data-plan-zeile-wert>1,35 rem</output>
        </p>

        <?php
        /* Vollbild. Beim Planen will man das Jahr sehen und sonst
           nichts — nicht die Werkzeugleiste, nicht das Seitenmenü,
           nicht die Brotkrumen. aria-pressed statt zweier Knöpfe,
           damit Screenreader den Zustand kennen ✅ WCAG 4.1.2 */
        ?>
        <button type="button" class="btn btn-sm btn-outline-secondary v-plan__vollbild"
                data-plan-vollbild aria-pressed="false">
            <span data-plan-vollbild-text><?= t('Vollbild') ?></span>
        </button>

        <?php
        /* Die Bedienhinweise. Man liest sie einmal und danach nie
           wieder — als drei Zeilen über dem Jahr nähmen sie
           dauerhaft Platz weg, den die Monatsspalten besser
           gebrauchen können.

           <details> und kein eigenes Aufklapp-Skript: Auf- und
           Zuklappen, Tastaturbedienung und die Ansage des Zustands
           bringt der Browser mit ✅ WCAG 4.1.2 */
        ?>
        <details class="v-plan__hilfe">
            <summary class="v-plan__hilfe-knopf" title="<?= t('Hinweise zur Bedienung') ?>">
                <span aria-hidden="true">i</span>
                <span class="v-plan__hilfe-wort"><?= t('Hinweise zur Bedienung') ?></span>
            </summary>

            <div class="v-plan__hilfe-text">
                <p><?= t('Links sammelst du, was stattfinden soll. Zieh eine Idee in einen Tag, dann liegt sie als Entwurf im Jahr und lässt sich weiter verschieben. Entwürfe sind gestreift und stehen weder im Kalender auf der Website noch im Abo — erst das Veröffentlichen macht sie sichtbar.') ?></p>
                <p><?= t('Ohne Maus: Termin anklicken, dann „Verschieben“ — dort gibst du das Datum ein.') ?></p>
                <p><?= t('Mit dem Regler stellst du ein, wie hoch ein Tag ist — eng für das ganze Jahr auf einen Blick, hoch für lesbare Titel.') ?></p>
            </div>
        </details>
    </div>

    <div class="v-plan__flaeche">

        <?php /* ---------- Ideenspeicher ---------- */ ?>
        <aside class="v-plan__speicher" data-plan-speicher>
            <h3 class="v-plan__titel"><?= t('Ideenspeicher') ?></h3>

            <form class="v-plan__neu" data-plan-neu>
                <div class="mb-2">
                    <label class="form-label small" for="idee-titel"><?= t('Was soll stattfinden?') ?></label>
                    <input type="text" class="form-control form-control-sm" id="idee-titel"
                           name="titel" maxlength="255" required>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <label class="form-label small" for="idee-tage"><?= t('Tage') ?></label>
                        <input type="number" class="form-control form-control-sm" id="idee-tage"
                               name="tage" value="1" min="1" max="365">
                    </div>
                    <div class="col-8">
                        <label class="form-label small" for="idee-stufe"><?= t('Stufe') ?></label>
                        <select class="form-select form-select-sm" id="idee-stufe" name="stufe">
                            <?php foreach ($stufen as $handle => $s) { ?>
                                <option value="<?= h($handle) ?>"><?= h($s['name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-secondary w-100"><?= t('In den Speicher') ?></button>
            </form>

            <ul class="v-plan__ideen" data-plan-ideen></ul>

            <p class="v-plan__leer" data-plan-ideen-leer>
                <?= t('Noch nichts gesammelt.') ?>
            </p>
        </aside>

        <?php /* ---------- Das Jahr ---------- */ ?>
        <div class="v-plan__jahrraster" data-plan-jahr></div>
    </div>

    <?php /* ---------- Menü zu einem Termin ---------- */ ?>
    <dialog class="v-plan__menue" data-plan-menue>
        <h4 class="v-plan__menue-titel" data-plan-menue-titel></h4>
        <p class="v-plan__menue-wann" data-plan-menue-wann></p>

        <div class="v-plan__menue-feld">
            <label class="form-label small" for="plan-datum"><?= t('Beginnt am') ?></label>
            <div class="input-group input-group-sm">
                <input type="date" class="form-control" id="plan-datum" data-plan-datum>
                <button type="button" class="btn btn-primary" data-plan-verschieben><?= t('Verschieben') ?></button>
            </div>
        </div>

        <div class="v-plan__menue-feld">
            <label class="form-label small" for="plan-tage"><?= t('Dauer in Tagen') ?></label>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control" id="plan-tage" min="1" max="365" data-plan-tage>
                <button type="button" class="btn btn-primary" data-plan-dauer><?= t('Ändern') ?></button>
            </div>
        </div>

        <div class="v-plan__menue-knoepfe">
            <button type="button" class="btn btn-sm btn-success" data-plan-veroeffentlichen></button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-plan-zurueck>
                <?= t('Zurück in den Speicher') ?>
            </button>
            <a class="btn btn-sm btn-outline-secondary" data-plan-bearbeiten>
                <?= t('Alle Felder bearbeiten') ?>
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger" data-plan-loeschen>
                <?= t('Löschen') ?>
            </button>
        </div>

        <p class="v-plan__menue-schluss">
            <button type="button" class="btn btn-sm btn-secondary" data-plan-menue-zu><?= t('Schließen') ?></button>
            <span class="v-plan__meldung" role="status" data-plan-meldung></span>
        </p>
    </dialog>
</div>

<link rel="stylesheet" href="<?= DIR_REL ?>/packages/einfach_bdp/css/planung.css?v=8">
<script src="<?= DIR_REL ?>/packages/einfach_bdp/js/planung.js?v=4"></script>
