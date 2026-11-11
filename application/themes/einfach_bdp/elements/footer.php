<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Area\GlobalArea;
use Concrete\Core\Support\Facade\Application;

$themePfad = $view->getThemePath();
$navHelfer = Application::getFacadeApplication()->make('helper/navigation');
?>

<?php
/*
   Fußbereich — ein flacher blauer Balken mit einer leichten Schräge.

   Aufbau: eine Reihe aus Marken, Anschrift und Kontaktwegen, darunter
   die gestrichelte CD-Linie und die Schlusszeile. Sonst nichts.

   Der Inhalt kommt aus dem globalen Bereich „Fußzeile“, in dem ein
   Block „Fußzeile“ liegt. Global heißt: einmal gepflegt, überall
   geändert. Der Balken selbst, seine Farbe und die Schräge bleiben
   hier im Template — daran soll niemand versehentlich drehen.

   Was hier bewusst fehlt, sind Überschriften über den Blöcken. „Wo du
   uns findest“ über einer Anschrift sagt nichts, was die Anschrift
   nicht selbst sagt, kostet aber rund 32 px Höhe. Für Screenreader
   tragen die Blöcke stattdessen ein aria-label.

   Auf Blau gelten andere Farben als auf Weiß:
     · Marken in Gelb bzw. gelber Wortbildmarke — die blau-gelbe würde
       hier zur Hälfte verschwinden ✅ CD: auf dunklem Grund die gelbe
       oder weiße Variante
     · Fließtext Weiß (10,15:1), Auszeichnungen und Links Gelb (7,00:1)
*/

$fussBereich = new GlobalArea('Fußzeile');
$fussGefuellt = $fussBereich->getTotalBlocksInArea($c) > 0;
?>
<footer class="v-fuss">
  <div class="v-fuss__balken auf-dunkel">
    <div class="container">

      <?php if ($fussGefuellt || (is_object($c) && $c->isEditMode())) { ?>
          <?php $fussBereich->display(); ?>
      <?php } ?>

      <?php if (!$fussGefuellt) { ?>
          <?php
          /* ------------------------------------------------------------
             Rückfallebene

             Sie steht hier, damit die Fußzeile auch dann vollständig
             ist, wenn im globalen Bereich noch kein Block liegt — etwa
             direkt nach der Installation oder wenn jemand den Block
             löscht. Ohne sie wäre die Seite dann ohne Impressumslink,
             und das ist keine Kleinigkeit.
             ------------------------------------------------------------ */
          ?>
          <div class="v-fuss__raster">

            <p class="v-fuss__marken">
              <a class="v-fuss__marke" href="<?= View::url('/') ?>">
                <img class="v-fuss__marke-stamm"
                     src="<?= $themePfad ?>/images/bdp-klilie-blau-gelb.svg"
                     alt="BdP-Logo, zur Startseite"
                     width="33" height="38">
              </a>
              <a class="v-fuss__marke" href="https://www.pfadfinden.de/" rel="noopener external">
                <img class="v-fuss__marke-bdp"
                     src="<?= $themePfad ?>/images/bdp-wbm-gelb.svg"
                     alt="Bund der Pfadfinder*innen, zur Website pfadfinden.de"
                     width="75" height="24">
              </a>
            </p>

            <address class="v-adresse" aria-label="Anschrift">
              <strong>Stammesheim</strong>
              Musterstraße 1<br>
              12345 Musterort
            </address>

            <ul class="v-sozial" aria-label="Kontakt und Kanäle">
              <li>
                <a href="mailto:info@musterstamm.de">
                  <span class="v-sozial__ikon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" role="img" focusable="false">
                      <path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4.24-8 4.76-8-4.76V6l8 4.76L20 6z"/>
                    </svg>
                  </span>
                  <span class="v-sozial__text">info@musterstamm.de</span>
                </a>
              </li>
            </ul>

          </div>

          <div class="v-fuss__schluss">
            <ul class="v-rechtslinks">
              <li><a href="<?= View::url('/impressum') ?>">Impressum</a></li>
              <li><a href="<?= View::url('/datenschutz') ?>">Datenschutzerklärung</a></li>
              <li><?= $navHelfer->getLogInOutLink() ?></li>
            </ul>
            <p class="v-fuss__hinweis">
              © <?= date('Y') ?> Musterstamm · Landesverband Muster ·
              Mitglied im Ring deutscher Pfadfinder*innenverbände (rdp)
            </p>
          </div>
      <?php } ?>

    </div>
  </div>
</footer>

<?php $view->inc('elements/footer_bottom.php'); ?>
