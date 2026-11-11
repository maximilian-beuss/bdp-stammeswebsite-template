<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array  $bilderListe
 * @var string $dichte
 * @var int    $infos
 * @var int    $lupe
 */

if (count($bilderListe) === 0) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-galerie__leer">' . t('Noch keine Bilder. Block bearbeiten und Bilder hinzufügen.') . '</p>';
    }

    return;
}

/*
   Die Info-Kästchen gehören zu diesem Block und zu keinem anderen.

   Früher rüstete das Theme jede Figur mit der Klasse .v-bild überall
   auf der Website mit einem Info-Schalter aus. Auf einer Textseite mit
   vier Fotos waren das vier Schalter, die beim Überfahren aufklappten,
   während man lesen wollte. Gebraucht wird das nur hier, in der
   Galerie, wo die Geschichte hinter dem Bild der Punkt ist.

   Deshalb steht die ganze Mechanik jetzt im Block: Markup hier,
   Aussehen in css/bloecke.css, Verhalten in js/bloecke.js. Ohne
   JavaScript bleibt die Bildunterschrift ein ganz normales
   figcaption unter dem Bild ✅ — es geht nichts verloren.
*/
$zaehler = 0;
?>
<div class="v-galerie v-galerie--<?= h($dichte) ?><?= $infos ? ' v-galerie--mit-info' : '' ?>"
     <?= $infos ? 'data-galerie-infos' : '' ?>>

    <?php foreach ($bilderListe as $i => $b) { ?>
        <?php
        $hatInfo = $infos && ($b['titel'] !== '' || $b['text'] !== '');
        $infoID = 'v-galerie-info-' . (isset($bID) ? (int) $bID : 0) . '-' . (++$zaehler);
        ?>
        <figure class="v-galerie__stueck v-galerie__stueck--<?= h($b['groesse']) ?>"
                <?= $hatInfo ? 'data-galerie-figur' : '' ?>>

            <?php if ($lupe) { ?>
                <?php /* Ein echter Link auf die Bilddatei: funktioniert ohne
                         JavaScript, öffnet mit JavaScript die Großansicht.
                         Kein <div onclick>, damit Tastatur und Screenreader
                         mitkommen ✅ WCAG 2.1.1 */ ?>
                <a class="v-galerie__lupe" href="<?= h($b['url']) ?>"
                   data-galerie-lupe
                   data-titel="<?= h($b['titel']) ?>"
                   aria-label="<?= h($b['titel'] !== '' ? t('Bild groß ansehen: %s', $b['titel']) : t('Bild groß ansehen')) ?>">
            <?php } ?>

            <img src="<?= h($b['url']) ?>"
                 <?= $b['breite'] > 0 ? 'width="' . (int) $b['breite'] . '"' : '' ?>
                 <?= $b['hoehe'] > 0 ? 'height="' . (int) $b['hoehe'] . '"' : '' ?>
                 alt="<?= h($b['alt']) ?>"
                 loading="<?= $i < 4 ? 'eager' : 'lazy' ?>">

            <?php if ($lupe) { ?>
                </a>
            <?php } ?>

            <?php if ($hatInfo) { ?>
                <?php
                /* Der Schalter steht außerhalb des Lupen-Links: Ein Klick
                   auf das i soll die Info aufklappen, nicht die
                   Großansicht öffnen. Er trägt aria-expanded und
                   aria-controls, damit Screenreader den Zustand kennen. */
                ?>
                <button type="button"
                        class="v-galerie__info"
                        aria-expanded="false"
                        aria-controls="<?= h($infoID) ?>"
                        data-galerie-info>
                    <span class="v-galerie__info-zeichen" aria-hidden="true">i</span>
                    <span class="nur-sr"><?= t('Bildinformationen anzeigen') ?></span>
                </button>

                <figcaption id="<?= h($infoID) ?>" data-galerie-text data-offen="false">
                    <?php if ($b['titel'] !== '') { ?><strong><?= h($b['titel']) ?></strong><?php } ?>
                    <?= h($b['text']) ?>
                </figcaption>
            <?php } ?>
        </figure>
    <?php } ?>
</div>
