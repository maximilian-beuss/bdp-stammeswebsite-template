<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var string $videoUrl
 * @var string $videoTyp
 * @var string $posterUrl
 * @var array  $spurenListe
 * @var string $titel
 * @var string $text
 * @var string $breite
 * @var string $vorladen
 * @var int    $schleife
 * @var string $farbAkzent
 * @var string $farbAkzentSymbol
 * @var string $farbKnopf
 * @var string $farbKnopfSymbol
 */

if ($videoUrl === '') {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-video__leer">' . t('Noch kein Video gewählt. Block bearbeiten und eine Videodatei auswählen.') . '</p>';
    }

    return;
}

$titel = trim((string) $titel);
$text = trim((string) $text);
$hatText = $text !== '';

/* Die Beschriftung des Spielers. Ein <video> ohne Namen meldet sich
   bei Screenreadern als „Video“ und sonst nichts — bei zwei Videos auf
   einer Seite weiß dann niemand, welches welches ist ✅ WCAG 4.1.2 */
$name = $titel !== '' ? $titel : t('Video');

/* Die gewählten Farben als Custom Properties am Block. Vier Werte
   statt zwei: Zu jeder Fläche gehört die Farbe, die darauf lesbar
   bleibt, und die rechnet der Controller aus.

   Inline und nicht im Stylesheet, weil sie zu diesem einen Block
   gehören — zwei Videos auf einer Seite dürfen verschieden aussehen. */
$farben = [
    '--v-akzent: ' . $farbAkzent,
    '--v-akzent-symbol: ' . $farbAkzentSymbol,
    '--v-knopf: ' . $farbKnopf,
    '--v-knopf-symbol: ' . $farbKnopfSymbol,
];
?>
<figure class="v-video v-video--<?= h($breite) ?>" style="<?= h(implode('; ', $farben)) ?>">

    <?php
    /* Die Bühne hält das Seitenverhältnis, solange Plyr noch nicht
       gestartet ist. Ohne sie springt der Text unter dem Video nach
       unten, sobald der Spieler steht. */
    ?>
    <div class="v-video__buehne">
        <video class="v-video__spieler"
               data-v-video
               controls
               playsinline
               preload="<?= h($vorladen) ?>"
               aria-label="<?= h($name) ?>"
               <?= $schleife ? 'loop muted' : '' ?>
               <?= $posterUrl !== '' ? 'poster="' . h($posterUrl) . '"' : '' ?>>

            <source src="<?= h($videoUrl) ?>" type="<?= h($videoTyp) ?>">

            <?php foreach ($spurenListe as $s) { ?>
                <track kind="captions"
                       src="<?= h($s['url']) ?>"
                       srclang="<?= h($s['sprache']) ?>"
                       label="<?= h($s['beschriftung']) ?>"
                       <?= $s['standard'] ? 'default' : '' ?>>
            <?php } ?>

            <?php
            /* Der letzte Ausweg: Ein Browser, der gar kein <video> kann,
               zeigt diesen Inhalt. Ein Link auf die Datei ist mehr wert
               als der Satz „Ihr Browser unterstützt kein Video“. */
            ?>
            <p class="v-video__notnagel">
                <?= t('Dein Browser kann dieses Video nicht abspielen.') ?>
                <a href="<?= h($videoUrl) ?>" download><?= t('Video herunterladen') ?></a>
            </p>
        </video>
    </div>

    <?php if ($hatText) { ?>
        <figcaption class="v-video__text"><?= nl2br(h($text)) ?></figcaption>
    <?php } ?>
</figure>
