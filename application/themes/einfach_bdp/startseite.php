<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Startseite.
 *
 * Der Bildwechsler steht als Block im Bereich „Hero“ und nicht mehr
 * fest im Template. Bilder, Überschriften, Texte und Knöpfe sind
 * damit ohne Dateizugriff änderbar.
 *
 * Der Bereich liegt vor <main>, weil der Hero über die ganze Breite
 * läuft und der Anschnitt nach oben bis in die Topbar durchschlägt.
 */

$view->inc('elements/header.php');
?>

<?php
/*
   Die Startseite hatte keine h1.

   Der Bildwechsler bringt nur h2 mit, und das ist richtig so: Seine
   Überschriften wechseln alle sieben Sekunden, eine davon zur
   Hauptüberschrift der Seite zu machen wäre unsinnig. Trotzdem braucht
   jedes Dokument genau eine h1 ✅ WCAG 1.3.1 / 2.4.6 — sie ist der
   Einstieg, über den Screenreader-Nutzer die Seite ansteuern.

   Deshalb steht sie hier, unsichtbar, mit dem, was die Seite wirklich
   ist. Sichtbar wäre sie eine Dopplung des Logos direkt darüber.
*/
?>
<h1 class="nur-sr">Musterstamm</h1>

<?php
$heroBereich = new Area('Hero');
$heroBereich->display($c);
?>

<main id="inhalt">
    <?php
    // Bewusst ohne Grid-Container: die Sektionen bringen ihre eigene
    // Breitenbegrenzung mit, damit blaue Flächen bis zum Rand laufen.
    $bereich = new Area('Startseite');
    $bereich->display($c);
    ?>
</main>

<?php $view->inc('elements/footer.php'); ?>
