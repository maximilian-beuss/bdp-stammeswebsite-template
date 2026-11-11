<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array  $schritteListe
 * @var int    $anzahl
 * @var string $hinausTitel
 * @var string $hinausTextHtml
 * @var int    $merken
 * @var string $merkschluessel
 */

if ($anzahl === 0) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p>' . t('Noch keine Schritte. Block bearbeiten und Schritte hinzufügen.') . '</p>';
    }

    return;
}
?>
<div class="v-antrag"
     data-antrag
     data-antrag-merken="<?= (int) $merken ?>"
     data-antrag-schluessel="<?= h($merkschluessel) ?>">

    <?php
    /* Die Schrittleiste ist eine Liste von Knöpfen und keine
       Navigation: sie führt nicht auf andere Seiten, sondern schaltet
       innerhalb desselben Dokuments um. Ohne JavaScript ist sie
       nutzlos und wird deshalb erst vom Skript eingeblendet. */
    ?>
    <ol class="v-antrag__leiste" data-antrag-leiste hidden>
        <?php foreach ($schritteListe as $i => $s) { ?>
            <li class="v-antrag__marke">
                <button type="button"
                        class="v-antrag__marke-knopf"
                        data-antrag-zu="<?= $i ?>"
                        aria-current="<?= $i === 0 ? 'step' : 'false' ?>">
                    <span class="v-antrag__marke-zahl"><?= $i + 1 ?></span>
                    <span class="v-antrag__marke-text"><?= h($s['titel']) ?></span>
                </button>
            </li>
        <?php } ?>
    </ol>

    <p class="v-antrag__stand" data-antrag-stand aria-live="polite" hidden></p>

    <div class="v-antrag__schritte">
        <?php foreach ($schritteListe as $i => $s) { ?>
            <section class="v-antrag__schritt<?= $s['abschluss'] ? ' v-antrag__schritt--fertig' : '' ?>"
                     data-antrag-schritt="<?= $i ?>"
                     <?= $s['abschluss'] ? 'data-antrag-fertig' : '' ?>
                     aria-labelledby="antrag-<?= h($merkschluessel) ?>-<?= $i ?>">

                <h3 class="v-antrag__titel" id="antrag-<?= h($merkschluessel) ?>-<?= $i ?>">
                    <span class="v-antrag__zahl" aria-hidden="true"><?= $i + 1 ?></span>
                    <?= h($s['titel']) ?>
                </h3>

                <div class="v-antrag__text">
                    <?= $s['text'] ?>
                </div>

                <?php if ($s['bild'] !== '') { ?>
                    <img class="v-antrag__bild" src="<?= h($s['bild']) ?>" alt="" loading="lazy">
                <?php } ?>

                <?php if ($s['einbettung'] !== '') { ?>
                    <?php
                    /* Ohne vorgeschaltete Einwilligung.

                       Der Einwilligungsschalter des Themes sitzt vor
                       Einbettungen, die ungefragt mitkommen — die Karte
                       auf der Kontaktseite etwa lädt, ohne dass jemand
                       danach gefragt hätte.

                       Hier ist es umgekehrt: Wer auf diesem Schritt
                       steht, ist dem Antrag Schritt für Schritt bis
                       hierher gefolgt und will genau dieses Formular
                       ausfüllen. Das Laden ist die Erfüllung dessen,
                       wozu er hergekommen ist, kein ungefragtes
                       Nachladen. Ein zweiter Zustimmungsklick davor
                       wäre Zeremonie und keine Wahl.

                       referrerpolicy bleibt streng: Der Anbieter
                       erfährt die Adresse, aber nicht, von welcher
                       Unterseite aus. */
                    ?>
                    <iframe class="v-antrag__formular"
                            src="<?= h($s['einbettung']) ?>"
                            title="<?= h($s['titel']) ?>"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                <?php } ?>

                <?php if ($s['knopfText'] !== '' && $s['knopfZiel'] !== '') { ?>
                    <p class="v-antrag__tat">
                        <a class="btn btn--gelb"
                           href="<?= h($s['knopfZiel']) ?>"
                           <?php if ($s['extern']) { ?>
                               data-antrag-hinaus
                               data-antrag-naechster="<?= $i + 2 <= $anzahl ? $i + 2 : 0 ?>"
                               target="_blank" rel="noopener external"
                           <?php } ?>>
                            <?= h($s['knopfText']) ?>
                            <?php if ($s['extern']) { ?>
                                <span class="nur-sr"><?= t('(öffnet ein neues Fenster)') ?></span>
                            <?php } ?>
                        </a>
                    </p>
                <?php } ?>

                <div class="v-antrag__blaettern" data-antrag-blaettern hidden>
                    <button type="button" class="btn btn--linie" data-antrag-zurueck>
                        <?= t('Zurück') ?>
                    </button>
                    <button type="button" class="btn" data-antrag-weiter>
                        <?= t('Weiter') ?>
                    </button>
                </div>
            </section>
        <?php } ?>
    </div>

    <?php
    /* Das Bestätigungsfenster. Ein echtes <dialog>: Fokusfalle,
       Escape und Hintergrund-Inertheit bringt der Browser mit, das
       muss man nicht nachbauen ✅ WCAG 2.4.3 */
    ?>
    <dialog class="v-antrag__frage" data-antrag-frage>
        <h4 class="v-antrag__frage-titel"><?= h($hinausTitel) ?></h4>
        <div class="v-antrag__frage-text"><?= $hinausTextHtml ?></div>
        <p class="v-antrag__frage-wohin" data-antrag-frage-wohin></p>
        <div class="v-antrag__frage-knoepfe">
            <button type="button" class="btn btn--linie" data-antrag-frage-ab>
                <?= t('Doch nicht') ?>
            </button>
            <button type="button" class="btn btn--gelb" data-antrag-frage-los>
                <?= t('Alles klar, los') ?>
            </button>
        </div>
    </dialog>
</div>
