<?php

namespace Application\EinfachBdP\Telefon;

use Concrete\Core\Config\Repository\Repository;
use Symfony\Component\HttpFoundation\Session\Session;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Das Rechen-Captcha vor den Telefonnummern.
 *
 * Der ganze Zustand liegt in der Sitzung auf dem Server: die Lösung, der
 * Zeitpunkt, die Zahl der Fehlversuche, die Freigabe. Der Browser bekommt
 * nur die Frage in Worten. Ein manipulierter Client kann also nichts
 * erzwingen — er kann nur raten, und Raten ist begrenzt.
 *
 * Warum eine Rechenaufgabe in Worten und kein Bild-Captcha:
 * Bilder sind für blinde Menschen unbedienbar, Audio-Alternativen sind
 * aufwendig und schlecht. Eine ausgeschriebene Aufgabe („sieben plus
 * drei“) ist mit jedem Screenreader lösbar und für ein Sammelskript
 * trotzdem Arbeit — es muss deutsche Zahlwörter kennen. Sie ist keine
 * Mauer, sondern eine Hürde; genau so ist sie gemeint.
 */
class Captcha
{
    /** Schlüssel des Sitzungseintrags. */
    const SESSION_KEY = 'bdp_telefon';

    /** @var Session */
    protected $session;

    /** @var Repository */
    protected $config;

    /** @var IpDrossel */
    protected $drossel;

    /**
     * Deutsche Zahlwörter. Nur bis 24 — weiter reicht keine Aufgabe,
     * und ab „einundzwanzig“ wird es beim Tippen ohnehin fehleranfällig.
     */
    protected static $woerter = [
        0 => 'null', 1 => 'eins', 2 => 'zwei', 3 => 'drei', 4 => 'vier',
        5 => 'fünf', 6 => 'sechs', 7 => 'sieben', 8 => 'acht', 9 => 'neun',
        10 => 'zehn', 11 => 'elf', 12 => 'zwölf', 13 => 'dreizehn',
        14 => 'vierzehn', 15 => 'fünfzehn', 16 => 'sechzehn',
        17 => 'siebzehn', 18 => 'achtzehn', 19 => 'neunzehn',
        20 => 'zwanzig', 21 => 'einundzwanzig', 22 => 'zweiundzwanzig',
        23 => 'dreiundzwanzig', 24 => 'vierundzwanzig',
    ];

    public function __construct(Session $session, Repository $config, IpDrossel $drossel)
    {
        $this->session = $session;
        $this->config = $config;
        $this->drossel = $drossel;
    }

    /* ==============================================================
       Öffentliche Schnittstelle
       ============================================================== */

    /**
     * Stellt eine neue Aufgabe und merkt sich die Lösung in der Sitzung.
     *
     * @return array{ok:bool,frage?:string,gueltig?:int,meldung?:string,sperre?:int}
     */
    public function aufgabeStellen($ip)
    {
        $stand = $this->stand();

        if ($this->istGesperrt($stand)) {
            return $this->gesperrt($stand);
        }

        if ($this->drossel->istGesperrt($ip, (int) $this->wert('ip_versuche_max', 20), (int) $this->wert('ip_fenster', 3600))) {
            return [
                'ok' => false,
                'grund' => 'gesperrt',
                'meldung' => 'Von dieser Verbindung kamen zu viele Fehlversuche. '
                    . 'Bitte später noch einmal — oder schreib uns einfach eine E-Mail.',
            ];
        }

        $aufgabe = $this->wuerfeln();

        // Eine neue Aufgabe setzt den Versuchszähler NICHT zurück. Sonst
        // reichte ein Klick auf „neue Aufgabe“, um beliebig weiterzuraten.
        $stand['loesung'] = $aufgabe['loesung'];
        $stand['gestellt'] = microtime(true);
        $this->stand($stand);

        return [
            'ok' => true,
            'frage' => $aufgabe['frage'],
            'gueltig' => (int) $this->wert('aufgabe_gueltig', 300),
            'versuche_frei' => max(0, (int) $this->wert('versuche_max', 4) - (int) $stand['versuche']),
        ];
    }

    /**
     * Prüft eine Antwort. Bei Erfolg wird die Freigabe in der Sitzung
     * vermerkt; die Nummern selbst liefert erst der Endpunkt.
     *
     * @return array{ok:bool,meldung?:string,grund?:string}
     */
    public function pruefen($antwort, $ip)
    {
        $stand = $this->stand();

        if ($this->istGesperrt($stand)) {
            return $this->gesperrt($stand);
        }

        $maxIp = (int) $this->wert('ip_versuche_max', 20);
        $fenster = (int) $this->wert('ip_fenster', 3600);
        if ($this->drossel->istGesperrt($ip, $maxIp, $fenster)) {
            return [
                'ok' => false,
                'grund' => 'gesperrt',
                'meldung' => 'Von dieser Verbindung kamen zu viele Fehlversuche. '
                    . 'Bitte später noch einmal — oder schreib uns einfach eine E-Mail.',
            ];
        }

        if ($stand['loesung'] === null) {
            return [
                'ok' => false,
                'grund' => 'keine_aufgabe',
                'meldung' => 'Es liegt gerade keine Aufgabe vor. Bitte hol dir eine neue.',
            ];
        }

        $alter = microtime(true) - (float) $stand['gestellt'];

        if ($alter > (float) $this->wert('aufgabe_gueltig', 300)) {
            $stand['loesung'] = null;
            $this->stand($stand);

            return [
                'ok' => false,
                'grund' => 'abgelaufen',
                'meldung' => 'Die Aufgabe ist abgelaufen. Hier ist eine neue.',
            ];
        }

        // Zu schnell geantwortet: kein Mensch liest, denkt und tippt in
        // unter anderthalb Sekunden. Zählt als Fehlversuch.
        if ($alter < (float) $this->wert('mindestzeit', 1.5)) {
            return $this->fehlversuch($stand, $ip, $fenster,
                'Das ging sehr schnell. Bitte versuch es noch einmal.');
        }

        $zahl = $this->antwortLesen($antwort);

        if ($zahl === null || $zahl !== (int) $stand['loesung']) {
            return $this->fehlversuch($stand, $ip, $fenster,
                'Das stimmt leider nicht. Hier ist eine neue Aufgabe.');
        }

        // Richtig: Aufgabe verbrauchen, Freigabe setzen, Zähler leeren.
        $stand['loesung'] = null;
        $stand['gestellt'] = 0.0;
        $stand['versuche'] = 0;
        $stand['frei_bis'] = time() + (int) $this->wert('freigabe_gueltig', 600);
        $this->stand($stand);

        return ['ok' => true];
    }

    /**
     * Ist in dieser Sitzung gerade eine gültige Freigabe vorhanden?
     * Das ist die einzige Stelle, die über die Herausgabe entscheidet.
     */
    public function istFrei()
    {
        $stand = $this->stand();

        return $stand['frei_bis'] !== null && $stand['frei_bis'] > time();
    }

    /**
     * Restlaufzeit der Freigabe in Sekunden (0, wenn keine besteht).
     */
    public function freigabeRest()
    {
        $stand = $this->stand();

        return $stand['frei_bis'] === null ? 0 : max(0, $stand['frei_bis'] - time());
    }

    /* ==============================================================
       Aufgabe würfeln
       ============================================================== */

    /**
     * @return array{frage:string,loesung:int}
     */
    protected function wuerfeln()
    {
        $art = random_int(1, 10);

        if ($art <= 5) {
            // plus — der Normalfall
            $a = random_int(2, 12);
            $b = random_int(2, 9);
            $zeichen = 'plus';
            $loesung = $a + $b;
        } elseif ($art <= 8) {
            // minus — nie negativ, nie null
            $a = random_int(6, 16);
            $b = random_int(2, $a - 2);
            $zeichen = 'minus';
            $loesung = $a - $b;
        } else {
            // mal — kleines Einmaleins, Ergebnis bleibt unter 25
            $a = random_int(2, 6);
            $b = random_int(2, 4);
            $zeichen = 'mal';
            $loesung = $a * $b;
        }

        $vorlagen = [
            'Was ergibt %s %s %s?',
            'Wie viel ist %s %s %s?',
            'Bitte rechne: %s %s %s',
        ];
        $vorlage = $vorlagen[random_int(0, count($vorlagen) - 1)];

        return [
            'frage' => sprintf($vorlage, self::$woerter[$a], $zeichen, self::$woerter[$b]),
            'loesung' => $loesung,
        ];
    }

    /**
     * Nimmt Ziffern („12“) genauso an wie Zahlwörter („zwölf“).
     * Menschen tippen mal so, mal so; das darf nicht durchfallen.
     *
     * @return int|null null = unverständlich
     */
    protected function antwortLesen($antwort)
    {
        if (!is_scalar($antwort)) {
            return null;
        }

        $roh = trim((string) $antwort);

        if ($roh === '' || mb_strlen($roh) > 30) {
            return null;
        }

        if (preg_match('/^\d{1,3}$/', $roh)) {
            return (int) $roh;
        }

        $wort = mb_strtolower($roh, 'UTF-8');
        $wort = str_replace(
            ['ä', 'ö', 'ü', 'ß', ' ', '-', '.'],
            ['ae', 'oe', 'ue', 'ss', '', '', ''],
            $wort
        );

        foreach (self::$woerter as $zahl => $name) {
            $vergleich = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);
            if ($wort === $vergleich) {
                return $zahl;
            }
        }

        // „ein“ und „eine“ meinen dasselbe wie „eins“
        if (in_array($wort, ['ein', 'eine', 'einer'], true)) {
            return 1;
        }

        return null;
    }

    /* ==============================================================
       Zustand, Sperren, Fehlversuche
       ============================================================== */

    /**
     * Liest (ohne Argument) oder schreibt (mit Argument) den Zustand.
     *
     * @param array|null $neu
     *
     * @return array
     */
    protected function stand(array $neu = null)
    {
        if ($neu !== null) {
            $this->session->set(self::SESSION_KEY, $neu);

            return $neu;
        }

        $stand = $this->session->get(self::SESSION_KEY);

        if (!is_array($stand)) {
            $stand = [];
        }

        return $stand + [
            'loesung' => null,
            'gestellt' => 0.0,
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
        $rest = (int) ceil(max(0, $stand['sperre_bis'] - time()) / 60);

        return [
            'ok' => false,
            'grund' => 'gesperrt',
            'meldung' => 'Zu viele Fehlversuche. Bitte versuch es in etwa '
                . max(1, $rest) . ' Minuten noch einmal — oder schreib uns eine E-Mail.',
            'sperre' => max(0, (int) $stand['sperre_bis'] - time()),
        ];
    }

    /**
     * Ein Fehlversuch: Zähler hoch, Aufgabe verbrannt, ggf. sperren.
     */
    protected function fehlversuch(array $stand, $ip, $fenster, $meldung)
    {
        $stand['versuche'] = (int) $stand['versuche'] + 1;
        $stand['loesung'] = null;   // eine verbrauchte Aufgabe kommt nie wieder
        $stand['gestellt'] = 0.0;
        $stand['frei_bis'] = null;  // ein Fehlversuch kassiert auch eine alte Freigabe

        $this->drossel->zaehlen($ip, (int) $fenster);

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
        $wert = $this->config->get('bdp_telefon.captcha.' . $name);

        return $wert === null ? $ersatz : $wert;
    }
}
