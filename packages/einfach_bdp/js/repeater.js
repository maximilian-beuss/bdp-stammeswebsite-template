/* =============================================================
   Wiederholfelder für die Blockmasken des Stammes
   -------------------------------------------------------------
   Alle Blöcke dieses Pakets, die Listen pflegen (Hero-Folien,
   Antragsschritte, Produkte, Galeriebilder, Telefonnummern), teilen
   sich diese eine Mechanik: hinzufügen, löschen, verschieben,
   Bild wählen.

   Wie die Namen zustande kommen
   -----------------------------
   Jede Zeile trägt Feldnamen der Form  liste[7][titel].  Die Zahl ist
   nur ein Zähler und bedeutet nichts: PHP baut das Array in der
   Reihenfolge auf, in der die Felder im Formular stehen, und der
   Block ruft beim Speichern array_values() darauf. Wer eine Zeile
   nach oben schiebt, verschiebt damit das DOM — und das genügt.
   Deshalb muss beim Sortieren nichts umnummeriert werden.

   Bilder
   ------
   ConcreteFileManager.launchDialog ist die einzige unterstützte Art,
   den Dateimanager aus eigenem Markup heraus zu öffnen. Der Umweg
   über getFileDetails liefert das Vorschaubild mit, sonst müsste man
   die Miniatur selbst zusammenbauen.
   ============================================================= */
(function ($) {
    'use strict';

    var zaehler = Date.now() % 100000;

    function naechsterIndex() {
        return ++zaehler;
    }

    /**
     * Setzt __I__ in allen Attributen eines frisch eingefügten
     * Fragments auf einen freien Zähler.
     */
    function indexSetzen($zeile) {
        var i = naechsterIndex();
        $zeile.find('[name*="__I__"], [id*="__I__"], [for*="__I__"]').each(function () {
            var el = $(this);
            ['name', 'id', 'for'].forEach(function (attr) {
                var wert = el.attr(attr);
                if (wert && wert.indexOf('__I__') !== -1) {
                    el.attr(attr, wert.split('__I__').join(i));
                }
            });
        });
        return $zeile;
    }

    /**
     * Der Bildwähler hängt am Dokument und nicht am einzelnen Block.
     *
     * Vorher wurde er nur innerhalb von [data-rep] gebunden — im
     * Abschnitt steht das Bildfeld aber für sich allein, ohne
     * Wiederholliste, und der Knopf tat dort schlicht nichts.
     * Delegiert am Dokument gilt die Bindung für jedes Bildfeld,
     * gleich ob es zu einer Liste gehört, und frisch eingefügte
     * Zeilen brauchen keine Nachbindung mehr.
     *
     * Die Masken kommen per AJAX, das Skript läuft also je geöffnetem
     * Dialog einmal. off() vor on() hält es bei einer Bindung.
     */
    function bildWaehlerBinden() {
        var $dok = $(document);

        $dok.off('click.vrep', '[data-rep-bild]').on('click.vrep', '[data-rep-bild]', function (e) {
            e.preventDefault();
            var $knopf = $(this);
            if (typeof ConcreteFileManager === 'undefined') {
                return;
            }
            ConcreteFileManager.launchDialog(function (daten) {
                ConcreteFileManager.getFileDetails(daten.fID, function (r) {
                    $.fn.dialog.hideLoader();
                    if (!r || !r.files || !r.files[0]) {
                        return;
                    }
                    var datei = r.files[0];
                    $knopf.html(datei.resultsThumbnailImg);
                    $knopf.closest('[data-rep-bildfeld]').find('[data-rep-bild-wert]').val(datei.fID);
                });
            });
        });

        $dok.off('click.vrep', '[data-rep-bild-weg]').on('click.vrep', '[data-rep-bild-weg]', function (e) {
            e.preventDefault();
            var $feld = $(this).closest('[data-rep-bildfeld]');
            var $knopf = $feld.find('[data-rep-bild]');
            $feld.find('[data-rep-bild-wert]').val('');
            $knopf.text($knopf.data('leerText') || 'Bild wählen');
        });
    }

    /**
     * Nummeriert die sichtbaren Zeilen durch. Rein kosmetisch — die
     * Beschriftung „Folie 3“ hilft beim Zurechtfinden.
     */
    function beschriften($block) {
        $block.find('[data-rep-zeilen] > [data-rep-zeile]').each(function (i) {
            $(this).find('[data-rep-nummer]').text(i + 1);
        });
        var leer = $block.find('[data-rep-zeilen] > [data-rep-zeile]').length === 0;
        $block.find('[data-rep-leer]').toggle(leer);
    }

    function starte($block) {
        var $zeilen = $block.find('[data-rep-zeilen]').first();

        $block.find('[data-rep-neu]').on('click', function (e) {
            e.preventDefault();
            var roh = $block.find('[data-rep-vorlage]').first().html();
            var $neu = indexSetzen($(roh.trim()));
            $zeilen.append($neu);
            beschriften($block);
            $neu.find('input, textarea, select').filter(':visible').first().focus();
        });

        $zeilen.on('click', '[data-rep-weg]', function (e) {
            e.preventDefault();
            var $zeile = $(this).closest('[data-rep-zeile]');
            // Kein confirm(): der Block wird erst mit „Speichern“
            // wirklich geändert, ein Versehen kostet also nur Abbrechen.
            $zeile.remove();
            beschriften($block);
        });

        $zeilen.on('click', '[data-rep-hoch]', function (e) {
            e.preventDefault();
            var $z = $(this).closest('[data-rep-zeile]');
            $z.prev('[data-rep-zeile]').before($z);
            beschriften($block);
        });

        $zeilen.on('click', '[data-rep-runter]', function (e) {
            e.preventDefault();
            var $z = $(this).closest('[data-rep-zeile]');
            $z.next('[data-rep-zeile]').after($z);
            beschriften($block);
        });

        // Zusammenklappen, damit lange Listen überschaubar bleiben
        $zeilen.on('click', '[data-rep-klapp]', function (e) {
            e.preventDefault();
            $(this).closest('[data-rep-zeile]').toggleClass('ist-zu');
        });

        beschriften($block);
    }

    $(function () {
        bildWaehlerBinden();
        $('[data-rep]').each(function () {
            starte($(this));
        });
    });
})(jQuery);
