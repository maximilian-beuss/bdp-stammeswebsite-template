<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var array  $zeilenListe
 * @var string $stufeHandle
 * @var string $stufeFarbe
 * @var string $stufeName
 */

if (count($zeilenListe) === 0) {
    if (isset($c) && is_object($c) && $c->isEditMode()) {
        echo '<p class="v-eckdaten__leer">' . t('Noch keine Eckdaten. Block bearbeiten.') . '</p>';
    }

    return;
}
?>
<?php
/* Die Stufenfarbe kommt als Variable ins Element und nicht als Klasse.

   Grund: Sie steht ohnehin schon einmal zentral in Stufen::katalog(),
   und eine zweite Liste im CSS ginge irgendwann auseinander. So gibt
   es genau eine Stelle, an der die Farben stehen. */
?>
<dl class="v-eckdaten v-eckdaten--<?= h($stufeHandle) ?>" style="--stufe:<?= h($stufeFarbe) ?>">
    <?php foreach ($zeilenListe as $z) { ?>
        <div class="v-eckdaten__zeile">
            <dt><?= h($z['bezeichnung']) ?></dt>
            <dd><?= h($z['wert']) ?></dd>
        </div>
    <?php } ?>
</dl>
