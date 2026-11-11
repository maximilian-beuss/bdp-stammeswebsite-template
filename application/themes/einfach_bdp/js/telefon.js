/* ============================================================
   Telefonnummern hinter Cloudflare Turnstile
   ------------------------------------------------------------
   Im HTML der Seite steht keine Ziffer der Nummern. Sichtbar sind
   nur Name, Amt und die Vorwahl; alles andere sind Kreise und
   Sechsecke. Erst wenn der Server ein Turnstile-Token bei Cloudflare
   hat bestätigen lassen, schickt er die Nummern, und dann stehen sie
   als anklickbare tel:-Links da.

   Das schützt gegen Sammelskripte, die den Quelltext durchsuchen:
   Was nicht drinsteht, kann man nicht abschreiben.

   Warum die Platzhalter Formen sind und keine gewürfelten Ziffern
   ----------------------------------------------------------------
   Vorher standen dort laufend wechselnde Zufallsziffern, auf ein
   Canvas gemalt. Das war unruhig, sah nach Fehler aus, und blinde
   Nutzer bekamen eine Fläche ohne Bedeutung. Kreise und Sechsecke
   sagen dagegen sofort: „hier fehlt noch etwas“. Sie drehen sich in
   unregelmäßigen Abständen, damit das Kästchen lebendig wirkt, ohne
   zu flackern.

   Die Schnittstelle
   -----------------
   Drei Adressen, die der Block als data-Attribute mitgibt:
     …/konfig    Sitekey und ob die Sitzung schon frei ist
     …/pruefen   Token einlösen, liefert bei Erfolg die Nummern
     …/nummern   Nummern für eine bereits freigeschaltete Sitzung
   ============================================================ */
(function () {
  'use strict';

  var wenigerBewegung = window.matchMedia('(prefers-reduced-motion: reduce)');

  var TURNSTILE_SKRIPT = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
  var turnstileLaedt = null;

  /**
   * Lädt das Turnstile-Skript, aber nur einmal und nur, wenn es
   * wirklich gebraucht wird. Auf Seiten ohne Telefonwidget wird gar
   * nichts von Cloudflare geholt — das steht so auch in der
   * Datenschutzerklärung.
   */
  function turnstileLaden() {
    if (window.turnstile) { return Promise.resolve(window.turnstile); }
    if (turnstileLaedt) { return turnstileLaedt; }

    turnstileLaedt = new Promise(function (fertig, schiefgegangen) {
      var skript = document.createElement('script');
      skript.src = TURNSTILE_SKRIPT;
      skript.async = true;
      skript.defer = true;
      skript.onload = function () {
        if (window.turnstile) { fertig(window.turnstile); }
        else { schiefgegangen(new Error('Turnstile fehlt nach dem Laden')); }
      };
      skript.onerror = function () { schiefgegangen(new Error('Turnstile nicht erreichbar')); };
      document.head.appendChild(skript);
    });

    return turnstileLaedt;
  }


  /* ----------------------------------------------------------
     Ein Widget
     ---------------------------------------------------------- */

  function widget(kasten) {
    if (kasten.hasAttribute('data-telefon-laeuft')) { return; }
    kasten.setAttribute('data-telefon-laeuft', '1');

    /* Die drei Adressen liefert der Block selbst. Es sind
       Block-Aktionen: Concrete lädt vor dem Aufruf den Datensatz
       genau dieses Blocks, der Endpunkt weiß dadurch, welche Nummern
       und welcher Secret Key gemeint sind. Zwei Telefonblöcke auf
       einer Seite kommen sich nicht ins Gehege. */
    var adressen = {
      konfig: kasten.getAttribute('data-telefon-konfig'),
      pruefen: kasten.getAttribute('data-telefon-pruefen'),
      nummern: kasten.getAttribute('data-telefon-nummern')
    };

    var eintraege = [].slice.call(kasten.querySelectorAll('[data-telefon-eintrag]'));
    var schloss = kasten.querySelector('[data-telefon-schloss]');
    if (eintraege.length === 0) { return; }

    var meldung = kasten.querySelector('[data-telefon-meldung]');
    var hinweis = kasten.querySelector('[data-telefon-hinweis]');
    var drehUhr = null;
    var freigegeben = false;


    /* --- Platzhalter: Kreise und Sechsecke ------------------------
       Je verdeckter Stelle eine Form. Die Mischung ist zufällig, aber
       fest: Sie wird einmal gewürfelt und bleibt dann, damit nicht
       bei jeder Drehung eine andere Form dasteht. */

    function formenBauen() {
      var mitSechseck = kasten.hasAttribute('data-telefon-sechseck');
      eintraege.forEach(function (eintrag) {
        var platz = eintrag.querySelector('[data-telefon-verdeckt]');
        if (!platz) { return; }

        var stellen = parseInt(eintrag.getAttribute('data-stellen'), 10) || 8;
        platz.innerHTML = '';

        for (var i = 0; i < stellen; i++) {
          var form = document.createElement('span');
          // Sechseck-Feature: Wenn aktiviert, Mischung aus Kreisen und Sechsecken.
          // Standard: nur Kreise.
          form.className = 'v-telefon__form v-telefon__form--'
            + (mitSechseck && Math.random() < 0.66 ? 'sechseck' : 'kreis');

          // Nach der dritten und der sechsten Stelle eine Lücke, dann
          // liest sich die Reihe wie eine gruppierte Nummer.
          if (i > 0 && i % 3 === 0) { form.classList.add('v-telefon__form--luecke'); }

          platz.appendChild(form);
        }
      });
    }

    /**
     * Dreht ein paar zufällige Formen um einen zufälligen Winkel.
     *
     * Bewusst nicht alle gleichzeitig und nicht im festen Takt: Ein
     * gleichmäßiges Blinken zieht den Blick an und stört beim Lesen
     * des Textes daneben. So wirkt es wie ein leises Flimmern.
     */
    function drehen() {
      var formen = kasten.querySelectorAll('.v-telefon__form');
      if (formen.length === 0) { return; }

      var wieviele = Math.max(1, Math.round(formen.length * 0.25));

      for (var i = 0; i < wieviele; i++) {
        var form = formen[Math.floor(Math.random() * formen.length)];
        form.style.setProperty('--dreh', Math.round(Math.random() * 360) + 'deg');
      }
    }

    function drehenPlanen() {
      if (freigegeben || wenigerBewegung.matches || document.hidden) { return; }
      window.clearTimeout(drehUhr);
      // Zufälliger Abstand zwischen anderthalb und viereinhalb Sekunden.
      drehUhr = window.setTimeout(function () {
        drehen();
        drehenPlanen();
      }, 1500 + Math.random() * 3000);
    }

    function drehenStoppen() {
      window.clearTimeout(drehUhr);
      drehUhr = null;
    }

    // Nur drehen, wenn das Kästchen im Bild ist und der Tab vorn liegt.
    if ('IntersectionObserver' in window) {
      var auge = new IntersectionObserver(function (eintraegeIO) {
        eintraegeIO.forEach(function (e) {
          if (e.isIntersecting) { drehenPlanen(); } else { drehenStoppen(); }
        });
      }, { threshold: 0.1 });
      auge.observe(kasten);
    } else {
      drehenPlanen();
    }

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { drehenStoppen(); } else { drehenPlanen(); }
    });


    /* --- Reden ---------------------------------------------------- */

    function sagen(was, istFehler) {
      if (!meldung) { return; }
      meldung.textContent = was || '';
      meldung.classList.toggle('v-telefon__meldung--fehler', !!istFehler);
    }

    function holen(welche, daten) {
      var adresse = adressen[welche];
      if (!adresse) { return Promise.reject(new Error('keine Adresse für ' + welche)); }

      var einstellungen = {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      };

      if (daten) {
        einstellungen.method = 'POST';
        einstellungen.body = daten;
      }

      return fetch(adresse, einstellungen).then(function (antwort) {
        return antwort.json().catch(function () {
          return { ok: false, meldung: 'Der Server hat unerwartet geantwortet.' };
        });
      });
    }


    /* --- Nummern anzeigen -----------------------------------------
       Nach dem Lösen steht die Nummer als ganz normaler Text in einem
       tel:-Link. Kein Canvas, keine Verschleierung mehr: Der Schutz
       war, dass die Nummer nicht im HTML stand, und den hat sie
       hinter sich. Wer das Captcha gelöst hat, darf die Nummer auch
       kopieren — und Screenreader, Sprachsteuerung und die
       Anruffunktion des Telefons kommen an einen Textlink heran, an
       eine gemalte Fläche nicht ✅ WCAG 1.1.1 */

    function anzeigen(nummern) {
      if (!nummern || nummern.length === 0) { return; }

      freigegeben = true;
      drehenStoppen();
      kasten.classList.add('ist-frei');

      var offen = eintraege.slice();

      nummern.forEach(function (n) {
        var eintrag = null;

        // Zuordnung über den Namen, nicht über die Reihenfolge: So
        // bleibt die Seite heil, wenn eine Nummer dazukommt oder die
        // Reihenfolge wechselt.
        for (var i = 0; i < offen.length; i++) {
          if ((offen[i].getAttribute('data-name') || '').toLowerCase()
              === String(n.name).toLowerCase()) {
            eintrag = offen.splice(i, 1)[0];
            break;
          }
        }
        if (!eintrag) { eintrag = offen.shift(); }
        if (!eintrag) { return; }

        var wert = eintrag.querySelector('[data-telefon-wert]');
        if (!wert) { return; }

        var link = document.createElement('a');
        link.className = 'v-telefon__anruf';
        link.href = 'tel:' + (n.waehlbar || n.nummer);
        link.textContent = n.nummer;

        wert.innerHTML = '';
        wert.appendChild(link);
        wert.classList.add('v-telefon__wert--frisch');
      });

      sagen('');
      if (hinweis) { hinweis.hidden = true; }
      if (schloss) { schloss.hidden = true; }
    }


    /* --- Token einlösen ------------------------------------------- */

    function tokenEinloesen(token) {
      var daten = new URLSearchParams();
      daten.append('token', token);

      sagen('Einen Moment …', false);

      return holen('pruefen', daten).then(function (antwort) {
        if (antwort && antwort.ok && antwort.nummern) {
          anzeigen(antwort.nummern);
          return;
        }

        sagen((antwort && antwort.meldung) || 'Das hat nicht geklappt.', true);
        if (window.turnstile) { window.turnstile.reset(); }
      }).catch(function () {
        sagen('Der Server war nicht erreichbar. Bitte versuch es später noch einmal.', true);
      });
    }


    /* --- Start ---------------------------------------------------- */

    formenBauen();

    holen('konfig').then(function (konfig) {
      if (!konfig || !konfig.ok || !konfig.sitekey) {
        kasten.classList.add('ist-alt');
        return;
      }

      // Wer in den letzten Minuten schon gelöst hat, muss das nicht
      // noch einmal tun. Die Freigabe steht in der Sitzung.
      if (konfig.frei) {
        return holen('nummern').then(function (n) {
          if (n && n.ok && n.nummern) { anzeigen(n.nummern); }
        });
      }

      if (!schloss) { return; }

      return turnstileLaden().then(function (turnstile) {
        turnstile.render(schloss, {
          sitekey: konfig.sitekey,
          theme: 'light',
          language: 'de',
          action: 'telefonnummern',
          callback: tokenEinloesen,
          'error-callback': function () {
            sagen('Das Captcha konnte nicht geladen werden.', true);
          },
          'expired-callback': function () {
            sagen('Das Captcha ist abgelaufen. Bitte noch einmal.', true);
          }
        });
      }).catch(function () {
        // Cloudflare nicht erreichbar: Dann gibt es keinen Weg über
        // das Captcha, und der E-Mail-Weg muss übernehmen.
        kasten.classList.add('ist-alt');
      });
    }).catch(function () {
      kasten.classList.add('ist-alt');
    });
  }


  function start() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-telefon]'), widget);
  }

  if (document.readyState !== 'loading') { start(); }
  else { document.addEventListener('DOMContentLoaded', start); }

  document.addEventListener('ccm-block-added', start);
})();
