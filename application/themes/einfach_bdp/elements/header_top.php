<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application;

$app = Application::getFacadeApplication();
$themePfad = $view->getThemePath();
?>
<?php
/*
   Zwei Zustände hängen am <html>-Element, weil CSS und JavaScript sie
   beide brauchen und beide sie brauchen, bevor der Body steht:

   data-editmodus  Die Seite wird gerade bearbeitet. Dann bleiben die
                   Einblendeanimationen aus — sie lenken beim Setzen
                   von Blöcken nur ab und lassen Bereiche kurz leer
                   aussehen, die längst da sind.
   data-angemeldet Jemand ist eingeloggt. Trägt den Hinweisstreifen in
                   der Topbar.
*/
$imEditmodus = is_object($c) && $c->isEditMode();
$angemeldet = $app->make('Concrete\Core\User\User')->isRegistered();
?>
<!doctype html>
<html lang="<?= Localization::activeLanguage() ?>"
      <?= $imEditmodus ? 'data-editmodus="1"' : '' ?>
      <?= $angemeldet ? 'data-angemeldet="1"' : '' ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0E4278">

    <?php
    // Tab-Symbol: die BdP-Klilie ist das Verbandszeichen und auf jeder BdP-Seite wiederzuerkennen.
    // Für das einfarbige Safari-Symbol wird ebenfalls die Klilie verwendet.
    ?>
    <link rel="icon" href="<?= $themePfad ?>/images/bdp-klilie-blau-gelb.svg" type="image/svg+xml">
    <link rel="mask-icon" href="<?= $themePfad ?>/images/bdp-klilie-blau-gelb.svg" color="#0E4278">

    <?php
    // Schriften früh anfordern: verhindert den Sprung beim ersten Rendern.
    foreach (['Klassisch', 'Stark', 'Superfett'] as $schnitt) {
        echo '<link rel="preload" as="font" type="font/woff2" crossorigin href="'
            . $themePfad . '/fonts/einfachBdP-' . $schnitt . '.woff2">' . "\n    ";
    }
    // MARKS wird nur dort geladen, wo es gebraucht wird — nicht im
    // Standardpreload. Eine zusätzliche Preload-Anforderung für eine
    // Schrift, die 95 % der Seiten gar nicht nutzen, wäre verschwendete
    // Bandbreite. Browser fordert sie per @font-face nach, sobald ein
    // Element var(--font-akzent) verwendet.
    ?>

    <?php
    // Markiert früh, dass JavaScript läuft. Alles, was ohne JS sichtbar
    // bleiben muss, hängt in main.css an dieser Klasse.
    ?>
    <script>document.documentElement.className += ' hat-js';</script>

    <?php
    /*
       Stilvorlagen mit dem Änderungsdatum als Anhängsel.

       Ohne das hält ein Browser die einmal geladene Datei fest, und
       zwar beliebig lange: Apache liefert die Dateien hier ohne
       Cache-Control aus, dann rät der Browser — und rät großzügig.
       Nach einer Änderung am Stylesheet sah die Redaktion die alte
       Fassung und musste hart neu laden, um zu sehen, was passiert
       ist. Über einen Zwischenspeicher wie den Entwicklungstunnel
       half selbst das nicht immer.

       filemtime() statt einer von Hand gepflegten Nummer: Eine
       Nummer, die man beim Ändern mitzählen muss, zählt irgendwann
       niemand mehr mit.
    */
    $themeVerzeichnis = DIR_APPLICATION . '/themes/einfach_bdp';

    $stil = function ($datei) use ($themePfad, $themeVerzeichnis) {
        $pfad = $themeVerzeichnis . '/' . $datei;
        $stand = is_file($pfad) ? filemtime($pfad) : 0;

        echo '<link rel="stylesheet" href="' . $themePfad . '/' . $datei
            . ($stand ? '?v=' . $stand : '') . '">' . "\n    ";
    };

    $stil('css/tokens.css');
    $stil('css/grid.css');
    $stil('css/main.css');

    // Eigene Datei für das Telefon-Widget auf der Kontaktseite. Steht
    // bewusst neben main.css und nicht darin: Der Baustein hängt an
    // telefon.js und an einem eigenen Endpunkt und soll als Ganzes
    // austauschbar bleiben.
    $stil('css/telefon.css');

    // Bausteine des einfachBdP-Pakets: Abschnitt, Galerie, Kämmerei,
    // Aufnahmeantrag, Kalender, Video, Karte.
    $stil('css/bloecke.css');
    ?>

    <?= $view->getThemeStyles() ?>

    <?php
    /*
       Seitentitel der Startseite.

       Alle anderen Seiten bekommen ihren <title> aus dem Kern nach
       dem Muster in application/config/concrete.php:
       „Seitenname • Musterstamm”.

       Auf der Startseite stünde damit „Startseite • Musterstamm” — der Seitenname doppelt sich dort mit dem Namen der
       Website und sagt nichts. Ein Format allein kann das nicht
       unterscheiden, also wird der Titel hier gesetzt und an
       header_required durchgereicht: Ein übergebener Titel wird von
       dort unverändert übernommen.

       Vorrang hat, was die Redaktion im Seitenattribut „Meta-Titel“
       einträgt — sonst ließe sich der Startseitentitel nirgends mehr
       ändern.
    */
    if (!isset($pageTitle) || !is_string($pageTitle) || $pageTitle === '') {
        $pageTitle = '';

        if (is_object($c) && $c->isHomePage()) {
            $eigenerTitel = (string) $c->getAttribute('meta_title');

            $pageTitle = $eigenerTitel !== ''
                ? $eigenerTitel
                : $app->make('site')->getSite()->getSiteName();
        }
    }

    View::element('header_required', [
        'pageTitle' => isset($pageTitle) ? $pageTitle : '',
        'pageDescription' => isset($pageDescription) ? $pageDescription : '',
        'pageMetaKeywords' => isset($pageMetaKeywords) ? $pageMetaKeywords : '',
    ]);
    ?>
</head>
<body>

<a class="sprung-zum-inhalt" href="#inhalt">Direkt zum Inhalt</a>

<div class="theme-einfach-bdp <?= $c->getPageWrapperClass() ?>">
