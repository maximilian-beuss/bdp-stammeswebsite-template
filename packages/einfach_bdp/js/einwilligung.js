/* =============================================================
   Einwilligungen für eingebettete Inhalte
   -------------------------------------------------------------
   Zwei Blöcke hängen daran:

     bdp_karte          der Platzhalter, der nach Zustimmung
                             den iframe nachlädt
     bdp_einwilligung   die Liste auf der Datenschutzseite,
                             auf der man die Zustimmung zurücknimmt

   Wo die Entscheidung liegt
   -------------------------
   Im localStorage und nicht in einem Cookie: Ein Cookie ginge bei
   jeder Anfrage an den Server mit, obwohl der Server damit nichts
   zu tun hat. Der Wert bleibt so auf dem Gerät, auf dem er gefällt
   wurde — das ist auch die ehrlichere Aussage gegenüber dem
   Besucher als „gilt für dich“.

   Ein privates Fenster wirft beim Schreiben; das ist kein Fehler,
   sondern eine Antwort. Dann gilt die Zustimmung eben nur für
   diesen Besuch.
   ============================================================= */
(function () {
    'use strict';

    var VORSATZ = 'bdp-einwilligung-';

    /* Was es gibt. Eine neue Einbettung wird hier eingetragen, dann
       taucht sie von selbst in der Widerrufsliste auf. */
    var DIENSTE = {
        karte: {
            name: 'Karten',
            erklaerung: 'Eingebettete Karten von Google Maps auf der Kontaktseite.'
        }
    };

    function lesen(schluessel) {
        try {
            return window.localStorage.getItem(VORSATZ + schluessel) === 'ja';
        } catch (e) {
            return false;
        }
    }

    function schreiben(schluessel, ja) {
        try {
            if (ja) {
                window.localStorage.setItem(VORSATZ + schluessel, 'ja');
            } else {
                window.localStorage.removeItem(VORSATZ + schluessel);
            }
        } catch (e) { /* privates Fenster: gilt nur für diesen Besuch */ }

        /* Andere Blöcke auf derselben Seite sollen sofort mitbekommen,
           dass sich etwas geändert hat. Das Storage-Ereignis des
           Browsers feuert nur in anderen Tabs, nicht im eigenen. */
        window.dispatchEvent(new CustomEvent('bdp:einwilligung', {
            detail: { dienst: schluessel, erteilt: ja }
        }));
    }

    /* ---------- Die Einbettungen ---------- */

    function rahmenBauen(kasten) {
        if (kasten.querySelector('iframe')) {
            return;
        }

        var rahmen = document.createElement('iframe');
        rahmen.src = kasten.getAttribute('data-einwilligung-quelle');
        rahmen.title = kasten.getAttribute('data-titel') || 'Eingebetteter Inhalt';
        rahmen.loading = 'lazy';
        rahmen.setAttribute('allowfullscreen', '');
        rahmen.setAttribute('referrerpolicy',
            kasten.getAttribute('data-referrer') || 'no-referrer');

        var hoehe = parseInt(kasten.getAttribute('data-hoehe'), 10);
        if (hoehe > 0) {
            rahmen.style.height = hoehe + 'px';
        }

        kasten.appendChild(rahmen);
        kasten.classList.add('ist-geladen');
    }

    function rahmenEntfernen(kasten) {
        var rahmen = kasten.querySelector('iframe');
        if (rahmen) {
            rahmen.parentNode.removeChild(rahmen);
        }
        kasten.classList.remove('ist-geladen');
    }

    function einbettungen() {
        var kaesten = document.querySelectorAll('[data-einwilligung]');

        Array.prototype.forEach.call(kaesten, function (kasten) {
            var dienst = kasten.getAttribute('data-einwilligung');
            var knopf = kasten.querySelector('[data-einwilligung-ja]');

            /* Der Knopf steht im HTML mit hidden. Erst hier, wo klar
               ist, dass das Skript läuft, wird er sichtbar.

               Eigene Marke statt der Klasse ist-bereit: die trug
               früher auch das Theme an denselben Kasten, und wer
               zuerst kam, ließ den anderen ins Leere laufen. */
            if (knopf && !kasten.hasAttribute('data-einwilligung-bereit')) {
                knopf.hidden = false;
                knopf.addEventListener('click', function () {
                    schreiben(dienst, true);
                });
            }
            kasten.setAttribute('data-einwilligung-bereit', '');

            if (lesen(dienst)) {
                rahmenBauen(kasten);
            } else {
                rahmenEntfernen(kasten);
            }
        });
    }

    /* ---------- Die Widerrufsliste ---------- */

    function verwaltung() {
        var kaesten = document.querySelectorAll('[data-einwilligung-verwaltung]');

        Array.prototype.forEach.call(kaesten, function (kasten) {
            var liste = kasten.querySelector('[data-einwilligung-liste]');
            var ohnejs = kasten.querySelector('[data-einwilligung-ohnejs]');
            var meldung = kasten.querySelector('[data-einwilligung-meldung]');
            if (!liste) {
                return;
            }

            liste.hidden = false;
            if (ohnejs) {
                ohnejs.hidden = true;
            }

            var zeichnen = function () {
                liste.textContent = '';

                Object.keys(DIENSTE).forEach(function (schluessel) {
                    var dienst = DIENSTE[schluessel];
                    var erteilt = lesen(schluessel);

                    var zeile = document.createElement('li');
                    zeile.className = 'v-einwilligung__zeile';

                    var text = document.createElement('div');
                    var name = document.createElement('strong');
                    name.textContent = dienst.name;
                    var stand = document.createElement('span');
                    stand.className = 'v-einwilligung__stand';
                    stand.textContent = erteilt ? 'erlaubt' : 'nicht erlaubt';
                    stand.setAttribute('data-erteilt', erteilt ? 'ja' : 'nein');

                    var erklaerung = document.createElement('p');
                    erklaerung.className = 'v-einwilligung__erklaerung';
                    erklaerung.textContent = dienst.erklaerung;

                    text.appendChild(name);
                    text.appendChild(document.createTextNode(' '));
                    text.appendChild(stand);
                    text.appendChild(erklaerung);

                    var knopf = document.createElement('button');
                    knopf.type = 'button';
                    // Zurücknehmen als Umriss, Erlauben gefüllt: Der
                    // zurückhaltendere Knopf gehört zur Handlung, die
                    // nichts nachlädt.
                    knopf.className = erteilt ? 'btn btn--linie' : 'btn';
                    knopf.textContent = erteilt ? 'Zurücknehmen' : 'Erlauben';
                    knopf.addEventListener('click', function () {
                        schreiben(schluessel, !erteilt);
                        if (meldung) {
                            meldung.textContent = erteilt
                                ? dienst.name + ': Einwilligung zurückgenommen. Es wird nichts mehr nachgeladen.'
                                : dienst.name + ': Einwilligung erteilt.';
                        }
                    });

                    zeile.appendChild(text);
                    zeile.appendChild(knopf);
                    liste.appendChild(zeile);
                });
            };

            zeichnen();
            window.addEventListener('bdp:einwilligung', zeichnen);
            // Zweiter Tab, gleiches Gerät.
            window.addEventListener('storage', function (e) {
                if (!e.key || e.key.indexOf(VORSATZ) === 0) { zeichnen(); }
            });
        });
    }

    function starte() {
        einbettungen();
        verwaltung();
        window.addEventListener('bdp:einwilligung', einbettungen);
        window.addEventListener('storage', function (e) {
            if (!e.key || e.key.indexOf(VORSATZ) === 0) { einbettungen(); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', starte);
    } else {
        starte();
    }
})();
