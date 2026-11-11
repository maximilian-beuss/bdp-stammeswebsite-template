<?php

use Concrete\Core\Application\Application;

/*
 * ----------------------------------------------------------------------------
 * Instantiate Concrete
 * ----------------------------------------------------------------------------
 */
$app = new Application();

/*
 * ----------------------------------------------------------------------------
 * Umgebung festlegen
 * ----------------------------------------------------------------------------
 * detectEnvironment() muss aufgerufen werden — nicht wegen der Umgebung,
 * sondern weil die Methode nebenbei `app_relative_path` setzt. Aus diesem
 * Wert baut Concrete alle URLs, wenn die Installation in einem
 * Unterverzeichnis liegt (auf XAMPP: /mein-stamm/). Ohne den Aufruf zeigen
 * lokal sämtliche Links und Bildpfade auf die Dokumentwurzel und laufen
 * ins Leere.
 *
 * Was hier vorher stand
 * ---------------------
 * Die Auslieferung von Concrete bringt Platzhalter mit:
 *
 *     'local'      => array('hostname'),
 *     'production' => array('live.site'),
 *
 * Die standen hier unverändert. Kein realer Rechner heißt „hostname"
 * oder „live.site", also griff keiner der Einträge — und weil
 * EnvironmentDetector::detectWebEnvironment() als Rückfallwert
 * 'production' liefert, lief JEDE Maschine unter „production": der
 * Entwicklungsrechner genauso wie der Server. Das war zufällig
 * folgenlos, aber es war eben Zufall. Wer die Platzhalter irgendwann
 * mit echten Werten füllt, ändert damit unangekündigt, welche
 * Konfigurationsdateien geladen werden.
 *
 * Warum jetzt eine feste Umgebung
 * -------------------------------
 * Diese Site braucht keine umgebungsabhängige Konfiguration. Was lokal
 * und live gleich sein muss (Sprache, Zeitzone, sprechende URLs,
 * erlaubte Dateiendungen), steht fest in application/config/concrete.php
 * und application/config/app.php. Was sich unterscheiden DARF, sind
 * Zugangsdaten — und die stehen in application/config/database.php,
 * die ohnehin nicht im Repo liegt.
 *
 * Eine Closure macht das Ergebnis eindeutig: eine Umgebung, überall
 * dieselbe, unabhängig davon wie ein Rechner heißt.
 *
 * Wenn hier je wieder umgebungsabhängige Konfiguration nötig wird,
 * dann bitte bewusst: Rechnernamen eintragen, die es wirklich gibt,
 * und in der jeweiligen application/config/<umgebung>.<gruppe>.php
 * dokumentieren, warum der Wert sich unterscheiden muss.
 */
$app->detectEnvironment(function () {
    return 'production';
});

return $app;
