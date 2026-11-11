<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url;

$app = Application::getFacadeApplication();
$form = $app->make('helper/form');
$token = $app->make('token');

/**
 * @var array      $termine
 * @var array      $stufen
 * @var array      $frei
 * @var array|null $bearbeiten
 * @var int        $jahr
 */

$wert = function ($feld, $ersatz = '') use ($bearbeiten) {
    if (!is_array($bearbeiten)) {
        return $ersatz;
    }

    return isset($bearbeiten[$feld]) ? (string) $bearbeiten[$feld] : $ersatz;
};

// Beim Bearbeiten kommen beginn/ende als DATETIME; die Maske will sie
// getrennt als Datum und Uhrzeit.
$tagVon = $wert('beginnTag', substr($wert('beginn'), 0, 10));
$tagBis = $wert('endeTag', substr($wert('ende'), 0, 10));
$zeitVon = $wert('beginnZeit', substr($wert('beginn'), 11, 5));
$zeitBis = $wert('endeZeit', substr($wert('ende'), 11, 5));
$istGanz = is_array($bearbeiten) ? !empty($bearbeiten['ganztaegig']) : true;
$terminID = (int) $wert('terminID', '0');

$monate = ['', 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
$kurz = function ($datetime) use ($monate) {
    $t = strtotime($datetime);

    return (int) date('j', $t) . '. ' . $monate[(int) date('n', $t)] . ' ' . date('Y', $t);
};
?>

<div class="ccm-dashboard-content-full">
    <div class="row">

        <?php /* ---------- Formular ---------- */ ?>
        <div class="col-lg-5">
            <form method="post" action="<?= (string) Url::to('/dashboard/einfach_bdp/termine', 'sichern') ?>">
                <?= $token->output('bdp_termin_sichern') ?>
                <input type="hidden" name="terminID" value="<?= $terminID ?>">

                <h4><?= $terminID > 0 ? t('Termin ändern') : t('Neuer Termin') ?></h4>

                <div class="form-group">
                    <label class="form-label"><?= t('Titel') ?></label>
                    <input type="text" class="form-control" name="titel" maxlength="255"
                           value="<?= h($wert('titel')) ?>" required>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="ganztaegig" name="ganztaegig" value="1"
                               <?= $istGanz ? 'checked' : '' ?> data-ganztaegig>
                        <label class="form-check-label" for="ganztaegig"><?= t('Ganztägig') ?></label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 form-group">
                        <label class="form-label"><?= t('Beginn') ?></label>
                        <input type="date" class="form-control" name="beginnTag" value="<?= h($tagVon) ?>" required>
                    </div>
                    <div class="col-6 form-group" data-zeitfeld<?= $istGanz ? ' hidden' : '' ?>>
                        <label class="form-label"><?= t('Uhrzeit') ?></label>
                        <input type="time" class="form-control" name="beginnZeit" value="<?= h($zeitVon) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 form-group">
                        <label class="form-label"><?= t('Ende') ?></label>
                        <input type="date" class="form-control" name="endeTag" value="<?= h($tagBis) ?>">
                        <p class="text-muted small mb-0"><?= t('Leer lassen für eintägige Termine.') ?></p>
                    </div>
                    <div class="col-6 form-group" data-zeitfeld<?= $istGanz ? ' hidden' : '' ?>>
                        <label class="form-label"><?= t('Uhrzeit') ?></label>
                        <input type="time" class="form-control" name="endeZeit" value="<?= h($zeitBis) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= t('Stufe') ?></label>
                    <select class="form-select" name="stufe">
                        <?php foreach ($stufen as $handle => $s) { ?>
                            <option value="<?= h($handle) ?>"<?= $wert('stufe', 'alle') === $handle ? ' selected' : '' ?>>
                                <?= h($s['name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= t('Ort') ?></label>
                    <input type="text" class="form-control" name="ort" maxlength="255" value="<?= h($wert('ort')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label"><?= t('Beschreibung') ?></label>
                    <textarea class="form-control" rows="3" name="beschreibung"><?= h($wert('beschreibung')) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= t('Link') ?></label>
                    <input type="url" class="form-control" name="link" maxlength="512" value="<?= h($wert('link')) ?>">
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $terminID > 0 ? t('Änderung speichern') : t('Termin anlegen') ?>
                </button>
                <?php if ($terminID > 0) { ?>
                    <a class="btn btn-secondary" href="<?= (string) Url::to('/dashboard/einfach_bdp/termine') ?>">
                        <?= t('Abbrechen') ?>
                    </a>
                <?php } ?>
            </form>

            <hr>

            <?php /* ---------- Ferien und Feiertage automatisch ---------- */ ?>
            <form method="post" action="<?= (string) Url::to('/dashboard/einfach_bdp/termine', 'abrufen') ?>">
                <?= $token->output('bdp_ferien_abrufen') ?>
                <h5><?= t('Ferien und Feiertage holen') ?></h5>
                <p class="text-muted small">
                    <?= t('Von mehr-schulferien.de, für das Vorjahr und die drei folgenden Jahre. Weiter voraus legen die Ministerien nichts fest.') ?>
                </p>

                <div class="form-group">
                    <label class="form-label" for="land"><?= t('Bundesland') ?></label>
                    <select class="form-select" name="land" id="land">
                        <?php foreach ($laender as $kuerzel => $name) { ?>
                            <option value="<?= h($kuerzel) ?>"<?= $land === $kuerzel ? ' selected' : '' ?>>
                                <?= h($name) ?>
                            </option>
                        <?php } ?>
                    </select>
                    <p class="text-muted small mb-0">
                        <?= t('Beim Wechsel werden die bisher geholten Zeiträume ersetzt. Von Hand eingetragene bleiben.') ?>
                    </p>
                </div>

                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="erzwingen" name="erzwingen" value="1">
                    <label class="form-check-label" for="erzwingen">
                        <?= t('Auch Jahre neu holen, die schon aktuell sind') ?>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary"><?= t('Jetzt holen') ?></button>

                <?php if (count($protokoll) > 0) { ?>
                    <details class="mt-3">
                        <summary class="small"><?= t('Letzte Abrufe') ?></summary>
                        <table class="table table-sm mt-2 small">
                            <tbody>
                            <?php foreach ($protokoll as $p) { ?>
                                <tr>
                                    <td><?= h($p['land']) ?></td>
                                    <td><?= (int) $p['jahr'] ?></td>
                                    <td><?= h(date('d.m.Y H:i', strtotime($p['geholt']))) ?></td>
                                    <td>
                                        <?php if ($p['fehler'] !== '') { ?>
                                            <span class="text-danger"><?= h($p['fehler']) ?></span>
                                        <?php } else { ?>
                                            <?= (int) $p['anzahl'] ?> <?= t('Zeiträume') ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </details>
                <?php } ?>
            </form>

            <hr>

            <?php /* ---------- Ferien von Hand ---------- */ ?>
            <form method="post" action="<?= (string) Url::to('/dashboard/einfach_bdp/termine', 'ferien') ?>">
                <?= $token->output('bdp_ferien') ?>
                <h5><?= t('Ferien von Hand eintragen') ?></h5>
                <p class="text-muted small">
                    <?= t('Für Zeiträume, die die Schnittstelle nicht kennt, etwa bewegliche Ferientage der einzelnen Schule.') ?>
                </p>
                <div class="form-group">
                    <input type="text" class="form-control" name="ferienTitel"
                           placeholder="<?= t('z. B. beweglicher Ferientag') ?>" maxlength="255">
                </div>
                <div class="row">
                    <div class="col-6 form-group">
                        <input type="date" class="form-control" name="ferienVon">
                    </div>
                    <div class="col-6 form-group">
                        <input type="date" class="form-control" name="ferienBis">
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary"><?= t('Eintragen') ?></button>
            </form>

            <hr>

            <?php /* ---------- Feiertage rechnen (Rückfallebene) ---------- */ ?>
            <form method="post" action="<?= (string) Url::to('/dashboard/einfach_bdp/termine', 'feiertage') ?>">
                <?= $token->output('bdp_feiertage') ?>
                <h5><?= t('Feiertage berechnen') ?></h5>
                <p class="text-muted small">
                    <?= t('Rückfallebene, falls die Schnittstelle ausfällt: Die gesetzlichen Feiertage in Hessen ergeben sich aus dem Osterdatum, inklusive Fronleichnam. Sobald die Schnittstelle dasselbe Jahr liefert, ersetzt sie die gerechneten Einträge.') ?>
                </p>
                <div class="input-group" style="max-width:260px">
                    <input type="number" class="form-control" name="jahr" value="<?= (int) $jahr ?>" min="2000" max="2100">
                    <button class="btn btn-outline-secondary" type="submit"><?= t('Berechnen') ?></button>
                </div>
            </form>
        </div>

        <?php /* ---------- Liste ---------- */ ?>
        <div class="col-lg-7">
            <h4><?= t('Termine') ?> <span class="badge bg-secondary"><?= count($termine) ?></span></h4>

            <?php if (count($termine) === 0) { ?>
                <p class="text-muted"><?= t('Noch keine Termine.') ?></p>
            <?php } else { ?>
                <table class="table table-striped table-sm align-middle">
                    <thead>
                    <tr>
                        <th><?= t('Wann') ?></th>
                        <th><?= t('Was') ?></th>
                        <th><?= t('Stufe') ?></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($termine as $t) {
                        $s = isset($stufen[$t['stufe']]) ? $stufen[$t['stufe']] : $stufen['alle'];
                        $mehrtaegig = substr($t['beginn'], 0, 10) !== substr($t['ende'], 0, 10);
                        ?>
                        <tr>
                            <td style="white-space:nowrap">
                                <?= h($kurz($t['beginn'])) ?>
                                <?php if ($mehrtaegig) { ?>
                                    <br><span class="text-muted small">bis <?= h($kurz($t['ende'])) ?></span>
                                <?php } elseif (empty($t['ganztaegig'])) { ?>
                                    <br><span class="text-muted small"><?= h(substr($t['beginn'], 11, 5)) ?> Uhr</span>
                                <?php } ?>
                            </td>
                            <td><?= h($t['titel']) ?></td>
                            <td>
                                <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:<?= h($s['farbe']) ?>"></span>
                                <?= h($s['kurz']) ?>
                            </td>
                            <td style="white-space:nowrap">
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="<?= (string) Url::to('/dashboard/einfach_bdp/termine', 'bearbeiten', (int) $t['terminID']) ?>">
                                    <?= t('Ändern') ?>
                                </a>
                                <a class="btn btn-sm btn-outline-danger"
                                   href="<?= (string) Url::to('/dashboard/einfach_bdp/termine', 'loeschen', (int) $t['terminID'], $token->generate('bdp_termin_loeschen')) ?>"
                                   onclick="return confirm('<?= t('Diesen Termin wirklich löschen?') ?>')">
                                    <?= t('Löschen') ?>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>

            <h5 class="mt-4"><?= t('Ferien und Feiertage') ?> <span class="badge bg-secondary"><?= count($frei) ?></span></h5>
            <?php if (count($frei) === 0) { ?>
                <p class="text-muted"><?= t('Noch nichts eingetragen.') ?></p>
            <?php } else { ?>
                <ul class="list-unstyled small">
                    <?php foreach ($frei as $f) { ?>
                        <li>
                            <strong><?= h($f['titel']) ?></strong>
                            <?= h($kurz($f['beginn'])) ?><?= $f['beginn'] !== $f['ende'] ? ' – ' . h($kurz($f['ende'])) : '' ?>
                            <span class="text-muted">(<?= h($f['art']) ?>)</span>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</div>

<script>
    // Die Uhrzeitfelder verschwinden, solange „Ganztägig“ steht.
    (function () {
        var schalter = document.querySelector('[data-ganztaegig]');
        if (!schalter) { return; }
        var felder = document.querySelectorAll('[data-zeitfeld]');
        schalter.addEventListener('change', function () {
            felder.forEach(function (f) { f.hidden = schalter.checked; });
        });
    })();
</script>
