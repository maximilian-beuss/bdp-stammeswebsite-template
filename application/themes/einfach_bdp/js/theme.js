/* ============================================================
   einfachBdP — Theme-Skript
   ------------------------------------------------------------
   Alles hier ist progressive enhancement: ohne JavaScript bleibt
   die Seite vollständig bedienbar und alle Inhalte lesbar.
   Bewegung richtet sich nach prefers-reduced-motion.
   ============================================================ */
(function () {
  'use strict';

  var wenigerBewegung = window.matchMedia('(prefers-reduced-motion: reduce)');

  function bereit(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }


  /* ----------------------------------------------------------
     Mobiles Menü
     ---------------------------------------------------------- */
  function menue() {
    var burger = document.querySelector('.v-burger');
    var nav = document.querySelector('.v-nav');
    if (!burger || !nav) { return; }

    function setzen(offen) {
      burger.setAttribute('aria-expanded', String(offen));
      nav.classList.toggle('ist-offen', offen);
      document.body.classList.toggle('menue-offen', offen);
      burger.setAttribute('aria-label', offen ? 'Menü schließen' : 'Menü öffnen');
    }

    burger.addEventListener('click', function () {
      setzen(burger.getAttribute('aria-expanded') !== 'true');
    });

    /* Kein Aufklappen: Im mobilen Menü stehen alle Untermenüs offen.
       Das kostet Höhe, zeigt dafür in einem Blick, was es gibt — die
       Ersparnis holt das Layout über zwei Spalten herein. */

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && burger.getAttribute('aria-expanded') === 'true') {
        setzen(false);
        burger.focus();
      }
    });

    // Beim Wechsel auf Desktop-Breite aufräumen
    var breit = window.matchMedia('(min-width: 992px)');
    (breit.addEventListener ? breit.addEventListener.bind(breit, 'change') : breit.addListener.bind(breit))(function (e) {
      if (e.matches) { setzen(false); }
    });
  }


  /* ----------------------------------------------------------
     Kopfzeile: Schatten sobald gescrollt wird
     ---------------------------------------------------------- */
  function kopfSchatten() {
    var kopf = document.querySelector('.v-kopf');
    if (!kopf) { return; }
    var laeuft = false;
    function pruefen() {
      kopf.classList.toggle('ist-gescrollt', window.scrollY > 8);
      laeuft = false;
    }
    window.addEventListener('scroll', function () {
      if (!laeuft) { laeuft = true; window.requestAnimationFrame(pruefen); }
    }, { passive: true });
    pruefen();
  }


  /* ----------------------------------------------------------
     Hero-Bildwechsler
     ------------------------------------------------------------
     Grundlage ist eine echte Scroll-Spur mit CSS scroll-snap. Damit
     funktionieren Wischen auf dem Touchscreen und das Scrollrad ohne
     eine Zeile JavaScript. Ergänzt werden hier:
       · Ziehen mit der Maus
       · Pfeiltasten, Pos1 und Ende
       · Punkte und Pfeilknöpfe
       · Autoplay, das bei Hover, Fokus, verstecktem Tab oder
         prefers-reduced-motion pausiert
       · endloser Umlauf über Kopien am Anfang und am Ende

     Der Umlauf
     ----------
     Vorher sprang die Spur nach der letzten Folie über alle Bilder
     zurück an den Anfang. Das sah aus, als hätte man sich verklickt.

     Jetzt liegt am Ende der Spur eine Kopie der ersten Folie und am
     Anfang eine Kopie der letzten. Von der letzten Folie geht es
     vorwärts weiter auf die Kopie — die Bewegung stimmt also mit dem
     überein, was der Klick versprochen hat. Ist die Animation durch,
     wird die Spur ohne Animation auf das Original gesetzt. Da beide
     Folien identisch aussehen, ist der Wechsel nicht zu sehen.
     Rückwärts läuft dasselbe spiegelbildlich.

     Zwei Zähler
     -----------
       stelle   Position in der Spur, 0 bis n+1 (Kopien eingerechnet)
       aktuell  echte Folie, 0 bis n-1 — für Punkte und Vorlesetext

     Alles, was der Benutzer sieht und hört, hängt an aktuell. stelle
     ist reine Mechanik.
     ---------------------------------------------------------- */
  function hero() {
    var hero = document.querySelector('[data-hero]');
    if (!hero) { return; }

    var spur = hero.querySelector('[data-hero-spur]');
    if (!spur) { return; }

    var echte = Array.prototype.slice.call(spur.querySelectorAll('.v-hero__folie'));
    if (echte.length < 2) { return; }

    var anzahl = echte.length;
    var punkte = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-punkt]'));
    var stand = hero.querySelector('[data-hero-stand]');
    var takt = parseInt(hero.getAttribute('data-takt'), 10) || 7000;

    var aktuell = 0;      // echte Folie
    var stelle = 1;       // Position in der Spur
    var uhr = null;
    var ersteMeldung = true;


    /* --- 0. Die Kopien anlegen --------------------------------------
       Die Kopien sind vollständig: Bild, Überschrift, Text, Knöpfe.

       Dass beim Umlauf nichts doppelt läuft, regelt nicht das Weglassen
       von Inhalten, sondern die Klasse .ist-dran (Abschnitt 2): Sie
       liegt gleichzeitig auf der Folie und auf ihrer Kopie am anderen
       Ende der Spur. Bildzoom und Texteinblendung starten damit auf
       beiden im selben Moment, laufen synchron, und das stille
       Umsetzen von der Kopie aufs Original ist nicht zu sehen.

       Für Screenreader ist die Kopie nicht vorhanden: aria-hidden auf
       der Folie, und alles Fokussierbare darin fliegt aus der
       Tabfolge. Sonst gäbe es jeden Knopf zweimal und die
       Tabreihenfolge liefe durch Inhalte, die nur technisch da sind
       ✅ WCAG 4.1.2 */

    function kopieVon(folie) {
      var kopie = folie.cloneNode(true);
      kopie.classList.remove('ist-aktiv', 'ist-sichtbar', 'ist-dran');
      kopie.setAttribute('aria-hidden', 'true');
      kopie.setAttribute('data-hero-kopie', '');
      // Ohne das meldete die Kopie sich als „Folie 3 von 5“, obwohl es
      // sie als Folie gar nicht gibt.
      kopie.removeAttribute('aria-label');
      kopie.removeAttribute('aria-roledescription');
      kopie.removeAttribute('role');

      Array.prototype.forEach.call(
        kopie.querySelectorAll('a, button, input, select, textarea, [tabindex]'),
        function (el) { el.setAttribute('tabindex', '-1'); }
      );

      // Ein zweites Mal dasselbe Bild mit hoher Priorität anzufordern
      // wäre verschenkte Bandbreite.
      Array.prototype.forEach.call(kopie.querySelectorAll('img'), function (bild) {
        bild.removeAttribute('fetchpriority');
        bild.setAttribute('loading', 'lazy');
      });

      return kopie;
    }

    spur.insertBefore(kopieVon(echte[anzahl - 1]), echte[0]);
    spur.appendChild(kopieVon(echte[0]));

    var alle = Array.prototype.slice.call(spur.querySelectorAll('.v-hero__folie'));

    /** Von der Position in der Spur auf die echte Folie. */
    function echt(d) {
      return ((d - 1) % anzahl + anzahl) % anzahl;
    }


    /* --- 1. Zustand: welche Folie ist die aktuelle? ------------------
       Bewusst ohne Früh-Ausstieg. Der alte Code stieg bei
       „i === aktuell“ aus; lief der Zähler einmal aus dem Tritt, kam
       er nie wieder mit den Klassen im DOM zusammen. */

    function zustandSetzen(r) {
      aktuell = r;
      echte.forEach(function (f, n) { f.classList.toggle('ist-aktiv', n === r); });
      punkte.forEach(function (p, n) { p.setAttribute('aria-current', String(n === r)); });
      // Beim Seitenaufbau nichts vorlesen — sonst meldet sich der
      // Bildwechsler ungefragt beim Screenreader.
      if (stand && !ersteMeldung) {
        stand.textContent = 'Bild ' + (r + 1) + ' von ' + anzahl;
      }
      ersteMeldung = false;
    }


    /* --- 2. Sichtbarkeit: was steht wirklich im Sichtfenster? --------
       Der Text hing früher an .ist-aktiv, und .ist-aktiv wurde beim
       Klick sofort auf die Zielfolie gesetzt — also lange bevor die
       Spur dort ankam. Für die Dauer des Scrollens verlor die
       sichtbare Folie ihren Text.

       Jetzt entscheidet der Browser: Eine Folie, die mindestens die
       Hälfte des Sichtfensters füllt, bekommt .ist-sichtbar — egal ob
       gewischt, gezogen, geblättert oder getippt wurde. Kopien
       eingeschlossen, sie sehen ja genauso aus. */

    function sichtSetzen(d) {
      var r = echt(d);

      alle.forEach(function (f, n) {
        // Welche Folie steht wirklich im Bild. Wird für den Zustand
        // gebraucht, nicht für die Gestaltung.
        f.classList.toggle('ist-sichtbar', n === d);

        // Und hier hängt alles Bewegte dran: Bildzoom UND
        // Texteinblendung. .ist-dran bekommt die Folie und zugleich
        // ihre Kopie am anderen Ende der Spur, denn beide zeigen
        // dasselbe. Sie fangen im selben Moment an und laufen
        // synchron; wenn die Spur nach der Animation still von der
        // Kopie auf das Original umgesetzt wird, stehen beide auf
        // demselben Stand und man sieht nichts davon.
        //
        // Hinge die Animation an .ist-sichtbar, liefe sie beim Umlauf
        // zweimal kurz nacheinander — einmal auf der Kopie, einmal auf
        // dem Original.
        f.classList.toggle('ist-dran', echt(n) === r);
      });
    }

    function stelleAusScroll() {
      var breite = spur.clientWidth || 1;
      return Math.max(0, Math.min(alle.length - 1, Math.round(spur.scrollLeft / breite)));
    }

    if ('IntersectionObserver' in window) {
      var auge = new IntersectionObserver(function (eintraege) {
        eintraege.forEach(function (e) {
          if (e.intersectionRatio < 0.5) { return; }
          var d = alle.indexOf(e.target);
          if (d < 0) { return; }
          sichtSetzen(d);
          // Läuft gerade ein geführter Sprung, kennt zeigen() das Ziel
          // besser als die Zwischenposition. Sonst zöge eine Folie, die
          // unterwegs durchs Bild wandert, den Zähler zurück.
          if (!notUhr) { zustandSetzen(echt(d)); }
        });
      }, { root: spur, threshold: [0, 0.5, 1] });
      alle.forEach(function (f) { auge.observe(f); });
    }


    /* --- 3. Blättern ------------------------------------------------
       Zielposition ist offsetLeft der Folie, nicht i × clientWidth:
       clientWidth ist gerundet, offsetLeft ist die echte Kante.

       Während eines geführten Sprungs schaltet .wird-gefuehrt das
       Einrasten ab. Sonst zieht der Browser die Spur mitten im Sprung
       auf den nächstgelegenen Rastpunkt zurück. */

    var sollJetzt = 0, ruheUhr = null, notUhr = null, freiUhr = null;
    var setztStill = false;   // gerade wird selbst umgesetzt
    var fingerDrauf = false;  // ein Finger liegt auf der Spur

    /**
     * Setzt die Spur ohne Animation auf eine Position. Das ist der
     * Trick hinter dem Umlauf: Nach der Bewegung auf eine Kopie wird
     * hier auf das Original gewechselt, und weil beide gleich
     * aussehen, sieht man nichts davon.
     */
    function stillSetzen(d) {
      stelle = d;
      spur.classList.add('wird-gefuehrt');       // Einrasten aus
      // Das eigene Umsetzen löst ein scroll-Ereignis aus. Ohne diesen
      // Merker liest der Zuhörer dort die Position, die gerade gesetzt
      // wurde, stößt seine Ruhe-Uhr neu an und ruft am Ende wieder
      // umlaufAufloesen — eine Runde, die nichts tut außer Arbeit.
      setztStill = true;
      var vorher = spur.style.scrollBehavior;
      spur.style.scrollBehavior = 'auto';
      spur.scrollLeft = Math.round(alle[d].offsetLeft);
      sollJetzt = spur.scrollLeft;
      spur.style.scrollBehavior = vorher;
      sichtSetzen(d);
      window.requestAnimationFrame(function () {
        spur.classList.remove('wird-gefuehrt');
        setztStill = false;
      });
    }

    /** Steht die Spur auf einer Kopie, geht es still aufs Original. */
    function umlaufAufloesen() {
      if (stelle === 0) { stillSetzen(anzahl); return true; }
      if (stelle === anzahl + 1) { stillSetzen(1); return true; }
      return false;
    }

    function ruheAnstossen() {
      if (!notUhr) { return; }          // gerade kein Sprung unterwegs
      window.clearTimeout(ruheUhr);
      ruheUhr = window.setTimeout(angekommen, 170);
    }

    function angekommen() {
      window.clearTimeout(ruheUhr);
      window.clearTimeout(notUhr);
      ruheUhr = null; notUhr = null;
      // Ist das weiche Scrollen ausgeblieben oder unterwegs abgebrochen,
      // wird hier hart nachgeholt — der Wunsch war die Zielfolie.
      if (Math.abs(spur.scrollLeft - sollJetzt) > 1) { spur.scrollLeft = sollJetzt; }
      spur.classList.remove('wird-gefuehrt');
      umlaufAufloesen();
      // Nach dem Umlauf zeigt die Spur eine andere Position als vor dem
      // Sprung. Ohne diese Zeile blieben die Punkte auf dem Stand von
      // vorhin stehen — Folie 6 im Bild, Punkt 3 aktiv.
      zustandSetzen(echt(stelle));
    }

    /* Ein Finger auf der Spur hält den Bildwechsler an — genauso, wie
       es der Zeiger über dem Hero tut.

       Vorher fehlte das: Wischen läuft als gewöhnliches Scrollen im
       Browser ab und ging an neuStarten() vorbei. Der Takt lief
       stur weiter und sprang mitten in die Wischbewegung, mit einem
       Zähler, der noch auf der alten Folie stand. Genau dann kam die
       Spur mit den Kopien an den Rändern durcheinander. */
    spur.addEventListener('touchstart', function () {
      fingerDrauf = true;
      stoppen();
    }, { passive: true });

    /* neuStarten() auch hier und nicht nur nach dem Ausrollen: Ein
       Tippen ohne Wischen löst gar kein scroll-Ereignis aus. Ohne
       diese Zeile bliebe der Bildwechsler nach einer Berührung für
       immer stehen. */
    function fingerWeg() {
      fingerDrauf = false;
      neuStarten();
    }
    spur.addEventListener('touchend', fingerWeg, { passive: true });
    spur.addEventListener('touchcancel', fingerWeg, { passive: true });

    spur.addEventListener('scroll', function () {
      if (setztStill) { return; }       // das war das eigene Umsetzen
      ruheAnstossen();
      if (notUhr) { return; }           // geführter Sprung räumt selbst auf

      /* Der Zähler wandert sofort mit und nicht erst, wenn die Spur
         steht. Er wird von außen gelesen — vom Bildwechsler, von den
         Pfeilen, von der Tastatur —, und ein Zähler, der 160 ms lang
         die vorherige Folie meldet, schickt sie alle eine Folie zu
         weit. */
      stelle = stelleAusScroll();

      // Freies Scrollen: wischen, ziehen, Rad. Wenn die Spur zur Ruhe
      // gekommen ist, wird nachgezogen und bei Bedarf umgesetzt.
      window.clearTimeout(freiUhr);
      freiUhr = window.setTimeout(function () {
        if (fingerDrauf) { return; }    // der Finger liegt noch, nichts entscheiden
        stelle = stelleAusScroll();
        zustandSetzen(echt(stelle));
        umlaufAufloesen();
        neuStarten();                   // Takt nach der Geste von vorn
      }, 160);
    }, { passive: true });

    /**
     * @param {number}  d      Position in der Spur, darf 0 und n+1 sein
     * @param {boolean} weich  false = ohne Animation
     */
    function zeigen(d, weich) {
      d = Math.max(0, Math.min(alle.length - 1, d));

      var sofort = (weich === false || wenigerBewegung.matches);
      stelle = d;
      sollJetzt = Math.round(alle[d].offsetLeft);

      // Punkte und Vorlesetext reagieren sofort — das ist die Rückmeldung
      // auf den Klick. Der Text im Bild folgt der Sicht (Abschnitt 2).
      zustandSetzen(echt(d));

      if (sofort) {
        stillSetzen(d);
        umlaufAufloesen();
        return;
      }

      spur.classList.add('wird-gefuehrt');
      window.clearTimeout(notUhr);
      notUhr = window.setTimeout(angekommen, 1600);   // harte Obergrenze
      spur.scrollTo({ left: sollJetzt, behavior: 'smooth' });
      ruheAnstossen();
    }

    punkte.forEach(function (p) {
      p.addEventListener('click', function () {
        zeigen(parseInt(p.getAttribute('data-hero-punkt'), 10) + 1, true);
        neuStarten();
      });
    });

    var vor = hero.querySelector('[data-hero-vor]');
    var zurueck = hero.querySelector('[data-hero-zurueck]');
    if (vor) { vor.addEventListener('click', function () { zeigen(stelle + 1, true); neuStarten(); }); }
    if (zurueck) { zurueck.addEventListener('click', function () { zeigen(stelle - 1, true); neuStarten(); }); }


    /* --- 4. Tastatur --- */

    spur.addEventListener('keydown', function (e) {
      var behandelt = true;
      switch (e.key) {
        case 'ArrowRight': zeigen(stelle + 1, true); break;
        case 'ArrowLeft':  zeigen(stelle - 1, true); break;
        case 'Home':       zeigen(1, true); break;
        case 'End':        zeigen(anzahl, true); break;
        default:           behandelt = false;
      }
      if (behandelt) { e.preventDefault(); neuStarten(); }
    });


    /* --- 5. Scrollrad ------------------------------------------------
       Ohne diesen Griff blättert das Rad überhaupt nicht: eine Raste
       schiebt rund 120 px, eine Folie ist über 1000 px breit — und
       „scroll-snap-type: mandatory“ zieht die Spur nach jeder Raste
       sofort auf dieselbe Folie zurück.

       Senkrechtes Rad gehört weiterhin der Seite. Nur waagerechte
       Gesten (Trackpad, Kippräder) und Umschalt+Rad blättern. */

    var radSumme = 0, radRuhe = null, radSperre = 0;

    spur.addEventListener('wheel', function (e) {
      var waagerecht = Math.abs(e.deltaX) > Math.abs(e.deltaY);
      if (!waagerecht && !e.shiftKey) { return; }   // die Seite darf scrollen
      e.preventDefault();
      if (Date.now() < radSperre) { return; }

      radSumme += waagerecht ? e.deltaX : e.deltaY;
      window.clearTimeout(radRuhe);
      radRuhe = window.setTimeout(function () { radSumme = 0; }, 220);

      if (Math.abs(radSumme) < 30) { return; }
      var richtung = radSumme > 0 ? 1 : -1;
      radSumme = 0;
      radSperre = Date.now() + 520;
      zeigen(stelle + richtung, true);
      neuStarten();
    }, { passive: false });


    /* --- 6. Ziehen mit der Maus --------------------------------------
       setPointerCapture hält den Zug fest, auch wenn der Zeiger die
       Spur verlässt. Beim Loslassen entscheidet die zurückgelegte
       Strecke, nicht die Restposition: ein entschlossener kurzer Zug
       blättert weiter, ein Wackler bleibt stehen. */

    var zieht = false, startX = 0, startScroll = 0, bewegt = 0;

    spur.addEventListener('pointerdown', function (e) {
      if (e.pointerType === 'touch') { return; }          // Touch macht der Browser selbst
      if (e.target.closest && e.target.closest('a, button')) { return; }
      zieht = true;
      bewegt = 0;
      startX = e.clientX;
      startScroll = spur.scrollLeft;
      spur.classList.add('wird-gezogen');
      try { spur.setPointerCapture(e.pointerId); } catch (x) { /* egal */ }
      stoppen();
    });

    spur.addEventListener('pointermove', function (e) {
      if (!zieht) { return; }
      var weg = e.clientX - startX;
      bewegt = Math.abs(weg);
      spur.scrollLeft = startScroll - weg;
      if (bewegt > 6) { e.preventDefault(); }
    });

    function ziehenBeenden(e) {
      if (!zieht) { return; }
      zieht = false;
      try { spur.releasePointerCapture(e.pointerId); } catch (x) { /* egal */ }

      var strecke = spur.scrollLeft - startScroll;
      var schwelle = spur.clientWidth * 0.14;
      var ziel = stelle + (Math.abs(strecke) > schwelle ? (strecke > 0 ? 1 : -1) : 0);

      // Erst führen (setzt .wird-gefuehrt, Einrasten bleibt aus), dann
      // .wird-gezogen lösen. Andersherum ruckt die Spur dazwischen auf
      // den nächsten Rastpunkt.
      zeigen(ziel, true);
      spur.classList.remove('wird-gezogen');
      neuStarten();
    }
    spur.addEventListener('pointerup', ziehenBeenden);
    spur.addEventListener('pointercancel', ziehenBeenden);

    // Nach dem Ziehen keinen Klick auf einen Link auslösen. bewegt wird
    // immer zurückgesetzt — sonst schluckt der Wächter irgendwann einen
    // Klick, der gar nichts mit dem Ziehen zu tun hatte.
    spur.addEventListener('click', function (e) {
      if (bewegt > 6) { e.preventDefault(); e.stopPropagation(); }
      bewegt = 0;
    }, true);


    /* --- 7. Autoplay -------------------------------------------------
       Pausengründe stehen in Merkern. Der alte Code rief nach jedem
       Klick starten() auf — auch dann, wenn der Zeiger noch über dem
       Hero stand. Der Bildwechsler lief also weiter, obwohl man ihn
       gerade von Hand bediente. */

    var zeigerDrauf = false, fokusDrin = false;

    function neuStarten() { stoppen(); planen(); }
    function planen() {
      if (wenigerBewegung.matches || document.hidden || zeigerDrauf || fokusDrin) { return; }
      uhr = window.setInterval(function () { zeigen(stelle + 1, true); }, takt);
    }
    function stoppen() { if (uhr) { window.clearInterval(uhr); uhr = null; } }

    hero.addEventListener('mouseenter', function () { zeigerDrauf = true;  stoppen(); });
    hero.addEventListener('mouseleave', function () { zeigerDrauf = false; neuStarten(); });
    hero.addEventListener('focusin',    function () { fokusDrin = true;    stoppen(); });
    hero.addEventListener('focusout',   function () { fokusDrin = false;   neuStarten(); });
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { stoppen(); } else { neuStarten(); }
    });


    /* --- 8. Breite geändert: Position nachziehen --- */

    var neuMessen;
    window.addEventListener('resize', function () {
      window.clearTimeout(neuMessen);
      neuMessen = window.setTimeout(function () { stillSetzen(stelle); }, 150);
    });


    /* --- 9. Startposition -------------------------------------------
       Die Spur beginnt bei scrollLeft 0, und dort liegt jetzt die Kopie
       der letzten Folie. Ohne diesen Schritt sähe man beim Laden das
       falsche Bild. */

    stillSetzen(1);
    zustandSetzen(0);
    planen();
  }


  /* ----------------------------------------------------------
     Sanftes Einblenden beim Scrollen
     ---------------------------------------------------------- */
  function auftritt() {
    var ziele = document.querySelectorAll('.v-auftritt');
    if (!ziele.length) { return; }

    // Im Bearbeitungsmodus alles sofort sichtbar. Wer Blöcke setzt,
    // scrollt ständig hin und her; Elemente, die dabei jedes Mal neu
    // hereinfahren, machen das Setzen zur Geduldsprobe. Außerdem sähe
    // ein Bereich, der noch nicht eingeblendet ist, aus wie ein leerer.
    if (document.documentElement.hasAttribute('data-editmodus')
        || wenigerBewegung.matches || !('IntersectionObserver' in window)) {
      Array.prototype.forEach.call(ziele, function (el) { el.classList.add('ist-sichtbar'); });
      return;
    }

    var beobachter = new IntersectionObserver(function (eintraege) {
      eintraege.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('ist-sichtbar');
          beobachter.unobserve(e.target);
        }
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.06 });

    Array.prototype.forEach.call(ziele, function (el) { beobachter.observe(el); });
  }


  /* ----------------------------------------------------------
     Einbettungen erst nach Zustimmung laden
     ------------------------------------------------------------
     Steht nicht mehr hier, sondern im Paket:
     packages/einfach_bdp/js/einwilligung.js.

     Grund: Die Zustimmung wird inzwischen gespeichert und lässt
     sich auf der Datenschutzseite zurücknehmen. Beides gehört zu
     den Blöcken „Karte“ und „Einwilligungen“ und wandert mit
     ihnen. Diese Fassung hier hat sich nichts gemerkt und hat dem
     Blockskript zuletzt sogar in die Quere gefunkt — beide setzten
     dieselbe Klasse ist-bereit, und wer zuerst kam, ließ den
     anderen ins Leere laufen.
     ---------------------------------------------------------- */


  /* ----------------------------------------------------------
     Glossar: Suche und Buchstabenleiste
     ---------------------------------------------------------- */
  function glossar() {
    var feld = document.querySelector('[data-glossar-suche]');
    if (!feld) { return; }
    var eintraege = document.querySelectorAll('.v-glossar__eintrag');
    var gruppen = document.querySelectorAll('.v-glossar__gruppe');
    var treffer = document.querySelector('[data-glossar-treffer]');

    feld.addEventListener('input', function () {
      var suche = feld.value.trim().toLowerCase();
      var anzahl = 0;

      Array.prototype.forEach.call(eintraege, function (e) {
        var passt = !suche || e.textContent.toLowerCase().indexOf(suche) !== -1;
        e.hidden = !passt;
        if (passt) { anzahl += 1; }
      });

      Array.prototype.forEach.call(gruppen, function (g) {
        var sichtbar = g.querySelectorAll('.v-glossar__eintrag:not([hidden])').length;
        g.hidden = sichtbar === 0;
      });

      if (treffer) {
        treffer.textContent = suche
          ? anzahl + (anzahl === 1 ? ' Eintrag gefunden' : ' Einträge gefunden')
          : '';
      }
    });
  }


  /* ----------------------------------------------------------
     Pfadi-Alphabet: die klebende Buchstabenleiste
     ------------------------------------------------------------
     Zwei Dinge, die sich nur mit gemessenen Werten lösen lassen —
     das CSS kann weder wissen, wie hoch die Leiste gerade ist,
     noch, ob sie umgebrochen ist.

     1. Die Leiste klebt oben am Fenster. Springt man auf einen
        Buchstaben, landet dessen Überschrift genau dort, wo die
        Leiste steht — also darunter und unsichtbar. Ausgeglichen
        wird das mit der Höhe der Leiste: Sie landet als
        --alphabet-hoehe am Elternelement und wird vom
        scroll-margin-top der Überschriften gelesen (main.css §10).

     2. Bricht die Leiste auf mehrere Zeilen um, ist sie zum
        Mitwandern zu hoch — zwei Reihen plus Polster stünden beim
        Scrollen dauerhaft über dem Text. Dann klebt sie nicht mehr,
        und weil nichts mehr klebt, entfällt mit ihr auch der
        Ausgleich aus 1.

     Ohne JavaScript bleibt es beim klebenden Verhalten und dem
     Vorgabewert im CSS — beides ist für die einzeilige Leiste
     richtig, und mehr als eine Zeile hat die Leiste nur auf
     schmalen Schirmen, wo das Sprungziel ohnehin knapp sitzt.
     ---------------------------------------------------------- */
  function alphabetleiste() {
    var leisten = document.querySelectorAll('.v-alphabet');
    if (!leisten.length) { return; }

    function messen() {
      Array.prototype.forEach.call(leisten, function (leiste) {
        var links = leiste.querySelectorAll('a');
        var eltern = leiste.parentNode;

        /* Umgebrochen ist die Leiste, wenn der letzte Buchstabe
           tiefer sitzt als der erste. Das ist verlässlicher, als
           die Zeilenzahl aus Höhe, Polster und Abständen
           zurückzurechnen — dort müsste jede spätere Änderung an
           den Maßen hier nachgezogen werden. */
        var mehrzeilig = links.length > 1
          && links[links.length - 1].offsetTop > links[0].offsetTop;

        leiste.classList.toggle('v-alphabet--umbruch', mehrzeilig);

        if (eltern && eltern.style) {
          eltern.style.setProperty(
            '--alphabet-hoehe',
            mehrzeilig ? '0px' : leiste.offsetHeight + 'px'
          );
        }
      });
    }

    messen();

    /* Beim Ziehen am Fenster wechselt die Zeilenzahl. Einmal je
       Bildaufbau messen reicht und hält das Layout-Lesen aus dem
       Ereignisstrom heraus. */
    var laeuft = false;
    window.addEventListener('resize', function () {
      if (laeuft) { return; }
      laeuft = true;
      window.requestAnimationFrame(function () {
        laeuft = false;
        messen();
      });
    });
  }


  /* ----------------------------------------------------------
     Termine nach Stufe filtern
     ---------------------------------------------------------- */
  function terminfilter() {
    var leiste = document.querySelector('[data-terminfilter]');
    if (!leiste) { return; }
    var knoepfe = leiste.querySelectorAll('.v-filter__knopf');
    var termine = document.querySelectorAll('.v-termin');

    Array.prototype.forEach.call(knoepfe, function (knopf) {
      knopf.addEventListener('click', function () {
        var stufe = knopf.getAttribute('data-stufe') || 'alle';

        Array.prototype.forEach.call(knoepfe, function (k) {
          k.setAttribute('aria-pressed', String(k === knopf));
        });

        Array.prototype.forEach.call(termine, function (t) {
          var stufen = (t.getAttribute('data-stufen') || '').split(/\s+/);
          t.hidden = stufe !== 'alle' && stufen.indexOf(stufe) === -1;
        });
      });
    });
  }


  bereit(function () {
    menue();
    kopfSchatten();
    hero();
    auftritt();
    glossar();
    alphabetleiste();
    terminfilter();
  });

  // Concrete lädt Blockinhalte im Bearbeitungsmodus nach. Was dabei neu
  // ins Dokument kommt, muss dieselbe Behandlung bekommen wie der Rest,
  // sonst funktioniert ein gerade eingefügter Block erst nach einem
  // Neuladen.
  document.addEventListener('ccm-block-added', function () {
    auftritt();
  });
})();
