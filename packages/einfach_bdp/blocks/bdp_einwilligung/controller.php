<?php

namespace Concrete\Package\EinfachBdp\Block\BdPEinwilligung;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;

/**
 * Zeigt, was der Besucher erlaubt hat, und nimmt es zurück.
 *
 * Eine Einwilligung muss so leicht zu widerrufen sein, wie sie zu
 * erteilen war (Art. 7 Abs. 3 DSGVO). Ein Satz „lösche deinen
 * Browserspeicher“ erfüllt das nicht — es braucht einen Knopf.
 *
 * Der Block gehört auf die Seite der Datenschutzerklärung. Er kennt
 * die Einwilligungen nicht selbst: Was es gibt, steht in
 * js/einwilligung.js, und der Block liefert nur die Hülle, die das
 * Skript füllt. So gibt es eine Stelle, an der eine neue Einbettung
 * einzutragen ist, und nicht zwei.
 *
 * Ohne JavaScript bleibt der Kasten leer — dann konnte aber auch
 * keine Einwilligung erteilt werden, denn die Einbettungen laden
 * ebenfalls nur mit Skript ✅ es gibt nichts zu widerrufen.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPEinwilligung';
    protected $btInterfaceWidth = 640;
    protected $btInterfaceHeight = 420;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Einwilligungen');
    }

    public function getBlockTypeDescription()
    {
        return t('Zeigt dem Besucher, welchen externen Inhalten er zugestimmt hat, und nimmt die Zustimmung zurück. Gehört auf die Datenschutzseite.');
    }

    public function add()
    {
        $this->set('ueberschrift', t('Deine Einwilligungen'));
        $this->set('text', t('Hier siehst du, was du auf diesem Gerät erlaubt hast. Du kannst es jederzeit zurücknehmen — danach fragt die Website wieder nach, bevor sie etwas nachlädt.'));
    }

    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('javascript', 'einfach_bdp/einwilligung');
    }

    public function getSearchableContent()
    {
        return $this->ueberschrift . ' ' . strip_tags((string) $this->text);
    }

    public function save($daten)
    {
        parent::save([
            'ueberschrift' => trim((string) ($daten['ueberschrift'] ?? '')),
            'text' => trim((string) ($daten['text'] ?? '')),
        ]);
    }
}
