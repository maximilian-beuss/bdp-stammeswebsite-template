<?php

namespace Application\EinfachBdP\Telefon;

use Concrete\Core\Config\Repository\Repository;
use Symfony\Component\HttpFoundation\Session\Session;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Cloudflare Turnstile vor den Telefonnummern.
 *
 * Ablauf: Der Browser lädt das Turnstile-Widget, löst es und bekommt von
 * Cloudflare ein Token. Dieses Token schickt er an /telefon/pruefen.
 * Erst hier, auf dem Server, wird es gegen Cloudflares siteverify geprüft.
 * Fällt die Prüfung positiv aus, merkt sich die Sitzung eine Freigabe und
 * die Nummern gehen raus.
 *
 * Entscheidend ist, dass die Prüfung serverseitig läuft: Ein Token, das
 * der Client vorlegt, ist wertlos, solange Cloudflare es nicht bestätigt.
 * Ein manipulierter Client kann sich die Freigabe also nicht selbst geben.
 *
 * Jedes Token ist bei Cloudflare genau einmal einlösbar. Wer dasselbe
 * Token zweimal schickt, bekommt beim zweiten Mal ein Nein.
 *
 * Zusätzlich bleibt die IP-Drossel aus der Vorgängerlösung aktiv. Sie
 * fängt den Fall ab, dass jemand mit gültigen Token um sich wirft, etwa
 * über einen Löserdienst.
 */
class Turnstile
{
    /** Schlüssel des Sitzungseintrags. */
    const SESSION_KEY = 'bdp_telefon';

    /** Cloudflares Prüfadresse. */
    const SITEVERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /** @var Session */
    protected $session;

    /** @var Repository */
    protected $config;

    /** @var IpDrossel */
    protected $drossel;

    /**
     * Schlüssel und Sitzungsbereich können von außen gesetzt werden.
     *
     * Hintergrund: Seit die Telefonnummern in einem Block stehen, gehören
     * auch die Turnstile-Schlüssel dorthin — die Redaktion soll sie ohne
     * Dateizugriff eintragen können. Die Konfigurationsdatei bleibt als
     * Rückfallebene bestehen.
     *
     * Der Sitzungsbereich trennt mehrere Telefonblöcke voneinander. Ohne
     * ihn würde eine Freigabe auf der Kontaktseite auch die Nummern eines
     * anderen Blocks öffnen.
     *
     * @var string|null
     */
    protected $sitekeyGesetzt;

    /** @var string|null */
    protected $secretGesetzt;

    /** @var string */
    protected $bereich = '';

    /** @var int|null Gültigkeit einer Freigabe in Sekunden */
    protected $freigabeDauer;

    public function __construct(Session $session, Repository $config, IpDrossel $drossel)
    {
        $this->session = $session;
        $this->config = $config;
        $this->drossel = $drossel;
    }

    /**
     * Schlüsselpaar dieses Blocks. Leere Werte bedeuten „nimm die
     * Konfigurationsdatei“.
     */
    public function setSchluessel($sitekey, $secret)
    {
        $sitekey = trim((string) $sitekey);
        $secret = trim((string) $secret);

        $this->sitekeyGesetzt = $sitekey !== '' ? $sitekey : null;
        $this->secretGesetzt = $secret !== '' ? $secret : null;

        return $this;
    }

    /**
     * Trennt die Freigaben mehrerer Blöcke.
     */
    public function setBereich($bereich)
    {
        $this->bereich = (string) $bereich;

        return $this;
    }

    /**
     * Wie lange eine bestandene Prüfung gilt. 0 oder weniger heißt
     * „nimm den Wert aus der Konfigurationsdatei“.
     */
    public function setFreigabeDauer($sekunden)
    {
        $sekunden = (int) $sekunden;
        $this->freigabeDauer = $sekunden > 0 ? $sekunden : null;

        return $this;
    }

    protected function sitzungsschluessel()
    {
        return $this->bereich === '' ? self::SESSION_KEY : self::SESSION_KEY . '_' . $this->bereich;
    }

    /* ==============================================================
       Öffentliche Schnittstelle
       ============================================================== */

    /**
     * Der öffentliche Sitekey. Der darf im Quelltext stehen, dafür ist
     * er gemacht. Der Secret Key darf das nie.
     */
    public function sitekey()
    {
        return $this->sitekeyGesetzt !== null
            ? $this->sitekeyGesetzt
            : (string) $this->config->get('bdp_telefon.turnstile.sitekey', '');
    }

    /**
     * Ist überhaupt ein Schlüsselpaar hinterlegt?
     */
    public function einsatzbereit()
    {
        return $this->sitekey() !== '' && $this->secret() !== '';
    }

    /**
     * Prüft das Token bei Cloudflare und schaltet bei Erfolg frei.
     *
     * @param string $token Das Token aus dem Turnstile-Widget
     * @param string $ip    Client-IP, geht als optionaler Hinweis mit
     *
     * @return array{ok:bool,grund?:string,meldung?:string,sperre?:int}
     */
    public function pruefen($token, $ip)
    {
        $stand = $this->stand();

        if ($this->istGesperrt($stand)) {
            return $this->gesperrt($stand);
        }

        $fenster = (int) $this->wert('ip_fenster', 3600);
        if ($this->drossel->istGesperrt($ip, (int) $this->wert('ip_versuche_max', 20), $fenster)) {
            return [
                'ok' => false,
                'grund' => 'gesperrt',
                'meldung' => 'Von dieser Verbindung kamen zu viele Versuche. Bitte später noch einmal.',
                'sperre' => $fenster,
            ];
        }

        if (!$this->einsatzbereit()) {
            return [
                'ok' => false,
                'grund' => 'unkonfiguriert',
                'meldung' => 'Das Captcha ist auf diesem Server noch nicht eingerichtet.',
            ];
        }

        $token = is_string($token) ? trim($token) : '';

        if ($token === '') {
            return $this->fehlversuch($stand, $ip, $fenster, 'Bitte löse zuerst das Captcha.');
        }

        // Cloudflare deckelt die Tokenlänge bei 2048 Zeichen. Was länger
        // ist, ist keins — dann sparen wir uns die Anfrage.
        if (strlen($token) > 2048) {
            return $this->fehlversuch($stand, $ip, $fenster, 'Das Captcha wurde nicht angenommen.');
        }

        $ergebnis = $this->beiCloudflareNachfragen($token, $ip);

        if ($ergebnis === null) {
            // Cloudflare nicht erreichbar. Das ist kein Fehlversuch der
            // Besucherin, also wird auch nichts hochgezählt.
            return [
                'ok' => false,
                'grund' => 'dienst',
                'meldung' => 'Die Prüfung war gerade nicht erreichbar. Bitte versuch es gleich noch einmal.',
            ];
        }

        if (empty($ergebnis['success'])) {
            return $this->fehlversuch($stand, $ip, $fenster, 'Das Captcha wurde nicht angenommen.');
        }

        // Bestanden: Freigabe in die Sitzung, Zähler zurücksetzen.
        $stand['versuche'] = 0;
        $stand['frei_bis'] = time() + (int) $this->wert('freigabe_gueltig', 600);
        $this->stand($stand);

        return ['ok' => true];
    }

    public function istFrei()
    {
        $stand = $this->stand();

        return $stand['frei_bis'] !== null && $stand['frei_bis'] > time();
    }

    public function freigabeRest()
    {
        $stand = $this->stand();

        return $stand['frei_bis'] === null ? 0 : max(0, $stand['frei_bis'] - time());
    }

    /* ==============================================================
       Cloudflare
       ============================================================== */

    /**
     * Fragt siteverify. Gibt das dekodierte Ergebnis zurück, oder null,
     * wenn der Dienst nicht erreichbar war.
     *
     * @return array|null
     */
    protected function beiCloudflareNachfragen($token, $ip)
    {
        $daten = [
            'secret' => $this->secret(),
            'response' => $token,
        ];

        if ($ip) {
            $daten['remoteip'] = $ip;
        }

        $rohdaten = http_build_query($daten);

        if (function_exists('curl_init')) {
            $ch = curl_init(self::SITEVERIFY);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $rohdaten,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $antwort = curl_exec($ch);
            $fehler = curl_errno($ch);
            curl_close($ch);

            if ($fehler || $antwort === false) {
                return null;
            }
        } else {
            $kontext = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $rohdaten,
                    'timeout' => 8,
                    'ignore_errors' => true,
                ],
            ]);
            $antwort = @file_get_contents(self::SITEVERIFY, false, $kontext);

            if ($antwort === false) {
                return null;
            }
        }

        $ergebnis = json_decode($antwort, true);

        return is_array($ergebnis) ? $ergebnis : null;
    }

    protected function secret()
    {
        return $this->secretGesetzt !== null
            ? $this->secretGesetzt
            : (string) $this->config->get('bdp_telefon.turnstile.secret', '');
    }

    /* ==============================================================
       Sitzungszustand
       ============================================================== */

    protected function stand(array $neu = null)
    {
        if ($neu !== null) {
            $this->session->set($this->sitzungsschluessel(), $neu);

            return $neu;
        }

        $stand = $this->session->get($this->sitzungsschluessel());

        if (!is_array($stand)) {
            $stand = [];
        }

        return $stand + [
            'versuche' => 0,
            'frei_bis' => null,
            'sperre_bis' => null,
        ];
    }

    protected function istGesperrt(array $stand)
    {
        return $stand['sperre_bis'] !== null && $stand['sperre_bis'] > time();
    }

    protected function gesperrt(array $stand)
    {
        return [
            'ok' => false,
            'grund' => 'gesperrt',
            'meldung' => 'Zu viele Versuche. Bitte später noch einmal.',
            'sperre' => max(0, $stand['sperre_bis'] - time()),
        ];
    }

    /**
     * Zählt einen Fehlversuch, sperrt bei Bedarf.
     */
    protected function fehlversuch(array $stand, $ip, $fenster, $meldung)
    {
        $this->drossel->zaehlen($ip, $fenster);

        $stand['versuche'] = (int) $stand['versuche'] + 1;
        $max = (int) $this->wert('versuche_max', 4);

        if ($stand['versuche'] >= $max) {
            $stand['sperre_bis'] = time() + (int) $this->wert('sperre', 900);
            $stand['versuche'] = 0;
            $this->stand($stand);

            return $this->gesperrt($stand);
        }

        $this->stand($stand);

        return [
            'ok' => false,
            'grund' => 'falsch',
            'meldung' => $meldung,
            'versuche_frei' => max(0, $max - $stand['versuche']),
        ];
    }

    protected function wert($name, $ersatz)
    {
        if ($name === 'freigabe_gueltig' && $this->freigabeDauer !== null) {
            return $this->freigabeDauer;
        }

        $wert = $this->config->get('bdp_telefon.captcha.' . $name, $ersatz);

        return $wert === null ? $ersatz : $wert;
    }
}
