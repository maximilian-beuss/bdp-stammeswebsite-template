<?php

namespace Concrete\Package\EinfachBdp\Block\BdPTelefon;

defined('C5_EXECUTE') or die('Access Denied.');

use Application\EinfachBdP\Telefon\IpDrossel;
use Application\EinfachBdP\Telefon\Turnstile;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Support\Facade\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use EinfachBdP\Fliesstext;
use EinfachBdP\Werkzeug;

/**
 * Telefonnummern hinter Cloudflare Turnstile.
 *
 * Die Nummern stehen in der Blocktabelle und werden erst ausgeliefert,
 * nachdem der Server ein Turnstile-Token bei Cloudflare hat bestätigen
 * lassen. Im HTML der Seite steht keine einzige Ziffer davon — dort
 * würfelt das Skript Platzhalter.
 *
 * Warum Block-Aktionen statt fester Routen
 * ----------------------------------------
 * Eine Aktion (view.php ruft $view->action('pruefen')) bringt die
 * Block-ID in der Adresse mit, und Concrete lädt vor dem Aufruf den
 * Datensatz dieses Blocks. Der Endpunkt weiß dadurch von selbst, welche
 * Nummern und welcher Secret Key gemeint sind. Mit einer festen Route
 * müsste die Block-ID als Parameter mitkommen und geprüft werden.
 *
 * Die Prüflogik selbst liegt weiter in Application\EinfachBdP\Telefon:
 * sie ist unabhängig davon, woher die Schlüssel kommen.
 */
class Controller extends BlockController
{
    protected $btTable = 'btBdPTelefon';
    protected $btInterfaceWidth = 760;
    protected $btInterfaceHeight = 640;
    protected $btCacheBlockRecord = true;
    // Niemals zwischenspeichern: die Ausgabe hängt am Freigabezustand
    // der Sitzung.
    protected $btCacheBlockOutput = false;
    protected $btDefaultSet = 'einfach_bdp';

    public function getBlockTypeName()
    {
        return t('Telefonnummern');
    }

    public function getBlockTypeDescription()
    {
        return t('Telefonnummern, die erst nach einem gelösten Captcha erscheinen. Die Nummern stehen nie im Quelltext.');
    }

    public function add()
    {
        $this->set('hinweis', 'Löse das Captcha, um Telefonnummern anzuzeigen');
        $this->set('ersatzMail', 'info@musterstamm.de');
        $this->set('freigabeMinuten', 10);
        $this->set('nummernListe', []);
        $this->set('sechseckAktiviert', 0);
    }

    public function edit()
    {
        $this->set('nummernListe', Werkzeug::liste($this->nummern));
        $this->set('sechseckAktiviert', (int) $this->sechseckAktiviert);
    }

    public function view()
    {
        $eintraege = [];
        foreach (Werkzeug::liste($this->nummern) as $n) {
            $name = trim(Werkzeug::feld($n, 'name'));
            if ($name === '') {
                continue;
            }
            /*
               Ins HTML gehen Name, Amt — und die Vorwahl.

               Die Vorwahl allein ist keine Telefonnummer: „+49“ steht
               auf jeder deutschen Nummer. Sichtbar ist sie trotzdem
               nützlich, weil das Kästchen dann aussieht wie eine
               Nummer und nicht wie ein Ladebalken.

               Die Zahl der verdeckten Stellen kommt mit, damit die
               Platzhalter die richtige Länge haben. Auch das verrät
               nichts: Deutsche Mobilnummern sind ohnehin alle etwa
               gleich lang.
            */
            $roh = trim(Werkzeug::feld($n, 'nummer'));
            $gruppen = preg_split('/\s+/', $roh, 2);
            $vorwahl = isset($gruppen[0]) ? $gruppen[0] : '';
            $rest = isset($gruppen[1]) ? $gruppen[1] : '';

            $eintraege[] = [
                'name' => $name,
                'rolle' => trim(Werkzeug::feld($n, 'rolle')),
                'vorwahl' => $vorwahl,
                'stellen' => max(3, min(14, strlen(preg_replace('/\D/', '', $rest)))),
            ];
        }

        $wache = $this->wache();

        $this->set('eintraege', $eintraege);
        $this->set('sitekey', $wache->sitekey());
        $this->set('bereit', $wache->einsatzbereit());
        $this->set('textVorHtml', Fliesstext::zuHtml($this->textVor));
        $this->set('textNachHtml', Fliesstext::zuHtml($this->textNach));
        $this->set('sechseckAktiviert', (int) $this->sechseckAktiviert);
    }

    /* ==============================================================
       Aktionen
       ============================================================== */

    /**
     * Der Browser holt sich Sitekey und Freigabestand.
     */
    public function action_konfig()
    {
        $wache = $this->wache();

        return new JsonResponse([
            'ok' => true,
            'sitekey' => $wache->sitekey(),
            'bereit' => $wache->einsatzbereit(),
            'frei' => $wache->istFrei(),
            'rest' => $wache->freigabeRest(),
        ]);
    }

    /**
     * Turnstile-Token einlösen.
     */
    public function action_pruefen()
    {
        $anfrage = Request::createFromGlobals();

        if (!$anfrage->isMethod('POST')) {
            return new JsonResponse(['ok' => false, 'grund' => 'methode'], 405);
        }

        $token = $anfrage->request->get('token');
        if ($token === null) {
            $token = $anfrage->request->get('cf-turnstile-response');
        }

        $ergebnis = $this->wache()->pruefen($token, $anfrage->getClientIp());

        if (empty($ergebnis['ok'])) {
            // 503 nur, wenn Cloudflare selbst nicht erreichbar war —
            // das ist kein Fehler der Besucherin und darf im Skript
            // anders behandelt werden als ein abgelehntes Token.
            $code = ($ergebnis['grund'] ?? '') === 'dienst' ? 503 : 403;

            return new JsonResponse($ergebnis, $code);
        }

        return new JsonResponse([
            'ok' => true,
            'nummern' => $this->nummernAusgeben(),
        ]);
    }

    /**
     * Nummern für eine bereits freigeschaltete Sitzung.
     */
    public function action_nummern()
    {
        $wache = $this->wache();

        if (!$wache->istFrei()) {
            return new JsonResponse(['ok' => false, 'grund' => 'gesperrt'], 403);
        }

        return new JsonResponse([
            'ok' => true,
            'rest' => $wache->freigabeRest(),
            'nummern' => $this->nummernAusgeben(),
        ]);
    }

    protected function nummernAusgeben()
    {
        $raus = [];

        foreach (Werkzeug::liste($this->nummern) as $n) {
            $name = trim(Werkzeug::feld($n, 'name'));
            $nummer = trim(Werkzeug::feld($n, 'nummer'));
            if ($name === '' || $nummer === '') {
                continue;
            }

            $raus[] = [
                'name' => $name,
                'rolle' => trim(Werkzeug::feld($n, 'rolle')),
                'nummer' => $nummer,
                // Für den tel:-Link: alles außer Ziffern und führendem
                // Plus muss raus, sonst wählen manche Geräte falsch.
                'waehlbar' => preg_replace('/(?!^\+)[^0-9]/', '', $nummer),
            ];
        }

        return $raus;
    }

    /**
     * Die Prüfinstanz, mit den Schlüsseln dieses Blocks bestückt.
     */
    protected function wache()
    {
        $app = Application::getFacadeApplication();

        $wache = new Turnstile(
            $app->make('session'),
            $app->make('config'),
            new IpDrossel()
        );

        return $wache
            ->setSchluessel($this->sitekey, $this->secret)
            ->setBereich((string) $this->bID)
            ->setFreigabeDauer(60 * (int) $this->freigabeMinuten);
    }

    public function save($daten)
    {
        $mail = trim((string) ($daten['ersatzMail'] ?? ''));
        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $mail = '';
        }

        parent::save([
            'nummern' => Werkzeug::listeSpeichern($daten['nummern'] ?? [], ['name', 'nummer']),
            'sitekey' => trim((string) ($daten['sitekey'] ?? '')),
            'secret' => trim((string) ($daten['secret'] ?? '')),
            'textVor' => trim((string) ($daten['textVor'] ?? '')),
            'textNach' => trim((string) ($daten['textNach'] ?? '')),
            'hinweis' => trim((string) ($daten['hinweis'] ?? '')),
            'ersatzMail' => $mail,
            'freigabeMinuten' => max(1, min(1440, (int) ($daten['freigabeMinuten'] ?? 10))),
            'sechseckAktiviert' => empty($daten['sechseckAktiviert']) ? 0 : 1,
        ]);
    }

    /**
     * Die Nummern dürfen nicht in den Suchindex. Sonst stünden sie in
     * der Volltextsuche wieder im Klartext da.
     */
    public function getSearchableContent()
    {
        return '';
    }
}
