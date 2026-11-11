<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var string $ueberschriftText
 * @var string $textHtml
 * @var string $ebene
 * @var string $flaeche
 * @var string $ankerName
 * @var string $bildUrl
 * @var int    $bildBreite
 * @var int    $bildHoehe
 * @var string $bildText
 * @var string $bildStil
 */

/*
   Ein Abschnitt ohne Überschrift, ohne Text und ohne Bild ist nichts.
   Er würde als leeres <section> trotzdem seinen Rand mitbringen und
   eine Lücke in die Seite reißen. Im Bearbeitungsmodus bleibt er
   sichtbar, sonst käme man nicht mehr an ihn heran, um ihn zu füllen
   oder zu löschen.
*/
$hatText = trim(strip_tags($textHtml, '<img><iframe><video><table>')) !== ''
    || strpos($textHtml, '<img') !== false;
$hatUeberschrift = $ebene !== 'keine' && trim($ueberschriftText) !== '';
$hatEigenesBild = $bildUrl !== '' && $bildStil !== 'kein';

if (!$hatText && !$hatUeberschrift && !$hatEigenesBild) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-abschnitt__leer">' . t('Leerer Abschnitt. Bearbeiten oder löschen.') . '</p>';
    }

    return;
}

$klassen = 'v-abschnitt';
if (in_array($flaeche, ['gedaempft', 'sand', 'gelb', 'blau'], true)) {
    $klassen .= ' v-abschnitt--flaeche v-abschnitt--' . h($flaeche);
}
// Nur Pfadiblau trägt hellen Text; die anderen Flächen bleiben hell.
if ($flaeche === 'blau') {
    $klassen .= ' auf-dunkel';
}

$ikonUrl = isset($ikonUrl) ? (string) $ikonUrl : '';
if ($ikonUrl !== '') {
    $klassen .= ' v-abschnitt--mit-ikon';
}

/* Das Ikon gehört in die Überschriftzeile, nicht darüber.
   ------------------------------------------------------------------
   Frei über dem Abschnitt sah es aus, als sei es dorthin gefallen:
   Die Überschriften stecken bei den übernommenen Abschnitten im
   Fließtext, und der bringt eigene Innenabstände mit — zwischen Ikon
   und Überschrift klaffte dadurch eine halbe Bildschirmhöhe.

   Deshalb wird es in die erste Überschrift des Textes hineingesetzt.
   Damit steht es immer unmittelbar davor, egal wie viel Abstand das
   Markup drumherum aufmacht, und rückt beim Umbruch mit.

   aria-hidden: Es sagt nichts, was nicht danebensteht. Ein „Grafik
   Kompass“ vor jeder Überschrift wäre für Screenreader nur Lärm
   ✅ WCAG 1.1.1 (dekorativ) */
$ikonHtml = $ikonUrl === '' ? '' :
    '<span class="v-abschnitt__ikon" aria-hidden="true" style="--v-ikon: url(\''
    . h($ikonUrl) . '\')"></span>';

/* Wohin damit? Die erste Überschrift des Abschnitts gewinnt.

   Das ist mal die aus dem eigenen Feld, mal eine, die im Fließtext
   steckt — bei den von der alten Website übernommenen Abschnitten
   fast immer Letzteres. Findet sich gar keine, bleibt nur der Platz
   über dem Text. */
$ikonImFeld = $ikonHtml !== '' && $ebene !== 'keine' && trim($ueberschriftText) !== '';
$ikonImText = false;

if ($ikonHtml !== '' && !$ikonImFeld) {
    // str_replace auf $: In der Ersetzung wäre $1 sonst ein Rückverweis.
    $textHtml = preg_replace(
        '/(<h[1-3]\b[^>]*>)/i',
        '$1' . str_replace('$', '\$', $ikonHtml),
        $textHtml,
        1,
        $ikonImText
    );
    $ikonImText = (bool) $ikonImText;
}

$ikonAllein = $ikonHtml !== '' && !$ikonImFeld && !$ikonImText;

/* Ein Teil der Abschnitte bringt aus der Übernahme der alten Website
   eine eigene <section class="v-sektion"> mit .container darin mit.
   Die hat ihre eigenen Innenabstände — der Abschnitt drumherum darf
   sie dann nicht noch einmal draufrechnen, sonst klafft zwischen
   Farbkante und Überschrift ein halber Bildschirm.

   Die Prüfung ist grob, trifft aber genau die beiden Fälle, die es
   gibt: mit eigenem Rahmen und ohne. */
$eigenerRahmen = strpos($textHtml, 'class="container"') !== false
    || strpos($textHtml, 'v-sektion') !== false;
if ($eigenerRahmen) {
    $klassen .= ' v-abschnitt--rahmen';
}

$hatBild = $bildUrl !== '' && $bildStil !== 'kein';
if ($hatBild) {
    $klassen .= ' v-abschnitt--bild-' . h($bildStil);
}

/**
 * Das Bild. Steht zweimal im Markup? Nein — einmal, an der Stelle, die
 * zur gewählten Anordnung gehört. Bei „oben“ vor der Überschrift, sonst
 * danach, damit es beim Umfließen neben dem Text sitzt und nicht neben
 * der Überschrift.
 */
$bildAusgeben = function () use ($bildUrl, $bildBreite, $bildHoehe, $bildText) {
    ?>
    <figure class="v-abschnitt__bild">
        <img src="<?= h($bildUrl) ?>"
             <?= $bildBreite > 0 ? 'width="' . (int) $bildBreite . '"' : '' ?>
             <?= $bildHoehe > 0 ? 'height="' . (int) $bildHoehe . '"' : '' ?>
             alt="<?= h($bildText) ?>"
             loading="lazy">
        <?php if (trim($bildText) !== '') { ?>
            <figcaption><?= h($bildText) ?></figcaption>
        <?php } ?>
    </figure>
    <?php
};
?>
<section class="<?= $klassen ?>"<?= $ankerName !== '' ? ' id="' . h($ankerName) . '"' : '' ?>>

    <?php if ($hatBild && $bildStil === 'oben') { $bildAusgeben(); } ?>


    <?php
    /* Steht die Überschrift im eigenen Feld statt im Fließtext, kommt
       das Ikon hier hinein — dort ist es dann die erste Überschrift
       des Abschnitts. */
    if ($ebene !== 'keine' && trim($ueberschriftText) !== '') { ?>
        <<?= $ebene ?> class="mit-strich"><?= $ikonImFeld ? $ikonHtml : '' ?><?= h($ueberschriftText) ?></<?= $ebene ?>>
    <?php } ?>

    <?php
    /* Ein Abschnitt ganz ohne Überschrift. Dann steht das Ikon für
       sich über dem Text — und bekommt denselben Rahmen wie der,
       sonst stünde es am äußersten Seitenrand. */
    if ($ikonAllein) { ?>
        <?php if ($eigenerRahmen) { ?><div class="container"><?php } ?>
        <?= $ikonHtml ?>
        <?php if ($eigenerRahmen) { ?></div><?php } ?>
    <?php } ?>

    <?php if ($hatBild && ($bildStil === 'links' || $bildStil === 'rechts')) { $bildAusgeben(); } ?>

    <?= $textHtml ?>

    <?php if ($hatBild && $bildStil === 'unten') { $bildAusgeben(); } ?>
</section>
