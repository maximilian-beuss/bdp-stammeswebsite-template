<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

</div><!-- /.theme-einfach-bdp -->

<?php View::element('footer_required'); ?>
<?php
/*
   Dasselbe Anhängsel wie bei den Stilvorlagen, aus demselben Grund:
   Ohne das liefert der Browser nach einer Änderung tagelang das alte
   Skript aus. Siehe elements/header_top.php.
*/
$themePfad = $view->getThemePath();
$themeVerzeichnis = DIR_APPLICATION . '/themes/einfach_bdp';

$skript = function ($datei) use ($themePfad, $themeVerzeichnis) {
    $pfad = $themeVerzeichnis . '/' . $datei;
    $stand = is_file($pfad) ? filemtime($pfad) : 0;

    echo '<script src="' . $themePfad . '/' . $datei
        . ($stand ? '?v=' . $stand : '') . '" defer></script>' . "\n";
};

$skript('js/theme.js');

// Telefon-Widget der Kontaktseite. Läuft eigenständig neben theme.js und
// tut nichts, wenn auf der Seite kein [data-telefon] steht.
$skript('js/telefon.js');

// Aufnahmeantrag und Galerie-Großansicht. Tun nichts, wenn auf der
// Seite kein [data-antrag] und keine [data-galerie-lupe] steht.
$skript('js/bloecke.js');

// Der Kalender ist groß genug für eine eigene Datei.
$skript('js/kalender.js');
?>

</body>
</html>
