<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Url;
?>

<div class="ccm-dashboard-content-full">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title"><?= t('Stamm') ?></h3>
                    <p><?= t('Stammesname, Ort, Logo und Links in der Topbar einstellen.') ?></p>
                    <a class="btn btn-primary" href="<?= (string) Url::to('/dashboard/einfach_bdp/stamm') ?>">
                        <?= t('Öffnen') ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title"><?= t('Termine') ?></h3>
                    <p><?= t('Termine des Stammeskalenders anlegen, ändern und löschen. Außerdem Feiertage und Schulferien.') ?></p>
                    <a class="btn btn-primary" href="<?= (string) Url::to('/dashboard/einfach_bdp/termine') ?>">
                        <?= t('Öffnen') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
