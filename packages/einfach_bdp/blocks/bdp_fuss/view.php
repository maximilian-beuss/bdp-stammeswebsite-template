<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;

/**
 * @var array  $kontakteListe
 * @var array  $rechtsListe
 * @var array  $adressZeilen
 * @var string $adresseTitel
 * @var string $schlussText
 * @var string $stammZiel
 * @var string $bdpZiel
 * @var int    $anmelden
 */

$app = Application::getFacadeApplication();
$navHelfer = $app->make('helper/navigation');

/*
   Die beiden Marken liegen im Theme und nicht im Dateimanager: Sie
   gehören zum Erscheinungsbild, nicht zum Inhalt, und dürfen deshalb
   auch nicht redaktionell austauschbar sein ✅ CD.

   Der Pfad kommt vom Theme der aktuellen Seite, nicht fest verdrahtet —
   sonst bräche der Block bei einem Themewechsel.
*/
$seite = \Concrete\Core\Page\Page::getCurrentPage();
$theme = is_object($seite) && !$seite->isError() ? $seite->getCollectionThemeObject() : null;
$themePfad = is_object($theme) ? $theme->getThemeURL() : DIR_REL . '/application/themes/einfach_bdp';

/** Die drei Symbole, die das Theme kennt. */
$ikon = function ($name) {
    $pfade = [
        'mail' => '<path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4.24-8 4.76-8-4.76V6l8 4.76L20 6z"/>',
        'karte' => '<path d="M12 2a7 7 0 0 0-7 7c0 5.06 6.24 12.34 6.5 12.65a.67.67 0 0 0 1 0C12.76 21.34 19 14.06 19 9a7 7 0 0 0-7-7zm0 10.2A3.2 3.2 0 1 1 15.2 9 3.2 3.2 0 0 1 12 12.2z"/>',
        'instagram' => '<path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.13 1.38C1.35 2.68.93 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.3.79.72 1.46 1.38 2.13.67.66 1.34 1.08 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.3 1.46-.72 2.13-1.38.66-.67 1.08-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.3-.79-.72-1.46-1.38-2.13C21.32 1.35 20.65.93 19.86.63 19.1.33 18.22.13 16.95.07 15.67.01 15.26 0 12 0z"/>'
            . '<path d="M12 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84zm0 10.16A4 4 0 1 1 16 12a4 4 0 0 1-4 4z"/>'
            . '<circle cx="18.41" cy="5.59" r="1.44"/>',
    ];

    return '<svg viewBox="0 0 24 24" role="img" focusable="false">'
        . (isset($pfade[$name]) ? $pfade[$name] : $pfade['mail']) . '</svg>';
};
?>

<div class="v-fuss__raster">

    <?php
    /* Zwei Marken, zwei Ziele: das Stammeslogo führt auf die
       Startseite, die Wortbildmarke des Bundes auf pfadfinden.de.
       Deshalb zwei getrennte Links mit je eigenem Alternativtext. */
    ?>
    <p class="v-fuss__marken">
        <a class="v-fuss__marke" href="<?= h(\EinfachBdP\Werkzeug::link($stammZiel)) ?>">
            <img class="v-fuss__marke-stamm"
                 src="<?= $themePfad ?>/images/bdp-klilie-blau-gelb.svg"
                 alt="Stammeslogo, zur Startseite"
                 width="33" height="38">
        </a>
        <?php if (trim($bdpZiel) !== '') { ?>
            <a class="v-fuss__marke" href="<?= h($bdpZiel) ?>" rel="noopener external">
                <img class="v-fuss__marke-bdp"
                     src="<?= $themePfad ?>/images/bdp-wbm-gelb.svg"
                     alt="Bund der Pfadfinder*innen, zur Website pfadfinden.de"
                     width="75" height="24">
            </a>
        <?php } ?>
    </p>

    <?php if (count($adressZeilen) > 0 || trim($adresseTitel) !== '') { ?>
        <address class="v-adresse" aria-label="Anschrift">
            <?php if (trim($adresseTitel) !== '') { ?>
                <strong><?= h($adresseTitel) ?></strong>
            <?php } ?>
            <?= implode('<br>', array_map('h', $adressZeilen)) ?>
        </address>
    <?php } ?>

    <?php if (count($kontakteListe) > 0) { ?>
        <ul class="v-sozial" aria-label="Kontakt und Kanäle">
            <?php foreach ($kontakteListe as $k) { ?>
                <li>
                    <a href="<?= h($k['ziel']) ?>"<?= strpos($k['ziel'], 'http') === 0 ? ' rel="noopener external"' : '' ?>>
                        <span class="v-sozial__ikon" aria-hidden="true"><?= $ikon($k['ikon']) ?></span>
                        <span class="v-sozial__text"><?= h($k['text']) ?></span>
                    </a>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>

</div>

<?php
/* Schlusszeile. Der Trenner darüber ist die gestrichelte CD-Linie
   in Gelb, dieselbe Form wie .trennstrich — waagerecht ✅ */
?>
<div class="v-fuss__schluss">
    <?php if (count($rechtsListe) > 0 || $anmelden) { ?>
        <ul class="v-rechtslinks">
            <?php foreach ($rechtsListe as $r) { ?>
                <li><a href="<?= h($r['ziel']) ?>"><?= h($r['text']) ?></a></li>
            <?php } ?>
            <?php if ($anmelden) { ?>
                <li><?= $navHelfer->getLogInOutLink() ?></li>
            <?php } ?>
        </ul>
    <?php } ?>

    <?php if (trim($schlussText) !== '') { ?>
        <p class="v-fuss__hinweis"><?= h($schlussText) ?></p>
    <?php } ?>
</div>
