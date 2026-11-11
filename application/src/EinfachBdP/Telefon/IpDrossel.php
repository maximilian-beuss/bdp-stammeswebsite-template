<?php

namespace Application\EinfachBdP\Telefon;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Fehlversuchs-Zähler je IP-Adresse.
 *
 * Warum zusätzlich zur Sitzung: Die Sitzung hängt an einem Cookie. Wer
 * durchprobieren will, wirft das Cookie einfach weg und fängt bei null
 * an — die Sperre in der Sitzung wäre wertlos. Erst der Zähler je
 * Absender macht das Raten teuer.
 *
 * Ablage bewusst als eine kleine JSON-Datei im Temp-Verzeichnis und
 * nicht im Concrete-Cache: Der Cache ist im Entwicklungsmodus
 * abgeschaltet (BlackHole), dann zählte gar nichts mehr, ohne dass es
 * auffiele. Beim Leeren des Caches wären die Zähler außerdem weg —
 * genau das würde ein Angreifer nutzen, wenn er könnte.
 *
 * Die IP wird nur als Hash gespeichert, mit dem Installationspfad als
 * Salz. Es steht also nirgends eine Besucher-IP im Klartext auf Platte.
 * Alte Einträge fliegen bei jedem Schreibvorgang raus.
 */
class IpDrossel
{
    /** @var string|null */
    protected $datei;

    /**
     * @return string|null null = kein beschreibbarer Ort gefunden
     */
    protected function datei()
    {
        if ($this->datei !== null) {
            return $this->datei ?: null;
        }

        $ordner = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'bdp-telefon';

        if (!is_dir($ordner) && !@mkdir($ordner, 0700, true) && !is_dir($ordner)) {
            $this->datei = '';

            return null;
        }

        // Der Installationspfad im Namen trennt mehrere Installationen
        // auf demselben Server sauber voneinander.
        $this->datei = $ordner . DIRECTORY_SEPARATOR
            . 'drossel-' . substr(sha1(defined('DIR_BASE') ? DIR_BASE : __DIR__), 0, 16) . '.json';

        return $this->datei;
    }

    protected function schluessel($ip)
    {
        return substr(hash_hmac('sha256', (string) $ip, 'bdp-telefon:' . (defined('DIR_BASE') ? DIR_BASE : __DIR__)), 0, 24);
    }

    /**
     * Öffnet die Ablage und ruft $arbeit mit dem entpackten Stand auf.
     * Gibt $arbeit ein Array zurück, wird es geschrieben.
     *
     * Die Sperre liegt auf der Datei selbst (LOCK_EX), damit zwei
     * gleichzeitige Anfragen sich nicht gegenseitig überschreiben.
     *
     * @return mixed Rückgabe von $arbeit
     */
    protected function mitAblage(callable $arbeit)
    {
        $datei = $this->datei();

        if ($datei === null) {
            // Kein Ort zum Schreiben: die Sitzungssperre greift trotzdem.
            $ergebnis = $arbeit([]);

            return isset($ergebnis['wert']) ? $ergebnis['wert'] : null;
        }

        $griff = @fopen($datei, 'c+');

        if (!$griff) {
            $ergebnis = $arbeit([]);

            return isset($ergebnis['wert']) ? $ergebnis['wert'] : null;
        }

        try {
            if (!flock($griff, LOCK_EX)) {
                $ergebnis = $arbeit([]);

                return isset($ergebnis['wert']) ? $ergebnis['wert'] : null;
            }

            $roh = stream_get_contents($griff);
            $stand = $roh === '' ? [] : json_decode($roh, true);

            if (!is_array($stand)) {
                $stand = [];
            }

            $ergebnis = $arbeit($stand);

            if (is_array($ergebnis) && isset($ergebnis['schreiben'])) {
                ftruncate($griff, 0);
                rewind($griff);
                fwrite($griff, json_encode($ergebnis['schreiben']));
                fflush($griff);
            }

            return isset($ergebnis['wert']) ? $ergebnis['wert'] : null;
        } finally {
            flock($griff, LOCK_UN);
            fclose($griff);
        }
    }

    /**
     * Zählt einen Fehlversuch für diese IP.
     */
    public function zaehlen($ip, $fenster)
    {
        $schluessel = $this->schluessel($ip);
        $jetzt = time();
        $fenster = max(60, (int) $fenster);

        $this->mitAblage(function (array $stand) use ($schluessel, $jetzt, $fenster) {
            $stand = $this->aufraeumen($stand, $jetzt);

            if (!isset($stand[$schluessel]) || !is_array($stand[$schluessel])) {
                $stand[$schluessel] = ['n' => 0, 'bis' => $jetzt + $fenster];
            }

            $stand[$schluessel]['n'] = (int) $stand[$schluessel]['n'] + 1;
            $stand[$schluessel]['bis'] = max((int) $stand[$schluessel]['bis'], $jetzt + $fenster);

            return ['schreiben' => $stand, 'wert' => true];
        });
    }

    /**
     * Hat diese IP das Kontingent im laufenden Fenster verbraucht?
     */
    public function istGesperrt($ip, $max, $fenster)
    {
        if ((int) $max <= 0) {
            return false;
        }

        $schluessel = $this->schluessel($ip);
        $jetzt = time();

        $anzahl = $this->mitAblage(function (array $stand) use ($schluessel, $jetzt) {
            if (!isset($stand[$schluessel]) || !is_array($stand[$schluessel])) {
                return ['wert' => 0];
            }

            if ((int) $stand[$schluessel]['bis'] <= $jetzt) {
                return ['wert' => 0];
            }

            return ['wert' => (int) $stand[$schluessel]['n']];
        });

        return (int) $anzahl >= (int) $max;
    }

    /**
     * Wirft abgelaufene Einträge weg. Hält die Datei klein, ohne dass
     * jemand aufräumen müsste.
     */
    protected function aufraeumen(array $stand, $jetzt)
    {
        foreach ($stand as $schluessel => $eintrag) {
            if (!is_array($eintrag) || (int) $eintrag['bis'] <= $jetzt) {
                unset($stand[$schluessel]);
            }
        }

        return $stand;
    }
}
