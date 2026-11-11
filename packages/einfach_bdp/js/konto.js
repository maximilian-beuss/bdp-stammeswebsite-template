/* =============================================================
   Kontodaten
   -------------------------------------------------------------
   IBAN und BIC sind Knöpfe. Ein Klick öffnet ein Fenster mit dem
   GiroCode — die Banking-App liest ihn, und Empfänger, IBAN und
   BIC stehen fertig im Überweisungsformular. Das erspart das
   Abtippen von 22 Stellen, der häufigsten Fehlerquelle bei
   Überweisungen.

   Daneben lässt sich jede Angabe einzeln kopieren, der Empfänger
   eingeschlossen. Er ist die längste der drei und war vorher die
   einzige, die man abtippen musste.

   Ohne JavaScript bleiben alle Angaben als Text lesbar; nur das
   Fenster geht nicht auf. Der Code darin ist ohnehin nur eine
   bequemere Schreibweise derselben Daten ✅
   ============================================================= */
(function () {
    'use strict';

    function konto(wurzel) {
        if (wurzel.hasAttribute('data-konto-laeuft')) {
            return;
        }
        wurzel.setAttribute('data-konto-laeuft', '1');

        var dialog = wurzel.querySelector('[data-konto-fenster]');
        var meldung = wurzel.querySelector('[data-konto-meldung]');
        if (!dialog || typeof dialog.showModal !== 'function') {
            return;
        }

        Array.prototype.forEach.call(wurzel.querySelectorAll('[data-konto-auf]'), function (knopf) {
            knopf.addEventListener('click', function () {
                if (meldung) { meldung.textContent = ''; }
                dialog.showModal();
            });
        });

        var zu = dialog.querySelector('[data-konto-zu]');
        if (zu) {
            zu.addEventListener('click', function () { dialog.close(); });
        }

        // Klick auf die Fläche neben dem Fenster schließt es.
        dialog.addEventListener('click', function (e) {
            if (e.target === dialog) { dialog.close(); }
        });

        Array.prototype.forEach.call(dialog.querySelectorAll('[data-konto-kopieren]'), function (knopf) {
            knopf.addEventListener('click', function () {
                var zeile = knopf.closest('div');
                var quelle = zeile ? zeile.querySelector('[data-konto-kopierwert]') : null;
                if (!quelle) { return; }

                /* IBAN und BIC ohne Leerzeichen — so erwartet es jedes
                   Überweisungsformular. Der Empfängername dagegen im
                   Wortlaut: „BundderPfadfinderinnen…“ wäre falsch. */
                var wert = quelle.textContent.replace(/\s+/g, ' ').trim();
                if (!quelle.hasAttribute('data-konto-wortlaut')) {
                    wert = wert.replace(/\s+/g, '');
                }

                var name = knopf.getAttribute('data-konto-name') || 'Wert';

                function sagen(text) {
                    if (meldung) { meldung.textContent = text; }
                }

                // Die Zwischenablage-Schnittstelle gibt es nur über HTTPS
                // und auf localhost.
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(wert).then(function () {
                        sagen(name + ' kopiert.');
                    }, function () {
                        auswaehlen(quelle, name, sagen);
                    });
                    return;
                }

                auswaehlen(quelle, name, sagen);
            });
        });
    }

    /**
     * Rückfall ohne Zwischenablage: den Wert markieren, damit
     * Strg+C genügt. Besser als ein Satz, der zum Abtippen auffordert.
     */
    function auswaehlen(quelle, name, sagen) {
        try {
            var bereich = document.createRange();
            bereich.selectNodeContents(quelle);
            var auswahl = window.getSelection();
            auswahl.removeAllRanges();
            auswahl.addRange(bereich);
            sagen(name + ' ist markiert — jetzt Strg+C drücken.');
        } catch (e) {
            sagen('Bitte von Hand markieren und kopieren.');
        }
    }

    function alle() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-konto]'), konto);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', alle);
    } else {
        alle();
    }

    // Concrete lädt Blockinhalte im Bearbeitungsmodus nach.
    document.addEventListener('ccm-block-added', alle);
})();
