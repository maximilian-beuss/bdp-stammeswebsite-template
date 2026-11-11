<?php
defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var string $titelText
 * @var string $untertitelText
 * @var string $bildUrl
 * @var array  $krumenListe
 * @var string $stil
 */

$hatBild = $stil === 'bild' && $bildUrl !== '';
?>
<div class="v-seitenkopf auf-dunkel<?= $hatBild ? ' v-seitenkopf--bild' : '' ?>">
    <?php if ($hatBild) { ?>
        <img class="v-seitenkopf__bild" src="<?= h($bildUrl) ?>" alt="" loading="eager">
    <?php } ?>

    <?php // Ein Anschnitt, am Rand — dasselbe Element wie im Hero ✅ CD ?>
    <span class="v-keil" aria-hidden="true"></span>

    <div class="container">
        <div class="v-seitenkopf__innen">

            <?php if (count($krumenListe) > 0) { ?>
                <?php // Der Rest der Seite duzt, die Brotkrumen tun es auch. ?>
                <nav class="v-krumen" aria-label="Wo du gerade bist">
                    <ol>
                        <?php foreach ($krumenListe as $k) { ?>
                            <li<?= $k['hier'] ? ' aria-current="page"' : '' ?>>
                                <?php if ($k['hier']) { ?>
                                    <?= h($k['name']) ?>
                                <?php } else { ?>
                                    <a href="<?= h($k['link']) ?>"><?= h($k['name']) ?></a>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ol>
                </nav>
            <?php } ?>

            <?php if (trim($titelText) !== '') { ?>
                <h1><?= h($titelText) ?></h1>
            <?php } ?>

            <?php if (trim($untertitelText) !== '') { ?>
                <p class="lead lesebreite"><?= h($untertitelText) ?></p>
            <?php } ?>

        </div>
    </div>
</div>
