<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Überschreibt die Standard-Ausgabe des Concrete-Blocks „Seitenliste"
 * (page_list).
 *
 * Auf /aktionen/berichte, auf der Startseite und überall sonst, wo eine
 * Seitenliste eingesetzt wird, entstehen daraus automatisch Kacheln im
 * einfachBdP-Design:
 *   - Vorschaubild (Attribut „Vorschaubild", „seiten_bild" oder „thumbnail")
 *   - Berichtsdatum als Etikett (Attribut „berichtsdatum" oder Veröffentlichungsdatum)
 *   - Titel der Seite
 *   - Kurzbeschreibung (Attribut „kurzbeschreibung" oder Seitenbeschreibung)
 *   - Pfeil-Link zum Weiterlesen
 *
 * Sind Seiten vom Typ „Bericht" dabei, werden sie zuerst vollständig nach
 * ihrem Berichtsdatum absteigend sortiert (neueste zuerst) und erst danach
 * auf die im Block eingestellte Anzahl ($num) begrenzt. So zeigt eine
 * Seitenliste mit $num = 1 zuverlässig immer den neuesten Bericht.
 *
 * Steht der Block auf der Startseite (oder im Bereich „Startseite"),
 * bringt er automatisch die eigene Sektion samt „Alle Berichte ansehen"-Link
 * und Rucksack-Ikon mit und lässt sich als normaler Block im Bearbeitungsmodus
 * verschieben und konfigurieren.
 */

use Concrete\Core\Page\Page;

$c = Page::getCurrentPage();

/** @var \Concrete\Core\Utility\Service\Text $th */
$th = Core::make('helper/text');

if (is_object($c) && $c->isEditMode() && $controller->isBlockEmpty()) { ?>
    <div class="container">
        <p class="v-raster__leer"><?= t('Leere Seitenliste. Block bearbeiten.') ?></p>
    </div>
    <?php
    return;
}

$kacheln = [];
$hatBerichte = false;

$ptBericht = \Concrete\Core\Page\Type\Type::getByHandle('bericht');
$istBerichtFilter = is_object($ptBericht)
    && isset($controller->ptID)
    && (int) $controller->ptID === (int) $ptBericht->getPageTypeID();

/*
   Wenn nach dem Seitentyp „Bericht" gefiltert wird und noch keine
   Seitenblätterung auf Seite 2+ aktiv ist, holen wir alle Berichte und
   sortieren sie zuerst nach ihrem Attribut „berichtsdatum" (neueste
   zuerst), bevor auf $num gekürzt wird. Sonst würde Concrete vorab nach
   Sitemap-Reihenfolge oder cDatePublic abschneiden und bei $num = 1
   nicht zwingend den nach Berichtsdatum neuesten Bericht liefern.
*/
if ($istBerichtFilter && empty($showPagination)) {
    $hatBerichte = true;
    $limit = isset($controller->num) && (int) $controller->num > 0 ? (int) $controller->num : 0;
    $kacheln = \EinfachBdP\Werkzeug::berichte($limit);

    if (!empty($controller->truncateSummaries) && (int) $controller->truncateChars > 0) {
        foreach ($kacheln as &$d) {
            if ($d['text'] !== '') {
                $d['text'] = (string) $th->wordSafeShortText($d['text'], (int) $controller->truncateChars);
            }
        }
        unset($d);
    }
} else {
    if (!empty($pages) && is_iterable($pages)) {
        foreach ($pages as $page) {
            if (!is_object($page) || $page->isError()) {
                continue;
            }

            if ($page->getPageTypeHandle() === 'bericht') {
                $hatBerichte = true;
            }

            $daten = \EinfachBdP\Werkzeug::berichtDaten($page);

            if ((string) $page->getCollectionPointerExternalLink() !== '') {
                $daten['ziel'] = (string) $page->getCollectionPointerExternalLink();
                $daten['neuesFenster'] = (bool) $page->openCollectionPointerExternalLinkInNewWindow();
            } else {
                $daten['neuesFenster'] = ((string) $page->getAttribute('nav_target')) === '_blank';
            }

            if (!empty($controller->truncateSummaries) && (int) $controller->truncateChars > 0 && $daten['text'] !== '') {
                $daten['text'] = (string) $th->wordSafeShortText($daten['text'], (int) $controller->truncateChars);
            }

            $kacheln[] = $daten;
        }
    }

    if ($hatBerichte && count($kacheln) > 1) {
        usort($kacheln, static function (array $a, array $b) {
            return $b['zeitstempel'] <=> $a['zeitstempel'];
        });
    }
}

$zeigeBild = !isset($displayThumbnail) || (bool) $displayThumbnail;
$zeigeDatum = !isset($includeDate) || (bool) $includeDate;
$zeigeText = !isset($includeDescription) || (bool) $includeDescription;
$zeigeTitel = !isset($includeName) || (bool) $includeName;
$knopfText = isset($buttonLinkText) && trim((string) $buttonLinkText) !== ''
    ? trim((string) $buttonLinkText)
    : t('Bericht lesen');
$titelTag = isset($titleFormat) && in_array($titleFormat, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)
    ? $titleFormat
    : 'h2';

$blockObjekt = isset($b) && is_object($b) ? $b : $controller->getBlockObject();
$bereichsName = is_object($blockObjekt) ? (string) $blockObjekt->getAreaHandle() : '';
$aufStartseite = $bereichsName === 'Startseite' || (is_object($c) && $c->isHomePage());
$istEinzelHighlight = $aufStartseite || ((int) ($controller->num ?? 0) === 1 && count($kacheln) === 1);

$alleBerichteUrl = '';
if ($hatBerichte && ($aufStartseite || ! (is_object($c) && $c->getCollectionPath() === '/aktionen/berichte'))) {
    $berichteSeite = Page::getByPath('/aktionen/berichte');
    $alleBerichteUrl = (is_object($berichteSeite) && !$berichteSeite->isError())
        ? (string) $berichteSeite->getCollectionLink()
        : (string) View::url('/aktionen/berichte');
}

$listenTitel = isset($pageListTitle) ? trim((string) $pageListTitle) : '';
if ($aufStartseite && $listenTitel === '' && $hatBerichte) {
    $listenTitel = t('Aktueller Bericht');
}
if ($aufStartseite && $titelTag === 'h5') {
    $titelTag = 'h2';
}

$rucksackIkon = DIR_REL . '/application/themes/einfach_bdp/images/ikons/blau/rucksack.svg';
?>

<?php if ($aufStartseite) { ?>
<section class="v-abschnitt v-abschnitt--rahmen v-sektion v-start-bericht">
    <div class="container">
<?php } ?>

<div class="ccm-block-page-list-wrapper v-berichte<?= $aufStartseite ? ' v-berichte--startseite' : '' ?>">

    <?php if ($listenTitel !== '' || $alleBerichteUrl !== '') { ?>
        <div class="v-sektion__kopf v-start-bericht__kopf">
            <?php if ($listenTitel !== '') { ?>
                <<?= $titelTag ?> class="mit-strich">
                    <?php if ($aufStartseite) { ?>
                        <span class="v-abschnitt__ikon" aria-hidden="true" style="--v-ikon: url('<?= h($rucksackIkon) ?>')"></span>
                    <?php } ?>
                    <?= h($listenTitel) ?>
                </<?= $titelTag ?>>
            <?php } ?>

            <?php if ($alleBerichteUrl !== '') { ?>
                <a class="pfeil-link" href="<?= h($alleBerichteUrl) ?>"><?= t('Alle Berichte ansehen') ?></a>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (count($kacheln) > 0) { ?>
        <div class="v-raster <?= $istEinzelHighlight ? 'v-raster--bericht-einzeln' : 'v-raster--berichte' ?>">
            <?php foreach ($kacheln as $k) {
                $hatZiel = $k['ziel'] !== '';
                $tag = $hatZiel ? 'a' : 'div';
                $zielAttr = '';
                if ($hatZiel) {
                    $zielAttr = ' href="' . h($k['ziel']) . '"';
                    if (!empty($k['neuesFenster'])) {
                        $zielAttr .= ' target="_blank" rel="noopener"';
                    }
                }
                $kachelKlassen = 'v-kachel v-kachel--bericht v-auftritt';
                if ($istEinzelHighlight) {
                    $kachelKlassen .= ' v-kachel--highlight';
                }
                ?>
                <<?= $tag ?> class="<?= $kachelKlassen ?>"<?= $zielAttr ?>>
                    <?php if ($zeigeBild && $k['bild'] !== '') { ?>
                        <div class="v-kachel__bild">
                            <img src="<?= h($k['bild']) ?>" alt="" loading="lazy">
                        </div>
                    <?php } ?>

                    <div class="v-kachel__inhalt">
                        <?php if ($zeigeDatum && $k['datum'] !== '') { ?>
                            <time class="v-etikett v-kachel__datum" datetime="<?= h($k['datumIso']) ?>">
                                <?= h($k['datum']) ?>
                            </time>
                        <?php } elseif ($k['etikett'] !== '') { ?>
                            <span class="v-etikett"><?= h($k['etikett']) ?></span>
                        <?php } ?>

                        <?php if ($zeigeTitel && $k['titel'] !== '') { ?>
                            <h3 class="v-kachel__titel"><?= h($k['titel']) ?></h3>
                        <?php } ?>

                        <?php if ($zeigeText && $k['text'] !== '') { ?>
                            <p class="v-kachel__text"><?= h($k['text']) ?></p>
                        <?php } ?>

                        <?php if ($hatZiel && $knopfText !== '') { ?>
                            <span class="v-kachel__fuss pfeil-link"><?= h($knopfText) ?></span>
                        <?php } ?>
                    </div>
                </<?= $tag ?>>
            <?php } ?>
        </div>
    <?php } elseif (isset($noResultsMessage) && trim((string) $noResultsMessage) !== '') { ?>
        <p class="v-berichte__leer"><?= h($noResultsMessage) ?></p>
    <?php } elseif (is_object($c) && $c->isEditMode()) { ?>
        <p class="v-raster__leer"><?= t('Noch keine Berichte vorhanden.') ?></p>
    <?php } ?>

    <?php if (!empty($showPagination) && !empty($pagination)) { ?>
        <div class="v-berichte__seite">
            <?= $pagination ?>
        </div>
    <?php } ?>

</div>

<?php if ($aufStartseite) { ?>
    </div>
</section>
<?php } ?>
