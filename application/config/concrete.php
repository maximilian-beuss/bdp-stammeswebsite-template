<?php

/**
 * application/config/concrete.php
 * ================================================================
 * Einstellungen, die auf JEDER Installation gelten müssen — lokal
 * auf XAMPP genauso wie live auf netcup. Diese Datei ist im Repo.
 *
 * Warum nicht production.concrete.php?
 * ------------------------------------
 * Vorher standen `seo.url_rewriting` und `upload.extensions` in
 * `application/config/production.concrete.php`. Concrete lädt eine
 * solche Datei nur, wenn die erkannte Umgebung „production" heißt —
 * und die Erkennung in application/bootstrap/start.php stand noch
 * auf den Platzhaltern der Concrete-Auslieferung ('hostname' /
 * 'live.site'). Auf netcup hätte also keiner der beiden Werte
 * gegriffen: keine sprechenden URLs, und die Redaktion hätte weder
 * SVG noch WebM noch VTT hochladen können — ohne erkennbare
 * Fehlermeldung.
 *
 * Umgebungsabhängige Konfiguration ist hier auch gar nicht nötig:
 * lokal und live sollen sich in genau diesen Punkten NICHT
 * unterscheiden. Also stehen sie fest hier.
 *
 * Ladereihenfolge in Concrete (später gewinnt):
 *   1. concrete/config/concrete.php                  (Kern-Vorgaben)
 *   2. application/config/concrete.php               ← diese Datei
 *   3. application/config/<umgebung>.concrete.php    (nicht benutzt)
 *   4. application/config/generated_overrides/…      (Dashboard-Klicks)
 *
 * Punkt 4 gewinnt. Was hier steht, ist also die Grundlinie einer
 * frischen Installation — was jemand später im Dashboard umstellt,
 * bleibt umgestellt. Genau so soll es sein.
 */

return [

    /*
     * Sprache der Oberfläche und der Inhalte. Steht auch in den
     * generated_overrides der laufenden Installation; hier, damit
     * eine Wiederherstellung nicht auf Englisch hochkommt.
     */
    'locale' => 'de_DE',

    'seo' => [
        /*
         * Sprechende URLs (/gruppen statt /index.php/gruppen).
         * Braucht mod_rewrite und die .htaccess im Wurzelverzeichnis.
         */
        'url_rewriting' => true,

        /*
         * Aufbau des <title>. Concrete setzt ihn aus zwei Teilen
         * zusammen: %1$s ist der Name der Website (Dashboard >
         * System und Einstellungen > Website-Name), %2$s der Name
         * der aufgerufenen Seite.
         *
         * Die Vorgabe des Kerns ist '%2$s :: %1$s' — zwei Doppelpunkte
         * als Trennung, und bei einer frisch aufgesetzten Installation
         * dahinter der Auslieferungsname „Concrete“. Das stand so in
         * jedem Browserreiter.
         *
         * Jetzt: „Seitenname • Stammesname”.
         */
        'title_format' => '%2$s • %1$s',

        /*
         * Trennung zwischen mehreren Titelabschnitten. Mehr als einen
         * Abschnitt setzen einzelne Seiten selbst zusammen (etwa die
         * Suche: „Suchergebnisse • Suche“). Gleiches Zeichen wie oben,
         * damit ein solcher Titel nicht zwei Trennarten mischt.
         */
        'title_segment_separator' => ' • ',
    ],

    'upload' => [
        /*
         * Erlaubte Dateiendungen im Dateimanager. Gegenüber der
         * Concrete-Vorgabe ergänzt um svg, webm, webp, ogg, ogv,
         * vtt und srt — das Theme und die Blöcke brauchen sie
         * (Ikons als SVG, Videoblock mit Untertiteln).
         */
        'extensions' => '*.flv;*.jpg;*.gif;*.jpeg;*.ico;*.docx;*.xla;*.png;*.psd;*.swf;*.doc;*.txt;*.xls;*.xlsx;*.csv;*.pdf;*.tiff;*.rtf;*.m4a;*.mov;*.wmv;*.mpeg;*.mpg;*.wav;*.3gp;*.avi;*.m4v;*.mp4;*.mp3;*.qt;*.ppt;*.pptx;*.kml;*.xml;*.svg;*.webm;*.webp;*.ogg;*.ogv;*.vtt;*.srt',
    ],

    'security' => [
        /*
         * Vertrauenswürdige Proxies (Cloudflare).
         *
         * Ohne diese Liste sieht Concrete die wechselnden Edge-IPs von
         * Cloudflare als Client-IP. Da `invalidate_on_ip_mismatch`
         * standardmäßig aktiv ist, wird die Sitzung sonst mitten im
         * Arbeiten mit `/login/session_invalidated` abgeworfen, sobald
         * Cloudflare eine Anfrage über einen anderen Knoten schickt.
         *
         * Offizielle Cloudflare-Netze (https://www.cloudflare.com/ips/):
         */
        'trusted_proxies' => [
            'ips' => [
                '173.245.48.0/20',
                '103.21.244.0/22',
                '103.22.200.0/22',
                '103.31.4.0/22',
                '141.101.64.0/18',
                '108.162.192.0/18',
                '190.93.240.0/20',
                '188.114.96.0/20',
                '197.234.240.0/22',
                '198.41.128.0/17',
                '162.158.0.0/15',
                '104.16.0.0/13',
                '104.24.0.0/14',
                '172.64.0.0/13',
                '131.0.72.0/22',
                '2400:cb00::/32',
                '2606:4700::/32',
                '2803:f800::/32',
                '2405:b500::/32',
                '2405:8100::/32',
                '2a06:98c0::/29',
                '2c0f:f248::/32',
            ],
            // Request::HEADER_X_FORWARDED_FOR (2) | Request::HEADER_X_FORWARDED_PROTO (8) = 10
            'headers' => 10,
        ],
    ],
];
