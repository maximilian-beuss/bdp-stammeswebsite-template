/* ============================================================
   Aufnahmeantrag und Galerie-Großansicht
   ------------------------------------------------------------
   Beide Bausteine kommen aus dem einfachBdP-Paket. Die Datei tut
   nichts, wenn auf der Seite weder [data-antrag] noch
   [data-galerie-lupe] vorkommt.
   ============================================================ */
(function () {
  'use strict';

  var wenigerBewegung = window.matchMedia('(prefers-reduced-motion: reduce)');

  function bereit(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }


  /* ==========================================================
     Konfetti
     ----------------------------------------------------------
     Ein Overlay über der ganzen Seite, nicht ein Streifen im
     Block. Die Schnipsel kommen von drei Seiten: von oben fallen
     sie herunter, aus den beiden unteren Ecken werden sie nach
     innen und oben geschossen. Jeder bekommt eigene Richtung,
     Drehung, Größe, Farbe, Dauer und Verzögerung.

     Das Overlay wird nach der längsten Flugzeit wieder aus dem
     Dokument entfernt — es soll nicht über der Seite liegen
     bleiben, wenn nichts mehr fliegt.

     Bei „Bewegung reduzieren“ entsteht es gar nicht erst
     ✅ WCAG 2.3.3
     ---------------------------------------------------------- */

  var konfettiLaeuft = null;

  function zufall(von, bis) {
    return von + Math.random() * (bis - von);
  }

  function konfettiWerfen() {
    if (wenigerBewegung.matches) { return; }

    // Läuft gerade eins, wird es ersetzt statt gestapelt. Sonst
    // sammelten sich beim Hin- und Herblättern mehrere Overlays.
    if (konfettiLaeuft) {
      window.clearTimeout(konfettiLaeuft.uhr);
      if (konfettiLaeuft.el.parentNode) {
        konfettiLaeuft.el.parentNode.removeChild(konfettiLaeuft.el);
      }
      konfettiLaeuft = null;
    }

    var breite = window.innerWidth;
    var hoehe = window.innerHeight;

    // Auf kleinen Schirmen weniger Schnipsel: gleiche Dichte,
    // weniger Arbeit für schwächere Geräte.
    var anzahl = Math.max(28, Math.min(90, Math.round(breite / 16)));

    var farben = ['#FFD208', '#0E4278', '#971B2F', '#00B74F', '#FFFFFF'];

    var overlay = document.createElement('div');
    overlay.className = 'v-konfetti';
    overlay.setAttribute('aria-hidden', 'true');

    var laengste = 0;

    for (var i = 0; i < anzahl; i++) {
      // Drei Abschussstellen. Die Anteile sind Geschmackssache:
      // etwa die Hälfte regnet von oben, der Rest kommt aus den
      // Ecken — das sieht nach Knallbonbon aus und nicht nach
      // Schneefall.
      var wurf = Math.random();
      var startX, startY, wegX, wegY, gipfel;

      if (wurf < 0.5) {
        // von oben herab
        startX = zufall(0, breite);
        startY = zufall(-80, -10);
        wegX = zufall(-140, 140);
        wegY = hoehe + zufall(60, 220);
        gipfel = 0;
      } else if (wurf < 0.75) {
        // aus der linken unteren Ecke nach rechts oben
        startX = zufall(-40, breite * 0.12);
        startY = hoehe + zufall(10, 60);
        wegX = zufall(breite * 0.3, breite * 0.95);
        wegY = zufall(60, 260);
        gipfel = -zufall(hoehe * 0.55, hoehe * 0.95);
      } else {
        // aus der rechten unteren Ecke nach links oben
        startX = zufall(breite * 0.88, breite + 40);
        startY = hoehe + zufall(10, 60);
        wegX = -zufall(breite * 0.3, breite * 0.95);
        wegY = zufall(60, 260);
        gipfel = -zufall(hoehe * 0.55, hoehe * 0.95);
      }

      var dauer = zufall(1.8, 3.4);
      var verzug = zufall(0, 0.7);
      laengste = Math.max(laengste, dauer + verzug);

      var aussen = document.createElement('i');
      var innen = document.createElement('span');

      aussen.style.setProperty('--start-x', Math.round(startX) + 'px');
      aussen.style.setProperty('--start-y', Math.round(startY) + 'px');
      aussen.style.setProperty('--weg-x', Math.round(wegX) + 'px');
      aussen.style.setProperty('--weg-y', Math.round(wegY) + 'px');
      aussen.style.setProperty('--gipfel', Math.round(gipfel) + 'px');
      aussen.style.setProperty('--dauer', dauer.toFixed(2) + 's');
      aussen.style.setProperty('--verzug', verzug.toFixed(2) + 's');
      // Drehrichtung und -menge frei: mal ein halber Purzelbaum,
      // mal fünf, mal andersherum.
      aussen.style.setProperty('--dreh', Math.round(zufall(-1080, 1080)) + 'deg');

      var w = Math.round(zufall(5, 11));
      innen.style.setProperty('--breite', w + 'px');
      innen.style.setProperty('--hoehe', Math.round(w * zufall(1.1, 2.2)) + 'px');
      innen.style.setProperty('--farbe', farben[Math.floor(Math.random() * farben.length)]);
      // Ein Viertel rund, der Rest eckig.
      innen.style.setProperty('--rundung', Math.random() < 0.25 ? '50%' : '1px');

      aussen.appendChild(innen);
      overlay.appendChild(aussen);
    }

    document.body.appendChild(overlay);

    var uhr = window.setTimeout(function () {
      if (overlay.parentNode) { overlay.parentNode.removeChild(overlay); }
      konfettiLaeuft = null;
    }, (laengste + 0.4) * 1000);

    konfettiLaeuft = { el: overlay, uhr: uhr };
  }


  /* ==========================================================
     Aufnahmeantrag
     ---------------------------------------------------------- */

  function antrag(wurzel) {
    var kasten = wurzel || document.querySelector('[data-antrag]');
    if (!kasten || kasten.hasAttribute('data-antrag-laeuft')) { return; }
    kasten.setAttribute('data-antrag-laeuft', '1');

    var schritte = Array.prototype.slice.call(kasten.querySelectorAll('[data-antrag-schritt]'));
    if (schritte.length === 0) { return; }

    var leiste = kasten.querySelector('[data-antrag-leiste]');
    var marken = Array.prototype.slice.call(kasten.querySelectorAll('[data-antrag-zu]'));
    var stand = kasten.querySelector('[data-antrag-stand]');
    var frage = kasten.querySelector('[data-antrag-frage]');
    var schluessel = kasten.getAttribute('data-antrag-schluessel') || 'bdp-antrag';
    var merken = kasten.getAttribute('data-antrag-merken') === '1';

    var aktuell = 0;
    var weiteste = 0;


    /* --- Gedächtnis ------------------------------------------------
       localStorage kann fehlen (privater Modus, abgeschaltet) oder
       beim Schreiben werfen. Beides darf den Antrag nicht anhalten,
       deshalb steht jeder Zugriff in einem try. */

    function gelesen() {
      if (!merken) { return null; }
      try {
        var roh = window.localStorage.getItem(schluessel);
        return roh === null ? null : JSON.parse(roh);
      } catch (e) { return null; }
    }

    function geschrieben() {
      if (!merken) { return; }
      try {
        window.localStorage.setItem(schluessel, JSON.stringify({
          bei: aktuell,
          weiteste: weiteste,
          zeit: Date.now()
        }));
      } catch (e) { /* kein Speicher, kein Beinbruch */ }
    }


    /* --- Anzeigen ------------------------------------------------- */

    function zeigen(i, fokussieren) {
      i = Math.max(0, Math.min(schritte.length - 1, i));
      aktuell = i;
      weiteste = Math.max(weiteste, i);

      schritte.forEach(function (s, n) {
        s.classList.toggle('ist-versteckt', n !== i);
      });

      marken.forEach(function (m, n) {
        m.setAttribute('aria-current', n === i ? 'step' : 'false');
        m.classList.toggle('ist-erledigt', n < weiteste && n !== i);
      });

      if (stand) {
        stand.textContent = 'Schritt ' + (i + 1) + ' von ' + schritte.length;
      }

      // Die Blätterknöpfe hängen am Schritt, nicht am Block: der erste
      // hat kein Zurück, der letzte kein Weiter.
      schritte.forEach(function (s, n) {
        var leiste2 = s.querySelector('[data-antrag-blaettern]');
        if (!leiste2) { return; }
        leiste2.hidden = false;
        var zurueck = leiste2.querySelector('[data-antrag-zurueck]');
        var weiter = leiste2.querySelector('[data-antrag-weiter]');
        if (zurueck) { zurueck.hidden = n === 0; }
        if (weiter) { weiter.hidden = n === schritte.length - 1; }
      });

      konfettiPruefen(schritte[i]);
      geschrieben();

      if (fokussieren) {
        var titel = schritte[i].querySelector('.v-antrag__titel');
        if (titel) {
          // tabindex -1 statt eines Links: der Titel soll den Fokus
          // annehmen können, aber nicht in der Tabreihenfolge stehen.
          titel.setAttribute('tabindex', '-1');
          titel.focus({ preventScroll: true });
        }
        var oben = kasten.getBoundingClientRect().top + window.pageYOffset - 90;
        window.scrollTo({
          top: oben,
          behavior: wenigerBewegung.matches ? 'auto' : 'smooth'
        });
      }
    }

    function konfettiPruefen(schritt) {
      if (!schritt.hasAttribute('data-antrag-fertig')) { return; }
      konfettiWerfen();
    }


    /* --- Der Weg nach draußen --------------------------------------
       Der Link bleibt ein echter Link mit target="_blank". Wir fangen
       den Klick nur ab, um vorher zu erklären, dass es weitergeht.
       Fällt das Skript aus, führt der Link trotzdem zum Ziel. */

    function hinausBinden() {
      kasten.addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest('[data-antrag-hinaus]') : null;
        if (!link || !frage || typeof frage.showModal !== 'function') { return; }

        e.preventDefault();

        var wohin = frage.querySelector('[data-antrag-frage-wohin]');
        if (wohin) {
          var naechster = parseInt(link.getAttribute('data-antrag-naechster'), 10);
          var host = '';
          try { host = new URL(link.href, location.href).hostname; } catch (err) { host = link.href; }
          wohin.textContent = 'Es geht zu ' + host
            + (naechster > 0 ? '. Danach wartet hier Schritt ' + naechster + ' auf dich.' : '.');
        }

        var los = frage.querySelector('[data-antrag-frage-los]');
        var ab = frage.querySelector('[data-antrag-frage-ab]');

        function aufraeumen() {
          los.removeEventListener('click', jetzt);
          ab.removeEventListener('click', abbrechen);
        }

        function jetzt() {
          aufraeumen();
          frage.close();
          window.open(link.href, '_blank', 'noopener');
          // Direkt einen Schritt weiter: Wer zurückkommt, steht dann
          // schon an der richtigen Stelle.
          var naechster = parseInt(link.getAttribute('data-antrag-naechster'), 10);
          if (naechster > 0) { zeigen(naechster - 1, false); }
        }

        function abbrechen() {
          aufraeumen();
          frage.close();
        }

        los.addEventListener('click', jetzt);
        ab.addEventListener('click', abbrechen);
        frage.showModal();
      });
    }


    /* --- Bedienung ------------------------------------------------- */

    marken.forEach(function (m) {
      m.addEventListener('click', function () {
        zeigen(parseInt(m.getAttribute('data-antrag-zu'), 10), true);
      });
    });

    kasten.addEventListener('click', function (e) {
      if (!e.target.closest) { return; }
      if (e.target.closest('[data-antrag-weiter]')) { zeigen(aktuell + 1, true); }
      if (e.target.closest('[data-antrag-zurueck]')) { zeigen(aktuell - 1, true); }
    });

    hinausBinden();

    if (leiste) { leiste.hidden = false; }
    if (stand) { stand.hidden = false; }

    // Wiederkommer landen dort, wo sie waren — aber nur, wenn der
    // Besuch nicht ewig her ist. Nach zwei Wochen ist die Erinnerung
    // eher irritierend als hilfreich.
    var gemerkt = gelesen();
    var start = 0;
    if (gemerkt && typeof gemerkt.bei === 'number'
        && Date.now() - (gemerkt.zeit || 0) < 14 * 24 * 3600 * 1000) {
      weiteste = Math.max(0, Math.min(schritte.length - 1, gemerkt.weiteste || 0));
      start = Math.max(0, Math.min(schritte.length - 1, gemerkt.bei));
    }

    zeigen(start, false);
  }


  /* ==========================================================
     Galerie: Großansicht
     ----------------------------------------------------------
     Ein <dialog> mit dem Bild darin. Escape, Fokusfalle und die
     Inertheit des Hintergrunds bringt der Browser mit; nachgebaut
     wird nur das Blättern mit den Pfeiltasten.
     ---------------------------------------------------------- */

  var lupe = null;
  var lupeBild = null;
  var lupeText = null;
  var lupeListe = [];
  var lupeBei = 0;

  function lupeBauen() {
    if (lupe) { return lupe; }

    lupe = document.createElement('dialog');
    lupe.className = 'v-lupe';
    lupe.innerHTML =
      '<button type="button" class="v-lupe__zu" data-lupe-zu aria-label="Schließen">&times;</button>' +
      '<button type="button" class="v-lupe__blaettern v-lupe__blaettern--zurueck" data-lupe-zurueck aria-label="Vorheriges Bild">&lsaquo;</button>' +
      '<button type="button" class="v-lupe__blaettern v-lupe__blaettern--vor" data-lupe-vor aria-label="Nächstes Bild">&rsaquo;</button>' +
      '<img class="v-lupe__bild" alt="">' +
      '<p class="v-lupe__text"></p>';

    document.body.appendChild(lupe);
    lupeBild = lupe.querySelector('.v-lupe__bild');
    lupeText = lupe.querySelector('.v-lupe__text');

    lupe.querySelector('[data-lupe-zu]').addEventListener('click', function () { lupe.close(); });
    lupe.querySelector('[data-lupe-vor]').addEventListener('click', function () { lupeZeigen(lupeBei + 1); });
    lupe.querySelector('[data-lupe-zurueck]').addEventListener('click', function () { lupeZeigen(lupeBei - 1); });

    lupe.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); lupeZeigen(lupeBei + 1); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); lupeZeigen(lupeBei - 1); }
    });

    // Klick auf den Hintergrund schließt. Der Test auf das Ziel
    // unterscheidet Hintergrund von Bild — ein Klick aufs Bild soll
    // nichts tun.
    lupe.addEventListener('click', function (e) {
      if (e.target === lupe) { lupe.close(); }
    });

    return lupe;
  }

  function lupeZeigen(i) {
    if (lupeListe.length === 0) { return; }
    lupeBei = (i + lupeListe.length) % lupeListe.length;

    var link = lupeListe[lupeBei];
    var bild = link.querySelector('img');

    lupeBild.src = link.getAttribute('href');
    lupeBild.alt = bild ? bild.getAttribute('alt') || '' : '';
    lupeText.textContent = link.getAttribute('data-titel') || '';

    var mehrere = lupeListe.length > 1;
    lupe.querySelector('[data-lupe-vor]').hidden = !mehrere;
    lupe.querySelector('[data-lupe-zurueck]').hidden = !mehrere;
  }

  /* ----------------------------------------------------------
     Galerie: Info-Kästchen
     ------------------------------------------------------------
     Aus Schalter und figcaption wird ein aufklappendes Kästchen.

     Die Funktion lebt hier und nicht im Theme: Gebraucht wird sie
     nur in der Galerie. Auf einer Textseite waren vier Bilder mit
     vier aufklappenden Schaltern schlicht im Weg.

     Ohne dieses Skript bleibt die Bildunterschrift eine ganz normale
     Bildunterschrift unter dem Bild — die Klasse .ist-bereit, an der
     das ganze Kästchen-Aussehen hängt, setzt nur diese Funktion.
     ---------------------------------------------------------- */

  function galerieInfos(wurzel) {
    var figuren = (wurzel || document).querySelectorAll('[data-galerie-infos] [data-galerie-figur]');

    Array.prototype.forEach.call(figuren, function (figur) {
      if (figur.classList.contains('ist-bereit')) { return; }

      var schalter = figur.querySelector('[data-galerie-info]');
      var kasten = figur.querySelector('[data-galerie-text]');
      if (!schalter || !kasten) { return; }

      var beschriftung = schalter.querySelector('.nur-sr');

      function umschalten(offen) {
        schalter.setAttribute('aria-expanded', String(offen));
        kasten.setAttribute('data-offen', String(offen));
        if (beschriftung) {
          beschriftung.textContent = offen
            ? 'Bildinformationen ausblenden'
            : 'Bildinformationen anzeigen';
        }
      }

      schalter.addEventListener('click', function (e) {
        // Der Schalter liegt über dem Bild; ohne das öffnete der Klick
        // zusätzlich die Großansicht.
        e.preventDefault();
        e.stopPropagation();
        umschalten(schalter.getAttribute('aria-expanded') !== 'true');
      });

      figur.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && schalter.getAttribute('aria-expanded') === 'true') {
          umschalten(false);
          schalter.focus();
        }
      });

      // Auf Zeigegeräten reicht Überfahren, ohne den Zustand zu erzwingen
      if (window.matchMedia('(hover: hover)').matches) {
        figur.addEventListener('mouseenter', function () { umschalten(true); });
        figur.addEventListener('mouseleave', function () {
          if (document.activeElement !== schalter) { umschalten(false); }
        });
      }

      figur.classList.add('ist-bereit');
    });
  }


  function galerie() {
    galerieInfos();

    document.addEventListener('click', function (e) {
      var link = e.target.closest ? e.target.closest('[data-galerie-lupe]') : null;
      if (!link) { return; }

      // Modifiziertes Klicken gehört dem Browser: Strg-Klick soll das
      // Bild in einem neuen Tab öffnen, nicht die Großansicht.
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) { return; }

      var behaelter = link.closest('.v-galerie') || document;
      lupeListe = Array.prototype.slice.call(behaelter.querySelectorAll('[data-galerie-lupe]'));

      var d = lupeBauen();
      if (typeof d.showModal !== 'function') { return; }   // dann eben als Link

      e.preventDefault();
      lupeZeigen(lupeListe.indexOf(link));
      d.showModal();
    });
  }


  /* ==========================================================
     Kontodaten
     ----------------------------------------------------------
     Stehen nicht mehr hier, sondern im Paket:
     packages/einfach_bdp/js/konto.js.

     Grund: Die Bankverbindung ist inzwischen ein eigener Block
     mit eigenen Feldern, und der GiroCode wird aus ihnen
     gerechnet. Skript und Block gehören zusammen und wandern
     zusammen.
     ---------------------------------------------------------- */


  bereit(function () {
    Array.prototype.forEach.call(document.querySelectorAll('[data-antrag]'), antrag);
    galerie();
  });

  document.addEventListener('ccm-block-added', function () {
    Array.prototype.forEach.call(document.querySelectorAll('[data-antrag]'), antrag);
    galerieInfos();
  });
})();
