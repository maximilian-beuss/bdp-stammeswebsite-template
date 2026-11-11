<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Area\GlobalArea;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url as URL;
use Concrete\Core\Validation\CSRF\Token;

$view->inc('elements/header_top.php');
$themePfad = $view->getThemePath();

/*
   Noch einmal fragen statt aus header_top.php übernehmen: $view->inc()
   führt die eingebundene Datei in einem eigenen Geltungsbereich aus,
   deren Variablen kommen hier nicht an.
*/
$app = Application::getFacadeApplication();
$angemeldet = $app->make('Concrete\Core\User\User')->isRegistered();

$abmeldeUrl = URL::to('/login', 'do_logout', $app->make(Token::class)->generate('do_logout'));

/*
   Kopfdaten: Stammesname, Ort, Logo und Topbar-Links.

   Diese kommen aus dem ersten Block im globalen Bereich „Kopfzeile" —
   wenn dort ein bdp_kopfzeile-Block liegt. Andernfalls greift der
   Fallback aus application/config/stamm.php.

   Warum direkt aus der Datenbank statt über GlobalArea::display()?
   Der Topbar-Streifen muss im DOM vor <header> stehen (er scrollt mit
   der Seite), während das Logo innerhalb des sticky <header> sitzt.
   Da GlobalArea::display() einen einzigen HTML-Klumpen ausgibt, ohne
   die Möglichkeit, ihn an zwei Stellen aufzuteilen, lesen wir die
   Daten hier selbst und rendern sie an der richtigen Stelle.

   Im Bearbeitungsmodus (isEditMode) wird zusätzlich der normale
   GlobalArea-Bereich angezeigt, damit der Block editierbar bleibt.
*/
$kopfDaten = null;

$stack = \Concrete\Core\Page\Stack\Stack::getByName('Kopfzeile');
if ($stack && !$stack->isError()) {
    $blocks = $stack->getBlocks('Stacks');
    foreach ($blocks as $bl) {
        if ($bl->getBlockTypeHandle() === 'bdp_kopfzeile') {
            $kopfDaten = $bl->getController();
            break;
        }
    }
}

if ($kopfDaten) {
    $stammKfgLinks  = \EinfachBdP\Werkzeug::liste($kopfDaten->topbarLinks  ?? '[]');
    $stammKfgRechts = \EinfachBdP\Werkzeug::liste($kopfDaten->topbarRechts ?? '[]');
    $logoUrl = \EinfachBdP\Werkzeug::bildUrl((int) ($kopfDaten->logoDateifID ?? 0));
    if ($logoUrl === '') {
        $logoUrl = $themePfad . '/images/bdp-klilie-blau-gelb.svg';
    }
    $logoAlt      = trim((string) ($kopfDaten->logoAlt ?? 'Zurück zur Startseite')) ?: 'Zurück zur Startseite';
    $stammesname  = trim((string) ($kopfDaten->stammesname ?? 'Musterstamm')) ?: 'Musterstamm';
    $stammOrt     = trim((string) ($kopfDaten->ort ?? ''));
} else {
    // Fallback: application/config/stamm.php
    $stammKonfig    = require_once DIR_APPLICATION . '/config/stamm.php';
    $stammKfgLinks  = $stammKonfig['topbar_links'] ?? [];
    $stammKfgRechts = $stammKonfig['topbar_rechts'] ?? [];
    $logoDatei = !empty($stammKonfig['logo_datei'])
        ? $themePfad . '/images/' . $stammKonfig['logo_datei']
        : $themePfad . '/images/bdp-klilie-blau-gelb.svg';
    $logoUrl     = $logoDatei;
    $logoAlt     = $stammKonfig['logo_alt'] ?? 'Zurück zur Startseite';
    $stammesname = $stammKonfig['stammesname'] ?? 'Musterstamm';
    $stammOrt    = $stammKonfig['ort'] ?? '';

    // Topbar-Link-Format angleichen: stamm.php benutzt 'href', Block benutzt 'href'
    $stammKfgLinks  = array_map(fn($l) => ['text' => $l['text'], 'href' => $l['href'], 'extern' => !empty($l['extern'])], $stammKfgLinks);
    $stammKfgRechts = array_map(fn($l) => ['text' => $l['text'], 'href' => $l['href'], 'extern' => !empty($l['extern'])], $stammKfgRechts);
}
?>

<?php
/*
   Hinweis für angemeldete Redakteure.
*/
if ($angemeldet) { ?>
    <div class="v-redaktionshinweis">
        <div class="container">
            <p>
                <strong>Redaktionsansicht.</strong>
                Was du hier siehst, kann sich von der Besucheransicht unterscheiden:
                Abstände, Rahmen um Bereiche und abgeschaltete Einblendungen gehören
                zum Bearbeiten und sind später nicht da.
                <a href="<?= h($abmeldeUrl) ?>">Abmelden und nachsehen</a>
            </p>
        </div>
    </div>
<?php } ?>

<!-- Meta-Topbar: schmaler Streifen in Pfadiblau. -->
<?php if (!empty($stammKfgLinks) || !empty($stammKfgRechts)) { ?>
<div class="v-topbar">
    <div class="container">
        <div class="v-topbar__innen">
            <div class="v-topbar__gruppe v-topbar__gruppe--links">
                <?php foreach ($stammKfgLinks as $link) {
                    $extern = !empty($link['extern']) ? ' rel="noopener external"' : '';
                    echo '<a href="' . h(\EinfachBdP\Werkzeug::link($link['href'])) . '"' . $extern . '>' . h($link['text']) . '</a>';
                } ?>
            </div>
            <div class="v-topbar__gruppe v-topbar__gruppe--rechts">
                <?php foreach ($stammKfgRechts as $link) {
                    $extern = !empty($link['extern']) ? ' rel="noopener external"' : '';
                    echo '<a href="' . h(\EinfachBdP\Werkzeug::link($link['href'])) . '"' . $extern . '>' . h($link['text']) . '</a>';
                } ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<header class="v-kopf">
    <div class="container">
        <div class="v-kopf__innen">

            <div class="v-kopf__logo-gruppe">
                <a class="v-logo" href="<?= View::url('/') ?>">
                    <img src="<?= h($logoUrl) ?>"
                         alt="<?= h($logoAlt) ?>"
                         width="45" height="52">
                    <span class="v-logo__text">
                        <span class="v-logo__name"><?= h($stammesname) ?></span>
                        <?php if ($stammOrt !== '') { ?>
                            <span class="v-logo__zusatz"><?= h($stammOrt) ?></span>
                        <?php } ?>
                    </span>
                </a>
                <?php if ($angemeldet) { ?>
                <a class="v-logo__einstellungen"
                   href="<?= h(URL::to('/dashboard/einfach_bdp/stamm')) ?>"
                   title="<?= t('Stammeseinstellungen') ?>">&#9998;<span class="v-logo__einstellungen-text"><?= t('Stammesname und Kopfzeilenlinks bearbeiten') ?></span></a>
                <?php } ?>
            </div>

            <button class="v-burger" type="button"
                    aria-expanded="false"
                    aria-controls="hauptnavigation"
                    aria-label="Menü öffnen">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </button>

            <?php $view->inc('elements/navigation.php'); ?>

        </div>
    </div>
</header>

<?php
/*
   GlobalArea „Kopfzeile" nur im Bearbeitungsmodus anzeigen.
   Im normalen Besuchermodus werden die Daten oben direkt aus dem Block
   gelesen — das ermöglicht die korrekte DOM-Reihenfolge (Topbar vor
   Header). Im Bearbeitungsmodus muss der Bereich trotzdem erscheinen,
   damit der Block editierbar ist.
*/
/*
   GlobalArea „Kopfzeile" nur im Bearbeitungsmodus anzeigen.
   Im normalen Besuchermodus werden die Daten oben direkt aus dem Block
   gelesen — das ermöglicht die korrekte DOM-Reihenfolge (Topbar vor
   Header). Im Bearbeitungsmodus muss der Bereich trotzdem erscheinen,
   damit der Block editierbar ist.
*/
if (is_object($c) && $c->isEditMode()) {
    $kopfBereich = new GlobalArea('Kopfzeile');
    echo '<div style="position:relative;z-index:100">';
    $kopfBereich->display();
    echo '</div>';
}
?>
