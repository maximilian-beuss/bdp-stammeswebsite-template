/* ============================================================
   Jahresplanung
   ------------------------------------------------------------
   Links der Ideenspeicher, rechts das Jahr als zwölf
   Monatsspalten mit einer Zeile je Tag. Aus dem Speicher zieht
   man ein Vorhaben in einen Tag, im Jahr schiebt man es weiter.

   Warum eigenes Ziehen statt einer Bibliothek
   -------------------------------------------
   Gebraucht wird genau eines: „Nimm dieses Ding, lass es auf
   einem Tag los.“ Das kann der Browser seit Jahren selbst
   (draggable, dragover, drop). Eine Bibliothek dafür wären
   dreißig Kilobyte für drei Ereignisse.

   Ziehen allein genügt allerdings nicht: Mit Tastatur oder
   Sprachsteuerung zieht niemand. Deshalb führt jeder Klick auf
   einen Termin in ein Menü, in dem dasselbe über ein Datumsfeld
   geht ✅ WCAG 2.1.1 und 2.5.7.
   ============================================================ */
(function () {
  'use strict';

  var wurzel = document.querySelector('[data-plan]');
  if (!wurzel) { return; }

  var quelle = wurzel.querySelector('[data-plan-daten]');
  if (!quelle) { return; }

  var D = JSON.parse(quelle.textContent);

  var TAGE = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
  var MONATE = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

  var jahrRaster = wurzel.querySelector('[data-plan-jahr]');
  var ideenListe = wurzel.querySelector('[data-plan-ideen]');
  var ideenLeer = wurzel.querySelector('[data-plan-ideen-leer]');
  var menue = wurzel.querySelector('[data-plan-menue]');
  var meldung = wurzel.querySelector('[data-plan-meldung]');

  var offenerTermin = null;


  /* ----------------------------------------------------------
     Datum. Alles auf 12 Uhr, damit die Sommerzeitumstellung
     keine Tagesgrenze verschiebt.
     ---------------------------------------------------------- */

  function ausText(t) {
    var s = String(t).slice(0, 10).split('-');
    return new Date(+s[0], +s[1] - 1, +s[2], 12, 0, 0);
  }

  function zuText(d) {
    var m = d.getMonth() + 1;
    var t = d.getDate();
    return d.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (t < 10 ? '0' : '') + t;
  }

  function tageZwischen(a, b) {
    return Math.round((b.getTime() - a.getTime()) / 86400000);
  }

  function wochentag(d) {
    return (d.getDay() + 6) % 7;
  }

  function tageImMonat(jahr, monat) {
    return new Date(jahr, monat + 1, 0, 12, 0, 0).getDate();
  }

  function text(roh) {
    var d = document.createElement('div');
    d.textContent = roh === null || roh === undefined ? '' : String(roh);
    return d.innerHTML;
  }

  function farbe(handle) {
    return (D.stufen[handle] && D.stufen[handle].farbe) || '#0E4278';
  }

  function stufenName(handle) {
    return (D.stufen[handle] && D.stufen[handle].name) || '';
  }


  /* ----------------------------------------------------------
     Mit dem Server reden
     ---------------------------------------------------------- */

  function sagen(was, istFehler) {
    if (!meldung) { return; }
    meldung.textContent = was || '';
    meldung.classList.toggle('ist-fehler', !!istFehler);
  }

  function schicken(aktion, daten) {
    var koerper = new URLSearchParams();
    koerper.append('ccm_token', D.token);

    Object.keys(daten).forEach(function (k) {
      koerper.append(k, daten[k]);
    });

    return fetch(D.basis + '/' + aktion, {
      method: 'POST',
      body: koerper,
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (a) {
      return a.json().catch(function () {
        return { ok: false, meldung: 'Der Server hat unerwartet geantwortet.' };
      });
    });
  }


  /* ----------------------------------------------------------
     Ideenspeicher
     ---------------------------------------------------------- */

  function ideenZeichnen() {
    ideenListe.innerHTML = '';
    ideenLeer.hidden = D.ideen.length > 0;

    D.ideen.forEach(function (idee) {
      var li = document.createElement('li');
      li.className = 'v-plan__idee';
      li.draggable = true;
      li.style.setProperty('--stufe', farbe(idee.stufe));
      li.setAttribute('data-idee', idee.id);
      li.innerHTML =
        '<span class="v-plan__idee-titel">' + text(idee.titel) + '</span>'
        + '<span class="v-plan__idee-meta">' + idee.tage
        + (idee.tage === 1 ? ' Tag' : ' Tage') + ' · ' + text(stufenName(idee.stufe)) + '</span>'
        + '<button type="button" class="v-plan__idee-weg" aria-label="'
        + text('„' + idee.titel + '“ aus dem Speicher entfernen') + '">&times;</button>';

      li.addEventListener('dragstart', function (e) {
        e.dataTransfer.setData('text/plain', 'idee:' + idee.id);
        e.dataTransfer.effectAllowed = 'move';
        li.classList.add('wird-gezogen');
      });
      li.addEventListener('dragend', function () {
        li.classList.remove('wird-gezogen');
      });

      li.querySelector('.v-plan__idee-weg').addEventListener('click', function () {
        schicken('idee_loeschen', { ideeID: idee.id }).then(function (a) {
          if (!a.ok) { sagen(a.meldung, true); return; }
          D.ideen = D.ideen.filter(function (x) { return x.id !== idee.id; });
          ideenZeichnen();
        });
      });

      ideenListe.appendChild(li);
    });
  }

  wurzel.querySelector('[data-plan-neu]').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target;

    schicken('idee_anlegen', {
      titel: f.titel.value,
      tage: f.tage.value,
      stufe: f.stufe.value
    }).then(function (a) {
      if (!a.ok) { sagen(a.meldung, true); return; }

      D.ideen.push({
        id: a.ideeID,
        titel: f.titel.value,
        tage: parseInt(f.tage.value, 10) || 1,
        stufe: f.stufe.value,
        notiz: ''
      });

      f.titel.value = '';
      f.tage.value = 1;
      ideenZeichnen();
      sagen('');
      f.titel.focus();
    });
  });


  /* ----------------------------------------------------------
     Das Jahr
     ----------------------------------------------------------
     Aufbau wie im Kalender der Website: je Monat eine Spalte,
     je Tag eine Zeile, Termine als Kästen über mehrere Zeilen.
     Nebeneinanderliegende Termine bekommen eigene Spuren.
     ---------------------------------------------------------- */

  function freiAmTag(tag) {
    for (var i = 0; i < D.frei.length; i++) {
      if (ausText(D.frei[i].von) <= tag && ausText(D.frei[i].bis) >= tag) {
        return D.frei[i];
      }
    }
    return wochentag(tag) >= 5 ? { titel: 'Wochenende', art: 'wochenende' } : null;
  }

  function jahrZeichnen() {
    jahrRaster.innerHTML = '';

    var heute = new Date();
    heute.setHours(12, 0, 0, 0);

    for (var m = 0; m < 12; m++) {
      jahrRaster.appendChild(monatZeichnen(D.jahr, m, heute));
    }
  }

  function monatZeichnen(jahr, monat, heute) {
    var spalte = document.createElement('div');
    spalte.className = 'v-plan__monat';

    var titel = document.createElement('h4');
    titel.className = 'v-plan__monat-titel';
    titel.textContent = MONATE[monat];
    spalte.appendChild(titel);

    var anzahl = tageImMonat(jahr, monat);
    var erster = new Date(jahr, monat, 1, 12, 0, 0);
    var letzter = new Date(jahr, monat, anzahl, 12, 0, 0);

    var raster = document.createElement('div');
    raster.className = 'v-plan__tage';
    raster.style.setProperty('--tage', anzahl);

    /* --- Tageszeilen. Sie sind zugleich die Ablegeflächen. --- */
    for (var d = 1; d <= anzahl; d++) {
      var tag = new Date(jahr, monat, d, 12, 0, 0);
      var zeile = document.createElement('div');
      zeile.className = 'v-plan__tag';
      zeile.style.gridRow = String(d);
      zeile.setAttribute('data-tag', zuText(tag));

      var f = freiAmTag(tag);
      if (f) { zeile.classList.add('v-plan__tag--' + f.art); zeile.title = f.titel; }
      if (tag.getTime() === heute.getTime()) { zeile.classList.add('v-plan__tag--heute'); }

      zeile.innerHTML = '<span class="v-plan__tag-nr">' + d + '</span>'
        + '<span class="v-plan__tag-wt">' + TAGE[wochentag(tag)] + '</span>';

      ablegeflaeche(zeile);
      raster.appendChild(zeile);
    }

    /* --- Terminkästen mit Spurenzuteilung --- */
    var stuecke = D.termine.filter(function (t) {
      return ausText(t.von) <= letzter && ausText(t.bis) >= erster;
    }).map(function (t) {
      var von = ausText(t.von);
      var bis = ausText(t.bis);
      return {
        termin: t,
        von: von < erster ? 1 : von.getDate(),
        bis: bis > letzter ? anzahl : bis.getDate(),
        vorher: von < erster,
        weiter: bis > letzter
      };
    }).sort(function (a, b) {
      return a.von - b.von || (b.bis - b.von) - (a.bis - a.von);
    });

    var spuren = [];

    stuecke.forEach(function (s) {
      var spur = 0;
      while (spur < spuren.length && spuren[spur] >= s.von) { ++spur; }
      spuren[spur] = s.bis;
      s.spur = spur;
    });

    var gesamt = Math.max(1, spuren.length);
    raster.style.setProperty('--spuren', gesamt);

    /* Ein einzelner Parallelfall im April hat bisher den ganzen
       April zweispaltig gemacht: Jeder Kasten stand in seiner Spur
       und ließ die andere leer. Deshalb dehnt sich jeder Kasten
       jetzt über die Nachbarspuren aus, solange dort an seinen
       eigenen Tagen nichts liegt. Schmal wird nur, was sich
       tatsächlich einen Tag teilt. */
    stuecke.forEach(function (s) {
      var breite = 1;
      while (s.spur + breite < gesamt && spurFrei(stuecke, s, s.spur + breite)) {
        ++breite;
      }
      s.breite = breite;
    });

    stuecke.forEach(function (s) {
      raster.appendChild(kastenZeichnen(s));
    });

    spalte.appendChild(raster);
    return spalte;
  }

  /* Liegt an den Tagen von s etwas in dieser Spur? Geprüft wird
     gegen die zugeteilten Spuren, nicht gegen die verbreiterten —
     verbreitert wird immer nur nach rechts, ein Kasten kann also
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

  function kastenZeichnen(s) {
    var t = s.termin;

    var kasten = document.createElement('button');
    kasten.type = 'button';
    kasten.className = 'v-plan__kasten'
      + (t.entwurf ? ' v-plan__kasten--entwurf' : '')
      + (s.vorher ? ' v-plan__kasten--vorher' : '')
      + (s.weiter ? ' v-plan__kasten--weiter' : '');
    kasten.style.setProperty('--stufe', farbe(t.stufe));
    kasten.style.gridRow = s.von + ' / ' + (s.bis + 1);
    kasten.style.gridColumn = (s.spur + 2) + ' / span ' + (s.breite || 1);
    kasten.draggable = true;
    kasten.setAttribute('data-termin', t.id);

    kasten.innerHTML = '<span class="v-plan__kasten-text">' + text(t.titel) + '</span>';

    var dauer = tageZwischen(ausText(t.von), ausText(t.bis)) + 1;
    kasten.setAttribute('aria-label', t.titel + ', ' + stufenName(t.stufe)
      + ', ' + dauer + (dauer === 1 ? ' Tag' : ' Tage')
      + ' ab ' + ausText(t.von).getDate() + '. ' + MONATE[ausText(t.von).getMonth()]
      + (t.entwurf ? ', Entwurf' : '') + '. Zum Bearbeiten öffnen.');

    kasten.addEventListener('dragstart', function (e) {
      e.dataTransfer.setData('text/plain', 'termin:' + t.id);
      e.dataTransfer.effectAllowed = 'move';
      kasten.classList.add('wird-gezogen');
    });
    kasten.addEventListener('dragend', function () {
      kasten.classList.remove('wird-gezogen');
    });

    kasten.addEventListener('click', function () { menueOeffnen(t); });

    return kasten;
  }


  /* ----------------------------------------------------------
     Ablegen
     ---------------------------------------------------------- */

  function ablegeflaeche(zeile) {
    zeile.addEventListener('dragover', function (e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      zeile.classList.add('ist-ziel');
    });

    zeile.addEventListener('dragleave', function () {
      zeile.classList.remove('ist-ziel');
    });

    zeile.addEventListener('drop', function (e) {
      e.preventDefault();
      zeile.classList.remove('ist-ziel');

      var last = e.dataTransfer.getData('text/plain') || '';
      var tag = zeile.getAttribute('data-tag');

      if (last.indexOf('idee:') === 0) {
        ideeAblegen(parseInt(last.slice(5), 10), tag);
      } else if (last.indexOf('termin:') === 0) {
        terminVerschieben(parseInt(last.slice(7), 10), tag);
      }
    });
  }

  function ideeAblegen(ideeID, tag) {
    var idee = D.ideen.filter(function (i) { return i.id === ideeID; })[0];
    if (!idee) { return; }

    schicken('idee_ablegen', { ideeID: ideeID, tag: tag }).then(function (a) {
      if (!a.ok) { sagen(a.meldung, true); return; }

      var bis = new Date(ausText(tag).getTime());
      bis.setDate(bis.getDate() + idee.tage - 1);

      D.termine.push({
        id: a.terminID,
        titel: idee.titel,
        von: tag,
        bis: zuText(bis),
        stufe: idee.stufe,
        entwurf: true,
        ort: '',
        beschreibung: idee.notiz
      });

      D.ideen = D.ideen.filter(function (i) { return i.id !== ideeID; });

      ideenZeichnen();
      jahrZeichnen();
      sagen('„' + idee.titel + '“ liegt jetzt im Jahr.');
    });
  }

  function terminVerschieben(terminID, tag) {
    var t = D.termine.filter(function (x) { return x.id === terminID; })[0];
    if (!t) { return; }

    schicken('verschieben', { terminID: terminID, tag: tag }).then(function (a) {
      if (!a.ok) { sagen(a.meldung, true); return; }

      var dauer = tageZwischen(ausText(t.von), ausText(t.bis));
      var bis = new Date(ausText(tag).getTime());
      bis.setDate(bis.getDate() + dauer);

      t.von = tag;
      t.bis = zuText(bis);

      jahrZeichnen();
      sagen('„' + t.titel + '“ verschoben.');
    });
  }


  /* ----------------------------------------------------------
     Menü zu einem Termin
     ---------------------------------------------------------- */

  function menueOeffnen(t) {
    if (typeof menue.showModal !== 'function') { return; }

    offenerTermin = t;
    sagen('');

    var von = ausText(t.von);
    var dauer = tageZwischen(von, ausText(t.bis)) + 1;

    menue.querySelector('[data-plan-menue-titel]').textContent = t.titel;
    menue.querySelector('[data-plan-menue-wann]').textContent =
      von.getDate() + '. ' + MONATE[von.getMonth()] + ' ' + von.getFullYear()
      + ' · ' + dauer + (dauer === 1 ? ' Tag' : ' Tage')
      + ' · ' + stufenName(t.stufe)
      + (t.entwurf ? ' · Entwurf' : ' · veröffentlicht');

    menue.querySelector('[data-plan-datum]').value = t.von;
    menue.querySelector('[data-plan-tage]').value = dauer;

    menue.querySelector('[data-plan-veroeffentlichen]').textContent =
      t.entwurf ? 'Veröffentlichen' : 'Zurück auf Entwurf';

    menue.querySelector('[data-plan-bearbeiten]')
      .setAttribute('href', D.basis.replace('/planung', '/termine') + '/bearbeiten/' + t.id);

    menue.showModal();
  }

  function menueSchliessen() {
    menue.close();
    offenerTermin = null;
  }

  menue.querySelector('[data-plan-menue-zu]').addEventListener('click', menueSchliessen);
  menue.addEventListener('click', function (e) {
    if (e.target === menue) { menueSchliessen(); }
  });

  menue.querySelector('[data-plan-verschieben]').addEventListener('click', function () {
    if (!offenerTermin) { return; }
    var tag = menue.querySelector('[data-plan-datum]').value;
    if (!/^\d{4}-\d{2}-\d{2}$/.test(tag)) { sagen('Bitte ein Datum wählen.', true); return; }
    terminVerschieben(offenerTermin.id, tag);
    menueSchliessen();
  });

  menue.querySelector('[data-plan-dauer]').addEventListener('click', function () {
    if (!offenerTermin) { return; }
    var t = offenerTermin;
    var tage = parseInt(menue.querySelector('[data-plan-tage]').value, 10) || 1;

    schicken('dauer_aendern', { terminID: t.id, tage: tage }).then(function (a) {
      if (!a.ok) { sagen(a.meldung, true); return; }

      var bis = new Date(ausText(t.von).getTime());
      bis.setDate(bis.getDate() + tage - 1);
      t.bis = zuText(bis);

      jahrZeichnen();
      menueSchliessen();
      sagen('Dauer geändert.');
    });
  });

  menue.querySelector('[data-plan-veroeffentlichen]').addEventListener('click', function () {
    if (!offenerTermin) { return; }
    var t = offenerTermin;

    schicken('veroeffentlichen', { terminID: t.id, entwurf: t.entwurf ? 1 : 0 }).then(function (a) {
      if (!a.ok) { sagen(a.meldung, true); return; }
      t.entwurf = !t.entwurf;
      jahrZeichnen();
      menueSchliessen();
      sagen(t.entwurf ? 'Wieder Entwurf.' : 'Veröffentlicht.');
    });
  });

  menue.querySelector('[data-plan-zurueck]').addEventListener('click', function () {
    if (!offenerTermin) { return; }
    var t = offenerTermin;

    schicken('zurueck_in_speicher', { terminID: t.id }).then(function (a) {
      if (!a.ok) { sagen(a.meldung, true); return; }

      var dauer = tageZwischen(ausText(t.von), ausText(t.bis)) + 1;

      D.termine = D.termine.filter(function (x) { return x.id !== t.id; });
      D.ideen.push({ id: -Date.now(), titel: t.titel, tage: dauer, stufe: t.stufe, notiz: t.beschreibung });

      ideenZeichnen();
      jahrZeichnen();
      menueSchliessen();
      // Die neue Idee hat serverseitig eine andere Nummer bekommen.
      // Ein Neuladen holt sie; bis dahin ist die Anzeige richtig, nur
      // das Löschen dieser einen Zeile ginge daneben.
      sagen('„' + t.titel + '“ liegt wieder im Speicher. Zum Weiterarbeiten die Seite neu laden.');
    });
  });

  menue.querySelector('[data-plan-loeschen]').addEventListener('click', function () {
    if (!offenerTermin) { return; }
    var t = offenerTermin;

    if (!window.confirm('„' + t.titel + '“ wirklich löschen?')) { return; }

    schicken('termin_loeschen', { terminID: t.id }).then(function (a) {
      if (!a.ok) { sagen(a.meldung, true); return; }
      D.termine = D.termine.filter(function (x) { return x.id !== t.id; });
      jahrZeichnen();
      menueSchliessen();
      sagen('Gelöscht.');
    });
  });


  /* ----------------------------------------------------------
     Hinweise
     ----------------------------------------------------------
     <details> kann alles außer zugehen, wenn man woanders
     hinklickt. Die Karte liegt über den Monatsspalten, also muss
     sie aus dem Weg, sobald man dort weiterarbeitet.
     ---------------------------------------------------------- */

  var hilfe = wurzel.querySelector('.v-plan__hilfe');

  if (hilfe) {
    document.addEventListener('click', function (e) {
      if (hilfe.open && !hilfe.contains(e.target)) { hilfe.open = false; }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && hilfe.open) {
        hilfe.open = false;
        hilfe.querySelector('summary').focus();

        // Escape schließt immer nur eine Ebene. Ohne das hier
        // fiele man aus der Hilfe und dem Vollbild zugleich.
        e.stopImmediatePropagation();
      }
    });
  }


  /* ----------------------------------------------------------
     Zeilenhöhe
     ----------------------------------------------------------
     Der Regler schreibt in --p-zeile, das Raster hängt daran. Die
     Einstellung bleibt lokal gespeichert: Wer eng plant, plant
     meist auch morgen eng, und niemand will das jedes Mal neu
     einstellen. localStorage und nicht der Server, weil es an
     diesem Bildschirm hängt und nicht am Konto.
     ---------------------------------------------------------- */

  var zeileRegler = wurzel.querySelector('[data-plan-zeile]');

  if (zeileRegler) {
    var zeileWert = wurzel.querySelector('[data-plan-zeile-wert]');
    var ZEILE_SCHLUESSEL = 'bdp-plan-zeile';

    var zeileSetzen = function (rem, merken) {
      rem = Math.min(2, Math.max(0.4, parseFloat(rem) || 1.35));

      wurzel.style.setProperty('--p-zeile', rem + 'rem');
      // Unter etwa 15px bleibt für Zahl und Kürzel nebeneinander
      // kein Platz mehr.
      wurzel.classList.toggle('ist-eng', rem < 0.95);
      zeileWert.textContent = rem.toFixed(2).replace('.', ',') + ' rem';

      if (merken) {
        try { window.localStorage.setItem(ZEILE_SCHLUESSEL, String(rem)); } catch (e) { /* privates Fenster */ }
      }
    };

    var gemerkt = null;
    try { gemerkt = window.localStorage.getItem(ZEILE_SCHLUESSEL); } catch (e) { /* privates Fenster */ }

    if (gemerkt) { zeileRegler.value = gemerkt; }

    zeileSetzen(zeileRegler.value, false);

    zeileRegler.addEventListener('input', function () {
      zeileSetzen(zeileRegler.value, true);
    });
  }


  /* ----------------------------------------------------------
     Vollbild
     ----------------------------------------------------------
     Zwei Mechanismen, weil jeder für sich lückenhaft wäre: Die
     Klasse legt den Planer über das Dashboard, die Fullscreen-API
     nimmt zusätzlich die Leisten des Browsers weg. Schlägt die API
     fehl — im iframe, ohne Nutzergeste, in älteren Safaris —,
     bleibt die Klasse, und der Planer füllt wenigstens das Fenster.
     Deshalb hängt der Zustand an der Klasse und nicht an
     document.fullscreenElement.
     ---------------------------------------------------------- */

  var vollbildKnopf = wurzel.querySelector('[data-plan-vollbild]');

  if (vollbildKnopf) {
    var vollbildText = vollbildKnopf.querySelector('[data-plan-vollbild-text]');

    /* Der Planer wandert für die Dauer des Vollbilds an den body.
       Das Dashboard steckt ihn in mehrere Behälter, und schon ein
       einziges transform oder overflow: hidden darüber macht aus
       position: fixed etwas, das doch wieder mitscrollt und am
       Rand abgeschnitten wird. Ein Platzhalter merkt sich die
       Stelle, damit er beim Beenden dorthin zurückkommt. */
    var platzhalter = document.createComment('planer');

    var vollbildSetzen = function (an) {
      if (an && !wurzel.classList.contains('ist-vollbild')) {
        wurzel.parentNode.insertBefore(platzhalter, wurzel);
        document.body.appendChild(wurzel);
      } else if (!an && platzhalter.parentNode) {
        platzhalter.parentNode.insertBefore(wurzel, platzhalter);
        platzhalter.parentNode.removeChild(platzhalter);
      }

      wurzel.classList.toggle('ist-vollbild', an);
      vollbildKnopf.setAttribute('aria-pressed', an ? 'true' : 'false');
      vollbildText.textContent = an ? 'Vollbild beenden' : 'Vollbild';
    };

    vollbildKnopf.addEventListener('click', function () {
      var an = !wurzel.classList.contains('ist-vollbild');

      vollbildSetzen(an);

      if (an && wurzel.requestFullscreen) {
        // Der Fehlerfall braucht keine Meldung: Es sieht dann aus
        // wie ein Vollbild im Fenster, und genau das ist es auch.
        wurzel.requestFullscreen().catch(function () {});
      } else if (!an && document.fullscreenElement) {
        document.exitFullscreen().catch(function () {});
      }

      // Die Monatsspalten hängen an der Breite des Rasters; ein
      // resize stößt dieselbe Neuberechnung an wie das Ziehen am
      // Fensterrand.
      window.dispatchEvent(new Event('resize'));
    });

    /* Escape verlässt das Vollbild am Browser vorbei — ohne das
       hier bliebe der Planer als Überlagerung stehen. */
    document.addEventListener('fullscreenchange', function () {
      if (!document.fullscreenElement && wurzel.classList.contains('ist-vollbild')) {
        vollbildSetzen(false);
      }
    });

    /* Ohne API gibt es kein fullscreenchange, Escape muss dann
       selbst abgefangen werden. Nicht, wenn gerade das Menü offen
       ist — dort schließt Escape zuerst den Dialog. */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !menue.open && !document.fullscreenElement
        && wurzel.classList.contains('ist-vollbild')) {
        vollbildSetzen(false);
      }
    });
  }


  ideenZeichnen();
  jahrZeichnen();
})();
