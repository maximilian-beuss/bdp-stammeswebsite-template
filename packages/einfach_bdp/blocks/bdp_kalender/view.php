<?php
defined('C5_EXECUTE') or die('Access Denied.');

use EinfachBdP\Kalender\Stufen;

/**
 * @var array  $termine
 * @var array  $kommend
 * @var array  $stufenKatalog
 * @var array  $gewaehlteStufen
 * @var string $datenUrl
 * @var string $icalUrl
 * @var string $ansicht
 * @var int    $zeigeFrei
 * @var int    $zeigeAbo
 * @var int    $zeigeUmschalter
 * @var int    $sechseckAktiviert
 */

$monatsnamen = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

/** Termin als lesbarer Zeitraum: „12.–19. Juli 2026“, „3. Mai 2026, 17:15“ */
$spanne = function (array $t) use ($monatsnamen) {
    $a = strtotime($t['beginn']);
    $e = strtotime($t['ende']);
    $ganz = !empty($t['ganztaegig']);

    $gleicherTag = date('Y-m-d', $a) === date('Y-m-d', $e);

    if ($gleicherTag) {
        $text = (int) date('j', $a) . '. ' . $monatsnamen[(int) date('n', $a)] . ' ' . date('Y', $a);

        return $ganz ? $text : $text . ', ' . date('H:i', $a) . '–' . date('H:i', $e) . ' Uhr';
    }

    if (date('Y-n', $a) === date('Y-n', $e)) {
        return (int) date('j', $a) . '.–' . (int) date('j', $e) . '. '
            . $monatsnamen[(int) date('n', $a)] . ' ' . date('Y', $a);
    }

    return (int) date('j', $a) . '. ' . $monatsnamen[(int) date('n', $a)]
        . ($ganz && date('Y', $a) !== date('Y', $e) ? ' ' . date('Y', $a) : '')
        . ' bis ' . (int) date('j', $e) . '. ' . $monatsnamen[(int) date('n', $e)] . ' ' . date('Y', $e);
};
?>
<div class="v-kal"
     data-kal
     data-kal-daten="<?= h($datenUrl) ?>"
     data-kal-ansicht="<?= h($ansicht) ?>"
     data-kal-stufen="<?= h(implode(',', $gewaehlteStufen)) ?>"
     data-kal-frei="<?= (int) $zeigeFrei ?>"
     <?php if (!empty($sechseckAktiviert)) { ?>data-kal-sechseck<?php } ?>>

    <div class="v-kal__kopf">
        <?php if ($zeigeUmschalter) { ?>
            <div class="v-kal__ansichten" role="group" aria-label="Ansicht wählen" data-kal-ansichten hidden>
                <?php
                $ansichten = [
                    'monat' => t('Monat'),
                    'halbjahr' => t('Halbjahr'),
                    'jahr' => t('Jahr'),
                    'liste' => t('Liste'),
                ];
                foreach ($ansichten as $wert => $beschriftung) { ?>
                    <button type="button" class="v-kal__ansicht" data-kal-ansicht-zu="<?= h($wert) ?>"
                            aria-pressed="<?= $wert === $ansicht ? 'true' : 'false' ?>">
                        <?= h($beschriftung) ?>
                    </button>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="v-kal__steuer" data-kal-steuer hidden>
            <button type="button" class="v-kal__blaettern" data-kal-zurueck aria-label="<?= t('Zurück') ?>">
                <span aria-hidden="true"></span>
            </button>
            <strong class="v-kal__zeitraum" data-kal-zeitraum aria-live="polite"></strong>
            <button type="button" class="v-kal__blaettern" data-kal-vor aria-label="<?= t('Weiter') ?>">
                <span aria-hidden="true"></span>
            </button>
            <button type="button" class="v-kal__heute" data-kal-heute><?= t('Heute') ?></button>
        </div>
    </div>

    <?php
    /* Die Legende ist keine Dekoration: ohne sie wäre die Stufe eines
       Termins nur an der Farbe erkennbar, und Farbe allein darf keine
       Information tragen ✅ WCAG 1.4.1. Deshalb steht die Stufe
       zusätzlich im Text jedes Termins. */
    ?>
    <ul class="v-kal__legende">
        <?php foreach ($stufenKatalog as $handle => $stufe) { ?>
            <?php if (count($gewaehlteStufen) > 0 && !in_array($handle, $gewaehlteStufen, true)) { continue; } ?>
            <li>
                <span class="v-kal__punkt" style="--stufe:<?= h($stufe['farbe']) ?>" aria-hidden="true"></span>
                <?= h($stufe['name']) ?>
            </li>
        <?php } ?>
        <?php if ($zeigeFrei) { ?>
            <?php // In der Listenansicht gibt es keine freien Tage, dann blendet kalender.js diesen Punkt aus. ?>
            <li data-kal-legende-frei>
                <span class="v-kal__punkt v-kal__punkt--frei" aria-hidden="true"></span>
                <?= t('Schulfrei: Ferien, Feiertage, Wochenende') ?>
            </li>
        <?php } ?>
    </ul>

    <?php // Hierhin zeichnet kalender.js. Ohne Skript bleibt es leer. ?>
    <div class="v-kal__flaeche" data-kal-flaeche></div>

    <?php
    /* Die Terminliste steht immer im HTML. Ohne JavaScript ist sie die
       ganze Ausgabe; mit JavaScript blendet das Skript sie aus, sobald
       eine der gezeichneten Ansichten steht. */
    ?>
    <div class="v-kal__ersatz" data-kal-ersatz>
        <?php if (count($kommend) === 0) { ?>
            <p><?= t('Zurzeit stehen keine Termine an.') ?></p>
        <?php } else { ?>
            <?php
            /* Derselbe Aufbau wie in der gezeichneten Listenansicht:
               einen Monat zurück, dann alles Kommende. Vergangenes ist
               abgeblendet, dazwischen steht die Heute-Marke — aber nur,
               wenn es überhaupt etwas Vergangenes gibt. */
            $heuteTag = date('Y-m-d');
            $heuteGesetzt = false;
            $gabVergangenes = false;

            foreach ($kommend as $t) {
                if (substr($t['ende'], 0, 10) < $heuteTag) {
                    $gabVergangenes = true;
                    break;
                }
            }
            ?>
            <ol class="v-kal__liste">
                <?php foreach ($kommend as $t) {
                    $stufe = Stufen::eine($t['stufe']);
                    $vorbei = substr($t['ende'], 0, 10) < $heuteTag;

                    if (!$vorbei && $gabVergangenes && !$heuteGesetzt) {
                        $heuteGesetzt = true;
                        ?>
                        <li class="v-kal__heutemarke">
                            <span><?= t('Heute') ?> · <?= (int) date('j') ?>. <?= h($monatsnamen[(int) date('n')]) ?></span>
                        </li>
                    <?php } ?>
                    <li class="v-kal__eintrag<?= $vorbei ? ' v-kal__eintrag--vorbei' : '' ?>"
                        style="--stufe:<?= h($stufe['farbe']) ?>">
                        <time class="v-kal__wann" datetime="<?= h(substr($t['beginn'], 0, 10)) ?>">
                            <?= h($spanne($t)) ?>
                        </time>
                        <h4 class="v-kal__was"><?= h($t['titel']) ?></h4>
                        <p class="v-kal__stufe"><?= h($stufe['name']) ?><?php
                            if (trim((string) $t['ort']) !== '') {
                                echo ' · ' . h($t['ort']);
                            } ?></p>
                        <?php if (trim((string) $t['beschreibung']) !== '') { ?>
                            <p class="v-kal__mehr"><?= nl2br(h($t['beschreibung'])) ?></p>
                        <?php } ?>
                        <?php if (trim((string) $t['link']) !== '') { ?>
                            <p><a class="pfeil-link" href="<?= h($t['link']) ?>"><?= t('Mehr dazu') ?></a></p>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ol>
        <?php } ?>
    </div>

    <?php if ($zeigeAbo) { ?>
        <p class="v-kal__abo">
            <?php
            /* Ohne JavaScript bleibt es ein Link auf die Datei — der
               Kalender lässt sich dann herunterladen und von Hand
               eintragen. Mit JavaScript fängt der Dialog den Klick ab
               und bietet die drei Wege an. */
            ?>
            <a class="btn btn--linie" href="<?= h($icalUrl) ?>" data-kal-abo>
                <?= t('Kalender abonnieren') ?>
            </a>
            <span class="v-kal__abo-hinweis">
                <?= t('Einmal eintragen, dann erscheinen neue Termine von allein im eigenen Kalender.') ?>
            </span>
        </p>

        <?php
        /* Der Abo-Dialog.

           Alle drei Wege zeigen auf dieselbe Datei; es gibt keine
           zweite Datenhaltung je Anbieter. Google bekommt die Adresse
           als Parameter und holt sie danach selbst ab, webcal:// ist
           derselbe Abruf mit anderem Schema, und die dritte Zeile ist
           schlicht die Adresse zum Kopieren.

           Ein echtes <dialog>: Escape, Fokusfalle und Inertheit des
           Hintergrunds bringt der Browser mit ✅ WCAG 2.1.2 */
        ?>
        <dialog class="v-kal__abofenster" data-kal-abofenster
                aria-labelledby="abo-titel-<?= (int) $bID ?>">
            <h3 class="v-kal__abofenster-titel" id="abo-titel-<?= (int) $bID ?>">
                <?= t('Stammeskalender abonnieren') ?>
            </h3>
            <p class="v-kal__abofenster-text">
                <?= t('Such dir aus, womit du deine Termine verwaltest. Der Kalender bleibt danach aktuell, ohne dass du etwas tun musst.') ?>
            </p>

            <ul class="v-kal__abowege">
                <li>
                    <a class="v-kal__aboweg" href="<?= h($googleUrl) ?>" rel="noopener external" target="_blank">
                        <span class="v-kal__aboweg-name"><?= t('Google Kalender') ?></span>
                        <span class="v-kal__aboweg-text">
                            <?= t('Öffnet Google Kalender und fragt dort nach, ob du den Kalender hinzufügen willst.') ?>
                        </span>
                    </a>
                </li>
                <li>
                    <a class="v-kal__aboweg" href="<?= h($webcalUrl) ?>">
                        <span class="v-kal__aboweg-name"><?= t('Apple Kalender') ?></span>
                        <span class="v-kal__aboweg-text">
                            <?= t('Auf iPhone, iPad und Mac öffnet sich die Kalender-App und fragt nach dem Abo. Outlook versteht diesen Weg auch.') ?>
                        </span>
                    </a>
                </li>
                <li>
                    <div class="v-kal__aboweg v-kal__aboweg--kopieren">
                        <span class="v-kal__aboweg-name"><?= t('Andere Kalender') ?></span>
                        <span class="v-kal__aboweg-text">
                            <?= t('Diese Adresse in deinem Kalenderprogramm als Abo eintragen — in Outlook unter „Kalender aus dem Internet abonnieren“.') ?>
                        </span>
                        <span class="v-kal__abofeld">
                            <input type="text" readonly value="<?= h($icalUrl) ?>"
                                   data-kal-aboadresse
                                   aria-label="<?= t('Adresse des Kalenders') ?>">
                            <button type="button" class="btn btn--klein" data-kal-abokopieren>
                                <?= t('Kopieren') ?>
                            </button>
                        </span>
                        <a class="pfeil-link" href="<?= h($icalUrl) ?>" download>
                            <?= t('Oder die Datei herunterladen') ?>
                        </a>
                    </div>
                </li>
            </ul>

            <p class="v-kal__abofenster-schluss">
                <button type="button" class="btn btn--linie" data-kal-abozu><?= t('Schließen') ?></button>
                <span class="v-kal__abomeldung" role="status" data-kal-abomeldung></span>
            </p>
        </dialog>
    <?php } ?>
</div>
