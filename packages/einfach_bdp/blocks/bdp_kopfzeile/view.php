<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Page\Page;

$c = Page::getCurrentPage();
$istBearbeitungsModus = is_object($c) && !$c->isError() && $c->isEditMode();
?>

<?php if ($istBearbeitungsModus) { ?>
<div style="background:#1a4480;color:#fff;padding:6px 12px;font-size:13px;display:flex;align-items:center;gap:8px;">
    <span>&#9998;</span>
    <span><?= t('Stammesname, Logo und Topbar-Links:') ?></span>
    <a href="/index.php/dashboard/einfach_bdp/stamm"
       style="color:#ffd700;text-decoration:underline;"
       target="_blank"><?= t('Dashboard → Stamm') ?></a>
</div>
<?php } ?>

<?php if (!empty($topbarLinksListe) || !empty($topbarRechtsListe)) { ?>
<div class="v-topbar">
    <div class="container">
        <div class="v-topbar__innen">
            <div class="v-topbar__gruppe v-topbar__gruppe--links">
                <?php foreach ($topbarLinksListe as $link) {
                    $rel = !empty($link['extern']) ? ' rel="noopener external"' : '';
                    echo '<a href="' . h($link['href']) . '"' . $rel . '>' . h($link['text']) . '</a>';
                } ?>
            </div>
            <div class="v-topbar__gruppe v-topbar__gruppe--rechts">
                <?php foreach ($topbarRechtsListe as $link) {
                    $rel = !empty($link['extern']) ? ' rel="noopener external"' : '';
                    echo '<a href="' . h($link['href']) . '"' . $rel . '>' . h($link['text']) . '</a>';
                } ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<a class="v-logo" href="<?= View::url('/') ?>">
    <img src="<?= h($logoUrl) ?>"
         alt="<?= h($logoAltText) ?>"
         width="45" height="52">
    <span class="v-logo__text">
        <span class="v-logo__name"><?= h($stammesname) ?></span>
        <?php if (trim($ort) !== '') { ?>
            <span class="v-logo__zusatz"><?= h($ort) ?></span>
        <?php } ?>
    </span>
</a>
