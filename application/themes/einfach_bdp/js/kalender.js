/* ============================================================
   Stammeskalender
   ------------------------------------------------------------
   Vier Ansichten über denselben Daten:

     monat      Sieben Spalten, eine Zelle je Tag. Klassisches
                Kalenderblatt.
     halbjahr   Sechs Monate nebeneinander, jeder Monat eine
                senkrechte Liste seiner Tage.
     jahr       Dasselbe mit zwölf Monaten.
     liste      Fester Zeitraum, ohne Ferien und Feiertage.

   Die Daten kommen einmal als JSON von /kalender/daten und werden
   danach nur noch im Browser umgerechnet — Blättern lädt nichts nach.

   Warum eigene Datumsarithmetik statt einer Bibliothek
   ----------------------------------------------------
   Gebraucht werden genau drei Dinge: den Monatsersten finden, Tage
   addieren und zwei Tage vergleichen. Dafür eine Bibliothek zu
   laden wäre mehr Code als die Rechnerei selbst.

   Ein Fallstrick dabei ist die Sommerzeit. new Date(a).setDate(x+1)
   springt in der Nacht der Umstellung nicht sauber, wenn man von
   Mitternacht ausgeht. Deshalb rechnet alles hier auf 12 Uhr
   mittags — dann liegt der Sprung nie an einer Tagesgrenze.
   ============================================================ */
(function () {
  'use strict';

  var TAGE = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
  var TAGE_LANG = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
  var MONATE = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
  var MONATE_KURZ = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun',
    'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];

  /* Wie viele Monate nebeneinander passen.

     Erlaubt sind 1, 2, 3, 4, 6 und 12 — Teiler von 12, damit die
     Zeilen aufgehen. Vier gibt es nur in der Jahresansicht: Im
     Halbjahr entstünden daraus zwei ungleiche Zeilen (4 + 2).

     Ausgewählt wird die größte Zahl, bei der jede Monatsspalte noch
     mindestens MIN_SPALTE breit ist. Darunter wird die Tagesliste
     unlesbar, weil die Terminkästen nur noch Buchstabensuppe zeigen.

     132 px ist knapp: Davon gehen rund 50 px für Tageszahl und
     Wochentag ab, es bleiben gut 80 px für den Titel. Das reicht für
     „Pfingstlager“, nicht für „Kurs für Meutenführungen“ — den kürzt
     die Anzeige dann ab und zeigt ihn vollständig im Tooltip und im
     Terminfenster. Für einen Jahresüberblick an der Wand ist das der
     richtige Kompromiss; wer Einzelheiten will, wechselt in den
     Monat. */
  var MIN_SPALTE = 132;

  /* Auf kleinen Schirmen darf eine Spalte nicht so schmal werden.

     Rechnerisch passten auf ein 390-px-Telefon zwei Monate à 179 px.
     In der Hand liest sich das aber schlecht: zwei parallele
     Tagesspuren, durch die man gleichzeitig scrollt, und Titel, die
     nach drei Buchstaben abbrechen. Ab 700 px Fensterbreite abwärts
     verlangt eine Spalte deshalb 190 px, was auf dem Telefon
     verlässlich auf eine Spalte hinausläuft. */
  var MIN_SPALTE_KLEIN = 190;

  function spaltenFuer(monate, breite) {
    var moeglich = monate === 12 ? [12, 6, 4, 3, 2, 1] : [6, 3, 2, 1];
    var min = document.documentElement.clientWidth < 700 ? MIN_SPALTE_KLEIN : MIN_SPALTE;

    for (var i = 0; i < moeglich.length; i++) {
      var n = moeglich[i];
      if (n <= monate && breite / n >= min) { return n; }
    }

    return 1;
  }


  /* ----------------------------------------------------------
     Datum
     ---------------------------------------------------------- */

  /** 'YYYY-MM-DD' oder 'YYYY-MM-DD HH:MM:SS' zu einem Date um 12 Uhr. */
  function ausText(text) {
    var t = String(text).slice(0, 10).split('-');
    return new Date(+t[0], +t[1] - 1, +t[2], 12, 0, 0);
  }

  function plusTage(d, n) {
    var neu = new Date(d.getTime());
    neu.setDate(neu.getDate() + n);
    neu.setHours(12, 0, 0, 0);
    return neu;
  }

  function gleicherTag(a, b) {
    return a.getFullYear() === b.getFullYear()
      && a.getMonth() === b.getMonth()
      && a.getDate() === b.getDate();
  }

  /** Ganze Tage von a bis b. Beide auf 12 Uhr, deshalb ohne Rundungsfehler. */
  function tageZwischen(a, b) {
    return Math.round((b.getTime() - a.getTime()) / 86400000);
  }

  /** Wochentag mit Montag = 0. getDay() zählt ab Sonntag. */
  function wochentag(d) {
    return (d.getDay() + 6) % 7;
  }

  function istWochenende(d) {
    return wochentag(d) >= 5;
  }

  function monatsErster(jahr, monat) {
    return new Date(jahr, monat, 1, 12, 0, 0);
  }

  function tageImMonat(jahr, monat) {
    return new Date(jahr, monat + 1, 0, 12, 0, 0).getDate();
  }


  /* ----------------------------------------------------------
     Ein Kalender
     ---------------------------------------------------------- */

  /* Liegt an den Tagen von s etwas in dieser Spur? Geprüft wird
     gegen die zugeteilten Spuren, nicht gegen die verbreiterten —
     verbreitert wird immer nur nach rechts, ein Balken kann also
     nie in einen anderen hineinwachsen. */
  function spurFrei(alle, s, spur) {
    for (var i = 0; i < alle.length; i++) {
      var a = alle[i];

      if (a !== s && a.spur === spur && a.von <= s.bis && a.bis >= s.von) {
        return false;
      }
    }
    return true;
  }

  function kalender(wurzel) {
    if (wurzel.hasAttribute('data-kal-laeuft')) { return; }
    wurzel.setAttribute('data-kal-laeuft', '1');

    var flaeche = wurzel.querySelector('[data-kal-flaeche]');
    var ersatz = wurzel.querySelector('[data-kal-ersatz]');
    var steuer = wurzel.querySelector('[data-kal-steuer]');
    var ansichten = wurzel.querySelector('[data-kal-ansichten]');
    var zeitraumText = wurzel.querySelector('[data-kal-zeitraum]');
    var legende = wurzel.querySelector('.v-kal__legende');
    if (!flaeche) { return; }

    var ansicht = wurzel.getAttribute('data-kal-ansicht') || 'monat';
    var zeigeFrei = wurzel.getAttribute('data-kal-frei') === '1';
    var stufenFilter = (wurzel.getAttribute('data-kal-stufen') || '')
      .split(',').filter(function (s) { return s !== ''; });

    var heute = new Date();
    heute.setHours(12, 0, 0, 0);

    var anker = new Date(heute.getTime());     // welcher Monat/Halbjahr/Jahr
    var termine = [];
    var frei = [];
    var stufen = {};
    var fenster = null;


    /* --- Daten holen --------------------------------------------- */

    function laden() {
      var url = wurzel.getAttribute('data-kal-daten');
      if (!url) { return Promise.reject(new Error('keine Adresse')); }

      var von = (heute.getFullYear() - 1) + '-01-01';
      var bis = (heute.getFullYear() + 1) + '-12-31';

      return fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'von=' + von + '&bis=' + bis, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      }).then(function (a) {
        if (!a.ok) { throw new Error('HTTP ' + a.status); }
        return a.json();
      }).then(function (d) {
        stufen = d.stufen || {};
        frei = (d.frei || []).map(function (f) {
          f.von = ausText(f.beginn);
          f.bis = ausText(f.ende);
          return f;
        });
        termine = (d.termine || []).filter(function (t) {
          return stufenFilter.length === 0 || stufenFilter.indexOf(t.stufe) !== -1;
        }).map(function (t) {
          t.von = ausText(t.beginn);
          t.bis = ausText(t.ende);
          t.tage = tageZwischen(t.von, t.bis) + 1;
          return t;
        });
      });
    }


    /* --- Nachschlagen -------------------------------------------- */

    function farbe(handle) {
      return (stufen[handle] && stufen[handle].farbe) || '#0E4278';
    }

    function stufenName(handle) {
      return (stufen[handle] && stufen[handle].name) || '';
    }

    /** Alle Termine, die diesen Tag berühren. */
    function amTag(tag) {
      return termine.filter(function (t) {
        return t.von <= tag && t.bis >= tag;
      });
    }

    /**
     * Schulfrei? Das sind Ferien, Feiertage — und Wochenenden.
     *
     * Samstag und Sonntag stehen in keiner Ferienliste, schulfrei sind
     * sie trotzdem. Für einen Stammeskalender ist das die
     * entscheidende Information: An diesen Tagen kann man fahren.
     *
     * @return {object|null} {titel, art}
     */
    function freiAmTag(tag) {
      if (!zeigeFrei) { return null; }

      for (var i = 0; i < frei.length; i++) {
        if (frei[i].von <= tag && frei[i].bis >= tag) {
          return { titel: frei[i].titel, art: frei[i].art };
        }
      }

      if (istWochenende(tag)) {
        return { titel: TAGE_LANG[wochentag(tag)], art: 'wochenende' };
      }

      return null;
    }

    /** Nur die Feiertage eines Tages, für die Anzeige als Eintrag. */
    function feiertageAmTag(tag) {
      if (!zeigeFrei) { return []; }

      return frei.filter(function (f) {
        return f.art === 'feiertag' && f.von <= tag && f.bis >= tag;
      });
    }

    function zeitraumEinesTermins(t) {
      var einTag = gleicherTag(t.von, t.bis);

      if (einTag) {
        var text = t.von.getDate() + '. ' + MONATE[t.von.getMonth()] + ' ' + t.von.getFullYear();
        if (!t.ganztaegig) {
          text += ', ' + t.beginn.slice(11, 16) + '–' + t.ende.slice(11, 16) + ' Uhr';
        }
        return text;
      }

      if (t.von.getFullYear() === t.bis.getFullYear() && t.von.getMonth() === t.bis.getMonth()) {
        return t.von.getDate() + '.–' + t.bis.getDate() + '. '
          + MONATE[t.von.getMonth()] + ' ' + t.von.getFullYear();
      }

      return t.von.getDate() + '. ' + MONATE[t.von.getMonth()]
        + (t.von.getFullYear() !== t.bis.getFullYear() ? ' ' + t.von.getFullYear() : '')
        + ' bis ' + t.bis.getDate() + '. ' + MONATE[t.bis.getMonth()] + ' ' + t.bis.getFullYear();
    }

    /** Maskiert alles, was aus den Daten kommt. */
    function text(roh) {
      var d = document.createElement('div');
      d.textContent = roh === null || roh === undefined ? '' : String(roh);
      return d.innerHTML;
    }


    /* --- Terminfenster -------------------------------------------- */

    function fensterBauen() {
      if (fenster) { return fenster; }

      fenster = document.createElement('dialog');
      fenster.className = 'v-kal__fenster';
      document.body.appendChild(fenster);

      fenster.addEventListener('click', function (e) {
        if (e.target === fenster || (e.target.closest && e.target.closest('[data-kal-fenster-zu]'))) {
          fenster.close();
        }
      });

      return fenster;
    }

    function terminZeigen(t) {
      var d = fensterBauen();
      if (typeof d.showModal !== 'function') { return; }

      d.style.setProperty('--stufe', farbe(t.stufe));

      var teile = [];
      teile.push('<h3 class="v-kal__fenster-titel">' + text(t.titel) + '</h3>');
      teile.push('<p class="v-kal__fenster-wann">' + text(zeitraumEinesTermins(t)) + '</p>');
      teile.push('<p class="v-kal__stufe">' + text(stufenName(t.stufe))
        + (t.ort ? ' · ' + text(t.ort) : '') + '</p>');
      if (t.beschreibung) {
        teile.push('<p class="v-kal__mehr">' + text(t.beschreibung).replace(/\n/g, '<br>') + '</p>');
      }
      if (t.link) {
        teile.push('<p><a class="pfeil-link" href="' + text(t.link) + '">Mehr dazu</a></p>');
      }
      teile.push('<p class="v-kal__fenster-zu"><button type="button" class="btn btn--linie" data-kal-fenster-zu>Schließen</button></p>');

      d.innerHTML = teile.join('');
      d.showModal();
    }


    /* --- Monatsansicht --------------------------------------------
       Ein Raster aus sieben Spalten. Jeder Tag bekommt die Termine,
       die ihn berühren — mehrtägige also an jedem ihrer Tage, mit
       der Angabe, der wievielte Tag es ist.
       -------------------------------------------------------------- */

    function monatBauen(jahr, monat) {
      var raster = document.createElement('div');
      raster.className = 'v-kal__monat';

      TAGE.forEach(function (t, i) {
        var kopf = document.createElement('div');
        kopf.className = 'v-kal__wochentag';
        kopf.innerHTML = '<span aria-hidden="true">' + t + '</span>'
          + '<span class="nur-sr">' + TAGE_LANG[i] + '</span>';
        raster.appendChild(kopf);
      });

      var erster = monatsErster(jahr, monat);
      var vorlauf = wochentag(erster);
      var anzahl = tageImMonat(jahr, monat);

      // Volle Wochen: Vorlauf aus dem Vormonat, Nachlauf aus dem
      // Folgemonat. Ohne die stünde die erste Zeile schief.
      var start = plusTage(erster, -vorlauf);
      var zellen = Math.ceil((vorlauf + anzahl) / 7) * 7;

      for (var i = 0; i < zellen; i++) {
        var tag = plusTage(start, i);
        raster.appendChild(tagBauen(tag, tag.getMonth() !== monat));
      }

      return raster;
    }

    function tagBauen(tag, fremd) {
      var zelle = document.createElement('div');
      zelle.className = 'v-kal__tag' + (fremd ? ' v-kal__tag--fremd' : '');

      var f = freiAmTag(tag);
      if (f) {
        zelle.classList.add('v-kal__tag--frei');
        zelle.classList.add('v-kal__tag--' + f.art);
        zelle.title = f.titel;
      }
      if (gleicherTag(tag, heute)) {
        zelle.classList.add('v-kal__tag--heute');
      }

      var zahl = document.createElement('span');
      zahl.className = 'v-kal__zahl';
      zahl.textContent = tag.getDate();
      if (gleicherTag(tag, heute)) {
        zahl.innerHTML = tag.getDate() + '<span class="nur-sr"> (heute)</span>';
      }
      zelle.appendChild(zahl);

      /* Feiertage stehen als eigener Eintrag im Tag und nicht nur als
         Schraffur im Hintergrund. Ein Hintergrundmuster sagt „hier ist
         etwas“, aber nicht was — und ob der 3. Oktober oder
         Fronleichnam frei ist, macht für die Planung einen
         Unterschied. Ferien bleiben Hintergrund: Sie dauern Wochen und
         stünden sonst an jedem einzelnen Tag noch einmal da. */
      /* Eigene Klasse und nicht .v-kal__marke: Die Marke ist die
         Form, in der ein Termin erscheint — farbiger Balken links,
         eigene Fläche, anklickbar. Ein Feiertag ist kein Termin und
         gehört keiner Stufe. Trug er dieselbe Form, war der Balken
         gelb, und Gelb ist im Kalender die Wölflingsstufe: Der
         3. Oktober sah aus wie ein Meutentermin. */
      feiertageAmTag(tag).forEach(function (ft) {
        var frei = document.createElement('span');
        frei.className = 'v-kal__frei';
        frei.textContent = ft.titel;
        frei.title = ft.titel;
        zelle.appendChild(frei);
      });

      var heutige = amTag(tag);
      var zeigen = heutige.slice(0, 3);

      zeigen.forEach(function (t) {
        var knopf = document.createElement('button');
        knopf.type = 'button';
        knopf.className = 'v-kal__marke';
        knopf.style.setProperty('--stufe', farbe(t.stufe));

        /* Bei mehrtägigen Terminen der wievielte Tag: „Osterfahrt
           (3/8)“. Ohne das steht acht Tage lang dasselbe da, und man
           sieht weder, wann es angefangen hat, noch wie lange es noch
           geht. */
        var nummer = '';
        if (t.tage > 1) {
          nummer = ' (' + (tageZwischen(t.von, tag) + 1) + '/' + t.tage + ')';
        }

        // Titel und Zählung als getrennte Elemente: Der Titel darf
        // abgeschnitten werden, die Zählung nicht (siehe CSS).
        knopf.innerHTML = '<span>' + text(t.titel) + '</span>'
          + (nummer ? '<span class="v-kal__marke-nr">' + text(nummer.trim()) + '</span>' : '');

        // Die Stufe steht im zugänglichen Namen, nicht nur in der
        // Farbe ✅ WCAG 1.4.1
        knopf.setAttribute('aria-label', t.titel + nummer + ', ' + stufenName(t.stufe)
          + ', ' + zeitraumEinesTermins(t));
        knopf.addEventListener('click', function () { terminZeigen(t); });
        zelle.appendChild(knopf);
      });

      if (heutige.length > zeigen.length) {
        var mehr = document.createElement('button');
        mehr.type = 'button';
        mehr.className = 'v-kal__mehrmarke';
        mehr.textContent = '+ ' + (heutige.length - zeigen.length) + ' weitere';
        mehr.addEventListener('click', function () {
          ansicht = 'liste';
          zeichnen();
        });
        zelle.appendChild(mehr);
      }

      return zelle;
    }


    /* --- Monatsspalte für Halbjahr und Jahr -----------------------
       Ein Monat als senkrechte Liste: eine Zeile je Tag, links die
       Zahl und der Wochentag, rechts die Terminkästen.

       Ein Termin ist ein Kasten über so viele Zeilen, wie er Tage in
       diesem Monat hat. Läuft er über den Monatswechsel, entsteht in
       jedem berührten Monat ein eigener Kasten; oben oder unten
       bekommt er dann eine offene Kante, damit man sieht, dass es
       weitergeht.

       Überschneiden sich Termine, brauchen sie eigene Spuren
       nebeneinander. Die Zuteilung ist gierig: Jeder Termin kommt in
       die erste Spur, in der er nicht mit dem letzten Kasten
       kollidiert. Das ist nicht optimal im Sinne der wenigsten
       Spuren, aber bei einer Handvoll Terminen je Monat sieht man
       keinen Unterschied — und es ist in fünf Zeilen erklärt.
       -------------------------------------------------------------- */

    function monatsSpalte(jahr, monat) {
      var spalte = document.createElement('div');
      spalte.className = 'v-kal__monatsspalte';

      var titel = document.createElement('h4');
      titel.className = 'v-kal__monatstitel';
      titel.textContent = MONATE[monat] + ' ' + jahr;
      spalte.appendChild(titel);

      var anzahl = tageImMonat(jahr, monat);
      var erster = monatsErster(jahr, monat);
      var letzter = new Date(jahr, monat, anzahl, 12, 0, 0);

      var raster = document.createElement('div');
      raster.className = 'v-kal__tageraster';
      raster.style.setProperty('--tage', anzahl);

      /* --- Die Tageszeilen --- */
      for (var d = 1; d <= anzahl; d++) {
        var tag = new Date(jahr, monat, d, 12, 0, 0);
        var zeile = document.createElement('div');
        zeile.className = 'v-kal__tagzeile';
        zeile.style.gridRow = String(d);

        var f = freiAmTag(tag);
        if (f) {
          zeile.classList.add('v-kal__tagzeile--frei');
          zeile.classList.add('v-kal__tagzeile--' + f.art);
          zeile.title = f.titel;
        }
        if (gleicherTag(tag, heute)) { zeile.classList.add('v-kal__tagzeile--heute'); }

        zeile.innerHTML = '<span class="v-kal__tagnr">' + d + '</span>'
          + '<span class="v-kal__tagwt">' + TAGE[wochentag(tag)] + '</span>';

        raster.appendChild(zeile);
      }

      /* --- Die Terminkästen --- */
      var stuecke = termine.filter(function (t) {
        return t.von <= letzter && t.bis >= erster;
      }).map(function (t) {
        // Auf den Monat zuschneiden. beginntFrueher / endetSpaeter
        // merken sich, dass der Termin über den Rand hinausgeht.
        var vonTag = t.von < erster ? 1 : t.von.getDate();
        var bisTag = t.bis > letzter ? anzahl : t.bis.getDate();

        return {
          termin: t,
          von: vonTag,
          bis: bisTag,
          vorher: t.von < erster,
          weiter: t.bis > letzter
        };
      }).sort(function (a, b) {
        // Nach Beginn, bei Gleichstand der längere zuerst — sonst
        // zerhackt ein kurzer Termin die Spur eines langen.
        return a.von - b.von || (b.bis - b.von) - (a.bis - a.von);
      });

      var spuren = [];   // je Spur der zuletzt belegte Tag

      stuecke.forEach(function (s) {
        var spur = 0;
        while (spur < spuren.length && spuren[spur] >= s.von) { ++spur; }
        spuren[spur] = s.bis;
        s.spur = spur;
      });

      var gesamt = Math.max(1, spuren.length);
      raster.style.setProperty('--spuren', gesamt);

      /* Ein einziger Parallelfall im Monat machte bisher den
         ganzen Monat schmalspaltig. Jeder Balken dehnt sich
         deshalb über die Nachbarspuren aus, solange dort an
         seinen eigenen Tagen nichts liegt — schmal wird nur,
         was sich wirklich einen Tag teilt. */
      stuecke.forEach(function (s) {
        var breite = 1;
        while (s.spur + breite < gesamt && spurFrei(stuecke, s, s.spur + breite)) {
          ++breite;
        }
        s.breite = breite;
      });

      stuecke.forEach(function (s) {
        var t = s.termin;
        var kasten = document.createElement('button');
        kasten.type = 'button';
        kasten.className = 'v-kal__balken'
          + (s.vorher ? ' v-kal__balken--vorher' : '')
          + (s.weiter ? ' v-kal__balken--weiter' : '');
        kasten.style.setProperty('--stufe', farbe(t.stufe));
        kasten.style.gridRow = s.von + ' / ' + (s.bis + 1);
        kasten.style.gridColumn = (s.spur + 2) + ' / span ' + (s.breite || 1);   // Spalte 1 sind die Tage

        kasten.innerHTML = '<span class="v-kal__balken-text">' + text(t.titel) + '</span>';
        kasten.setAttribute('aria-label', t.titel + ', ' + stufenName(t.stufe)
          + ', ' + zeitraumEinesTermins(t));
        kasten.title = t.titel + ' · ' + zeitraumEinesTermins(t);
        kasten.addEventListener('click', function () { terminZeigen(t); });

        raster.appendChild(kasten);
      });

      spalte.appendChild(raster);
      return spalte;
    }

    function periodeBauen(startJahr, startMonat, wieviele) {
      var kasten = document.createElement('div');
      kasten.className = 'v-kal__monate';

      /* Nicht die Breite des Containers messen, sondern die, die das
         Raster gleich bekommt: Es sprengt den Container und nimmt sich
         die Fensterbreite (siehe .v-kal__monate in bloecke.css). Mit
         der Containerbreite gerechnet käme immer eine Spalte zu wenig
         heraus. Dieselbe Formel wie im CSS. */
      var breite = Math.min(document.documentElement.clientWidth - 32, 2200);
      kasten.style.setProperty('--spalten', spaltenFuer(wieviele, breite));

      for (var i = 0; i < wieviele; i++) {
        var m = startMonat + i;
        var j = startJahr + Math.floor(m / 12);
        kasten.appendChild(monatsSpalte(j, ((m % 12) + 12) % 12));
      }

      return kasten;
    }


    /* --- Liste ----------------------------------------------------
       Fester Zeitraum: einen Monat zurück, dann alles, was kommt.
       Ohne Ferien und Feiertage — in einer Terminliste sind sie
       Rauschen, und wer wissen will, wann Ferien sind, schaut in eine
       der Monatsansichten.

       Vergangenes bleibt sichtbar, aber abgeblendet, und dazwischen
       steht eine Marke für heute. Ohne die müsste man die Daten lesen,
       um zu erkennen, wo die Gegenwart anfängt.
       -------------------------------------------------------------- */

    function listeBauen() {
      var grenze = plusTage(heute, -30);

      var passend = termine.filter(function (t) {
        return t.bis >= grenze;
      }).sort(function (a, b) {
        return a.von - b.von;
      });

      if (passend.length === 0) {
        var leer = document.createElement('p');
        leer.className = 'v-kal__leer';
        leer.textContent = 'Zurzeit stehen keine Termine an.';
        return leer;
      }

      var liste = document.createElement('ol');
      liste.className = 'v-kal__liste';

      var gabVergangenes = passend.some(function (t) { return t.bis < heute; });
      var heuteGesetzt = false;

      passend.forEach(function (t) {
        var vorbei = t.bis < heute;

        if (!vorbei && gabVergangenes && !heuteGesetzt) {
          heuteGesetzt = true;
          var marke = document.createElement('li');
          marke.className = 'v-kal__heutemarke';
          marke.innerHTML = '<span>Heute · ' + heute.getDate() + '. '
            + MONATE[heute.getMonth()] + '</span>';
          liste.appendChild(marke);
        }

        var eintrag = document.createElement('li');
        eintrag.className = 'v-kal__eintrag' + (vorbei ? ' v-kal__eintrag--vorbei' : '');
        eintrag.style.setProperty('--stufe', farbe(t.stufe));
        eintrag.innerHTML =
          '<time class="v-kal__wann" datetime="' + text(t.beginn.slice(0, 10)) + '">'
          + text(zeitraumEinesTermins(t)) + '</time>'
          + '<h4 class="v-kal__was">' + text(t.titel) + '</h4>'
          + '<p class="v-kal__stufe">' + text(stufenName(t.stufe))
          + (t.ort ? ' · ' + text(t.ort) : '') + '</p>'
          + (t.beschreibung ? '<p class="v-kal__mehr">' + text(t.beschreibung).replace(/\n/g, '<br>') + '</p>' : '')
          + (t.link ? '<p><a class="pfeil-link" href="' + text(t.link) + '">Mehr dazu</a></p>' : '');
        liste.appendChild(eintrag);
      });

      return liste;
    }


    /* --- Zeichnen -------------------------------------------------- */

    function zeichnen() {
      flaeche.innerHTML = '';

      var jahr = anker.getFullYear();
      var monat = anker.getMonth();
      var beschriftung = '';

      if (ansicht === 'monat') {
        flaeche.appendChild(monatBauen(jahr, monat));
        beschriftung = MONATE[monat] + ' ' + jahr;

      } else if (ansicht === 'halbjahr') {
        var erstesHalbjahr = monat < 6;
        var start = erstesHalbjahr ? 0 : 6;
        flaeche.appendChild(periodeBauen(jahr, start, 6));
        beschriftung = (erstesHalbjahr ? '1. Halbjahr ' : '2. Halbjahr ') + jahr;

      } else if (ansicht === 'jahr') {
        flaeche.appendChild(periodeBauen(jahr, 0, 12));
        beschriftung = String(jahr);

      } else {
        flaeche.appendChild(listeBauen());
        beschriftung = '';
      }

      /* In der Liste steht der Zeitraum fest — einen Monat zurück,
         dann alles. Vor und zurück hätte dort nichts zu blättern. */
      var listenansicht = ansicht === 'liste';
      if (steuer) { steuer.hidden = listenansicht; }
      if (zeitraumText) { zeitraumText.textContent = beschriftung; }

      // Ferien und Feiertage kommen in der Liste nicht vor, also
      // gehört auch ihr Legendeneintrag dort nicht hin.
      if (legende) {
        var freiEintrag = legende.querySelector('[data-kal-legende-frei]');
        if (freiEintrag) { freiEintrag.hidden = listenansicht; }
      }

      if (ansichten) {
        Array.prototype.forEach.call(ansichten.querySelectorAll('[data-kal-ansicht-zu]'), function (k) {
          k.setAttribute('aria-pressed', String(k.getAttribute('data-kal-ansicht-zu') === ansicht));
        });
      }
    }

    function springen(richtung) {
      if (ansicht === 'monat') {
        anker = new Date(anker.getFullYear(), anker.getMonth() + richtung, 1, 12, 0, 0);
      } else if (ansicht === 'halbjahr') {
        anker = new Date(anker.getFullYear(), anker.getMonth() + 6 * richtung, 1, 12, 0, 0);
      } else if (ansicht === 'jahr') {
        anker = new Date(anker.getFullYear() + richtung, 0, 1, 12, 0, 0);
      }
      zeichnen();
    }


    /* --- Bedienung ------------------------------------------------- */

    function binden() {
      if (ansichten) {
        ansichten.hidden = false;
        ansichten.addEventListener('click', function (e) {
          var knopf = e.target.closest ? e.target.closest('[data-kal-ansicht-zu]') : null;
          if (!knopf) { return; }
          ansicht = knopf.getAttribute('data-kal-ansicht-zu');
          zeichnen();
        });
      }

      if (steuer) {
        steuer.hidden = false;
        var zurueck = steuer.querySelector('[data-kal-zurueck]');
        var vor = steuer.querySelector('[data-kal-vor]');
        var jetzt = steuer.querySelector('[data-kal-heute]');
        if (zurueck) { zurueck.addEventListener('click', function () { springen(-1); }); }
        if (vor) { vor.addEventListener('click', function () { springen(1); }); }
        if (jetzt) {
          jetzt.addEventListener('click', function () {
            anker = new Date(heute.getTime());
            zeichnen();
          });
        }
      }

      /* Bei geänderter Fensterbreite passt womöglich eine andere Zahl
         Monate nebeneinander. Neu gezeichnet wird erst, wenn die
         Größenänderung zur Ruhe gekommen ist. */
      var messUhr;
      window.addEventListener('resize', function () {
        if (ansicht !== 'halbjahr' && ansicht !== 'jahr') { return; }
        window.clearTimeout(messUhr);
        messUhr = window.setTimeout(zeichnen, 180);
      });
    }


    /* --- Abonnieren ------------------------------------------------ */

    function aboBinden() {
      var knopf = wurzel.querySelector('[data-kal-abo]');
      var dialog = wurzel.querySelector('[data-kal-abofenster]');
      if (!knopf || !dialog || typeof dialog.showModal !== 'function') { return; }

      knopf.addEventListener('click', function (e) {
        e.preventDefault();
        dialog.showModal();
      });

      var zu = dialog.querySelector('[data-kal-abozu]');
      if (zu) { zu.addEventListener('click', function () { dialog.close(); }); }

      dialog.addEventListener('click', function (e) {
        if (e.target === dialog) { dialog.close(); }
      });

      var kopieren = dialog.querySelector('[data-kal-abokopieren]');
      var feld = dialog.querySelector('[data-kal-aboadresse]');
      var meldung = dialog.querySelector('[data-kal-abomeldung]');

      if (kopieren && feld) {
        kopieren.addEventListener('click', function () {
          function geschafft() {
            if (meldung) { meldung.textContent = 'Adresse kopiert.'; }
          }

          // Die Zwischenablage-Schnittstelle gibt es nur über HTTPS.
          // Auf http bleibt das Markieren, das tut es auch.
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(feld.value).then(geschafft, function () {
              feld.select();
              if (meldung) { meldung.textContent = 'Bitte mit Strg+C kopieren.'; }
            });
            return;
          }

          feld.select();
          try {
            document.execCommand('copy');
            geschafft();
          } catch (x) {
            if (meldung) { meldung.textContent = 'Bitte mit Strg+C kopieren.'; }
          }
        });
      }
    }


    aboBinden();

    laden().then(function () {
      // Die serverseitige Liste war die Ausgabe ohne JavaScript. Jetzt
      // übernimmt die gezeichnete Ansicht.
      if (ersatz) { ersatz.hidden = true; }
      binden();
      zeichnen();
    }).catch(function () {
      // Ging etwas schief, bleibt die serverseitige Liste stehen. Das
      // ist der Grund, warum sie überhaupt im HTML steht.
      if (ersatz) { ersatz.hidden = false; }
    });
  }


  function start() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-kal]'), kalender);
  }

  if (document.readyState !== 'loading') { start(); }
  else { document.addEventListener('DOMContentLoaded', start); }

  document.addEventListener('ccm-block-added', start);
})();
