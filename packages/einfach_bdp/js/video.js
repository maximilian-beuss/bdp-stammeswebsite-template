/* =============================================================
   Videospieler des Stammes
   -------------------------------------------------------------
   Hängt Plyr an jedes <video data-v-video>. Mehr passiert hier
   nicht — das Markup steht fertig im Block, und ohne diese Datei
   bleibt der Spieler des Browsers stehen. Das ist Absicht: ein
   Video, das nur mit JavaScript läuft, ist ein Video, das für
   manche gar nicht läuft.

   Warum die Symbole aus dem Paket kommen
   --------------------------------------
   Plyr lädt seine Symbole von Haus aus als SVG-Sprite von
   cdn.plyr.io nach. Das wäre eine fremde Anfrage bei jedem Aufruf
   — datenschutzrechtlich unnötig und offline kaputt. Die Datei
   liegt deshalb im Paket, und der Pfad wird aus der Adresse dieses
   Skripts abgeleitet: so stimmt er auch, wenn die Installation in
   einem Unterverzeichnis liegt.
   ============================================================= */
(function () {
    'use strict';

    var mich = document.currentScript;
    var basis = mich ? mich.src.replace(/\/js\/video\.js.*$/, '') : '';

    /* Deutsche Beschriftungen. Plyr liefert englische mit; die stehen
       sonst mitten in einer deutschen Seite und werden von
       Screenreadern auch englisch vorgelesen ✅ WCAG 3.1.2 */
    var WORTE = {
        restart: 'Neu starten',
        rewind: '{seektime} s zurück',
        play: 'Abspielen',
        pause: 'Pause',
        fastForward: '{seektime} s vor',
        seek: 'Suchen',
        seekLabel: '{currentTime} von {duration}',
        played: 'Abgespielt',
        buffered: 'Geladen',
        currentTime: 'Aktuelle Zeit',
        duration: 'Dauer',
        volume: 'Lautstärke',
        mute: 'Stumm schalten',
        unmute: 'Ton an',
        enableCaptions: 'Untertitel einblenden',
        disableCaptions: 'Untertitel ausblenden',
        download: 'Herunterladen',
        enterFullscreen: 'Vollbild an',
        exitFullscreen: 'Vollbild aus',
        frameTitle: 'Spieler für {title}',
        captions: 'Untertitel',
        settings: 'Einstellungen',
        menuBack: 'Zurück',
        speed: 'Geschwindigkeit',
        normal: 'Normal',
        quality: 'Qualität',
        loop: 'Schleife',
        start: 'Anfang',
        end: 'Ende',
        all: 'Alles',
        reset: 'Zurücksetzen',
        disabled: 'Aus',
        enabled: 'An',
        advertisement: 'Werbung',
        qualityBadge: { 2160: '4K', 1440: 'HD', 1080: 'HD', 720: 'HD', 576: 'SD', 480: 'SD' }
    };

    function starte() {
        var spieler = document.querySelectorAll('[data-v-video]');
        if (spieler.length === 0 || typeof window.Plyr === 'undefined') {
            return;
        }

        Array.prototype.forEach.call(spieler, function (el) {
            if (el.dataset.vVideoLaeuft) {
                return;
            }
            el.dataset.vVideoLaeuft = '1';

            /* Untertitel an, sobald eine Spur `default` trägt. Plyr
               liest das nicht selbst aus dem Markup.

               Die Sprache muss mit: Plyrs Voreinstellung ist 'auto',
               und das heißt navigator.language. Auf einem englisch
               eingestellten Browser lief damit die englische Spur an,
               obwohl in der Maske die deutsche als Standard angehakt
               war. Was die Redaktion dort einträgt, gilt. */
            var standard = el.querySelector('track[default]');
            var sprache = standard ? standard.getAttribute('srclang') : 'auto';

            new window.Plyr(el, {
                i18n: WORTE,
                iconUrl: basis + '/images/plyr.svg',
                controls: [
                    'play-large', 'play', 'progress', 'current-time',
                    'mute', 'volume', 'captions', 'settings', 'fullscreen'
                ],
                settings: ['captions', 'speed'],
                speed: { selected: 1, options: [0.75, 1, 1.25, 1.5, 2] },
                captions: { active: !!standard, language: sprache, update: true },
                keyboard: { focused: true, global: false },
                tooltips: { controls: true, seek: true },
                /* Kein Vollbild-Ersatz per CSS: Der Dashboard-Rahmen
                   und die Concrete-Leiste liegen sonst darüber. */
                fullscreen: { enabled: true, fallback: true, iosNative: true }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', starte);
    } else {
        starte();
    }
})();
