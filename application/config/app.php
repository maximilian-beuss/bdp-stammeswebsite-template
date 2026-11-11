<?php

/**
 * application/config/app.php
 * ================================================================
 * Anwendungsweite Grundeinstellungen. Im Repo, weil sie auf jeder
 * Installation gleich sein müssen.
 */

return [

    /*
     * Zeitzone des Servers. Ohne diesen Wert rechnet Concrete in
     * UTC — der Stammeskalender zeigte Termine dann im Winter eine
     * und im Sommer zwei Stunden zu früh, und der iCal-Export
     * verteilte den Fehler an jeden abonnierten Kalender weiter.
     * netcup-Server laufen üblicherweise auf UTC, XAMPP auf der
     * Windows-Zeitzone — genau deshalb steht der Wert fest hier
     * und nicht in den generated_overrides.
     */
    'server_timezone' => 'Europe/Berlin',
];
