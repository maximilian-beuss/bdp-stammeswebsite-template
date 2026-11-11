<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array  $folienListe
 * @var int    $anzahl
 * @var int    $takt
 * @var string $hoehe
 */

if ($anzahl === 0) {
    // Im Bearbeitungsmodus muss etwas dastehen, sonst ist der Block
    // unsichtbar und nicht mehr anklickbar.
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<div class="v-hero v-hero--leer"><p>' . t('Noch keine Folien. Block bearbeiten und Bilder hinzufügen.') . '</p></div>';
    }

    return;
}
?>
<section class="v-hero auf-dunkel<?= $hoehe !== 'normal' ? ' v-hero--' . h($hoehe) : '' ?>"
         data-hero data-takt="<?= (int) $takt ?>">

    <div class="v-hero__spur"
         data-hero-spur
         tabindex="0"
         role="group"
         aria-roledescription="Bildwechsler"
         aria-label="Einblicke in unser Stammesleben. Mit den Pfeiltasten blättern.">

        <?php foreach ($folienListe as $i => $folie) { ?>
            <?php
            /* Drei Klassen auf der ersten Folie, alle schon im HTML,
               damit sie vollständig dasteht, bevor irgendein Skript
               gelaufen ist:

                 ist-aktiv      Zustand der Punkte unten
                 ist-sichtbar   sie steht im Sichtfenster
                 ist-dran       Bildzoom und Text sind eingeblendet

               Danach vergibt theme.js alle drei weiter. */
            ?>
            <article class="v-hero__folie<?= $i === 0 ? ' ist-aktiv ist-sichtbar ist-dran' : '' ?>"
                     role="group"
                     aria-roledescription="Folie"
                     aria-label="<?= ($i + 1) . ' von ' . (int) $anzahl ?>">

                <img class="v-hero__bild" src="<?= h($folie['bild']) ?>" alt="<?= h($folie['alt']) ?>"
                     <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                <span class="v-hero__schleier" aria-hidden="true"></span>

                <div class="v-hero__innen">
                    <div class="container">
                        <?php if ($folie['titel'] !== '') { ?>
                            <h2 class="v-hero__titel"><?= h($folie['titel']) ?></h2>
                        <?php } ?>
                        <?php if ($folie['text'] !== '') { ?>
                            <p class="v-hero__text"><?= h($folie['text']) ?></p>
                        <?php } ?>
                        <?php if (count($folie['knoepfe']) > 0) { ?>
                            <div class="v-hero__aktionen">
                                <?php foreach ($folie['knoepfe'] as $knopf) { ?>
                                    <a class="btn <?= h($knopf['stil']) ?>"
                                       href="<?= h($knopf['ziel']) ?>"><?= h($knopf['text']) ?></a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </article>
        <?php } ?>
    </div>

    <?php // Ein Anschnitt, am Rand ✅ CD — läuft nach oben bis in die Topbar durch ?>
    <span class="v-keil" aria-hidden="true"></span>

    <?php if ($anzahl > 1) { ?>
        <?php
        /* Pfeile und Punkte stehen zusammen in einer Leiste am unteren
           Rand.

           Vorher hingen die Pfeile auf halber Höhe am linken und rechten
           Rand. Links lagen sie damit über der Überschrift — bei
           „Einfach raus.“ saß der Knopf mitten auf dem E. Rechts mussten
           sie dem gelben Anschnitt ausweichen, standen dadurch weit
           innen und das Paar sah schief aus.

           Unten mittig gibt es beide Probleme nicht: Der Text steht
           darüber, der Anschnitt läuft rechts vorbei, und die drei
           Bedienelemente liegen als eine Gruppe beieinander. ✅ CD: der
           Anschnitt bleibt sauber. */
        ?>
        <div class="v-hero__leiste">
            <button class="v-hero__pfeil v-hero__pfeil--zurueck" type="button"
                    data-hero-zurueck aria-label="Vorheriges Bild">
                <span aria-hidden="true"></span>
            </button>

            <div class="v-hero__punkte" role="group" aria-label="Bild auswählen">
                <?php foreach ($folienListe as $i => $folie) { ?>
                    <button class="v-hero__punkt" type="button"
                            data-hero-punkt="<?= $i ?>"
                            aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                            aria-label="Bild <?= $i + 1 ?><?= $folie['titel'] !== '' ? ': ' . h($folie['titel']) : '' ?>"></button>
                <?php } ?>
            </div>

            <button class="v-hero__pfeil v-hero__pfeil--vor" type="button"
                    data-hero-vor aria-label="Nächstes Bild">
                <span aria-hidden="true"></span>
            </button>
        </div>

        <p class="nur-sr" aria-live="polite" data-hero-stand></p>
    <?php } ?>

</section>
