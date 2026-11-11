<?php

namespace Concrete\Package\EinfachBdp\Block\BdPKonto;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use EinfachBdP\Girocode;

/**
 * Die Bankverbindung des Stammes.
 *
 * Vorher stand sie als handgeschriebenes HTML im Abschnittsblock —
 * eine Definitionsliste, ein <dialog>, ein fest eingebautes Bild.
 * Wer die IBAN ändern wollte, musste sie an drei Stellen im Quelltext
 * finden und durfte den GiroCode dabei nicht vergessen, der als
 * fertige SVG im Theme lag. Genau das ist die Sorte Aufgabe, bei der
 * am Ende die halbe Seite eine alte und die andere Hälfte eine neue
 * Kontonummer zeigt.
 *
 * Jetzt gibt es je ein Feld, und der GiroCode wird daraus gerechnet.
 * Er kann deshalb nicht mehr veralten, und das ist kein Schönheits-
 * fehler: Ein QR-Code sieht falsch genauso aus wie richtig, und wer
 * ihn scannt, überweist auf das Konto, das darin steht.
 *
 * Kopieren
 * --------
 * Empfänger, IBAN und BIC lassen sich im Fenster einzeln kopieren.
 * IBAN und BIC ohne Leerzeichen, wie es die Überweisungsformulare
 * erwarten; der Empfängername im Wortlaut.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPKonto';
    protected $btInterfaceWidth = 700;
    protected $btInterfaceHeight = 600;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Kontodaten');
    }

    public function getBlockTypeDescription()
    {
        return t('Bankverbindung mit GiroCode zum Abscannen. Empfänger, IBAN und BIC lassen sich kopieren.');
    }

    public function add()
    {
        $this->set('ueberschrift', t('Kontodaten'));
        $this->set('einleitung', t('Für Beiträge, Lagerbeiträge und Spenden. Wofür die Überweisung ist, gehört in den Verwendungszweck.'));
        $this->set('bank', '');
        $this->set('empfaenger', '');
        $this->set('iban', '');
        $this->set('bic', '');
        $this->set('zweckHinweis', '');
        $this->set('zeigeCode', 1);
    }

    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('javascript', 'einfach_bdp/konto');
    }

    public function view()
    {
        $this->set('codeSvg', $this->zeigeCode
            ? Girocode::svg($this->empfaenger, $this->iban, $this->bic)
            : '');
    }

    public function getSearchableContent()
    {
        return $this->ueberschrift . ' ' . $this->bank . ' ' . $this->empfaenger;
    }

    /**
     * Für die Maske: Warum ein Code nicht erzeugt werden konnte. So
     * steht die Redaktion nicht vor einem leeren Feld ohne Grund.
     */
    public function getCodeFehler()
    {
        if (trim((string) $this->empfaenger) === '' || trim((string) $this->iban) === '') {
            return t('Empfänger und IBAN fehlen noch.');
        }
        if (Girocode::nutzlast($this->empfaenger, $this->iban, $this->bic) === '') {
            return t('IBAN oder BIC sehen nicht nach gültigen Werten aus, oder der Empfänger ist länger als 70 Zeichen.');
        }

        return '';
    }

    public function save($daten)
    {
        /* IBAN und BIC aufgeräumt ablegen: Großbuchstaben, keine
           Sonderzeichen. Angezeigt werden sie später wieder in
           Vierergruppen — gespeichert gehören sie ohne. */
        $iban = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($daten['iban'] ?? '')));
        $bic = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($daten['bic'] ?? '')));

        parent::save([
            'ueberschrift' => trim((string) ($daten['ueberschrift'] ?? '')),
            'einleitung' => trim((string) ($daten['einleitung'] ?? '')),
            'bank' => trim((string) ($daten['bank'] ?? '')),
            'empfaenger' => trim(preg_replace('/\s+/u', ' ', (string) ($daten['empfaenger'] ?? ''))),
            'iban' => $iban,
            'bic' => $bic,
            'zweckHinweis' => trim((string) ($daten['zweckHinweis'] ?? '')),
            'zeigeCode' => empty($daten['zeigeCode']) ? 0 : 1,
        ]);
    }
}
