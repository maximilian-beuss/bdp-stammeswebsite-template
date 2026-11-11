<?php

namespace Application\EinfachBdP\Telefon;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Die drei Endpunkte hinter dem Telefon-Widget.
 *
 *   GET  /telefon/konfig    liefert den öffentlichen Turnstile-Sitekey
 *   POST /telefon/pruefen   prüft das Turnstile-Token, liefert bei Erfolg die Nummern
 *   GET  /telefon/nummern   liefert die Nummern, wenn die Sitzung frei ist
 *
 * Die Nummern verlassen den Server ausschließlich über pruefen() und
 * nummern(), und beide fragen vorher Turnstile::istFrei(). Es gibt keinen
 * zweiten Weg: Weder Seite noch Theme noch Block kennen die Nummern.
 *
 * Kein CSRF-Token, und das ist Absicht: Concretes Token hängt nicht an
 * der Sitzung, sondern an Nutzer-ID und Pepper — es würde hier nichts
 * absichern, was die Sitzungsbindung nicht schon absichert. Fremde
 * Seiten können die Antwort ohnehin nicht lesen, weil kein einziger
 * CORS-Kopf gesetzt wird.
 */
class Endpunkt
{
    /** @var Turnstile */
    protected $turnstile;

    /** @var Repository */
    protected $config;

    public function __construct(Turnstile $turnstile, Repository $config)
    {
        $this->turnstile = $turnstile;
        $this->config = $config;
    }

    /**
     * Was der Browser braucht, um das Widget zu bauen: den öffentlichen
     * Sitekey. Ist die Sitzung noch freigeschaltet, sagt die Antwort das
     * mit — dann muss niemand zweimal in zehn Minuten lösen.
     */
    public function konfig(Request $request)
    {
        return $this->antwort([
            'ok' => $this->turnstile->einsatzbereit(),
            'sitekey' => $this->turnstile->sitekey(),
            'frei' => $this->turnstile->istFrei(),
            'rest' => $this->turnstile->freigabeRest(),
        ]);
    }

    /**
     * Token prüfen. Nur hier und in nummern() werden Nummern herausgegeben.
     */
    public function pruefen(Request $request)
    {
        $token = $request->request->get('token');

        if ($token === null) {
            // Turnstile schickt das Feld unter diesem Namen, wenn das
            // Widget in einem Formular steckt.
            $token = $request->request->get('cf-turnstile-response');
        }

        $ergebnis = $this->turnstile->pruefen($token, $request->getClientIp());

        if (!$ergebnis['ok']) {
            $status = $ergebnis['grund'] === 'gesperrt' ? 429 : 403;

            if ($ergebnis['grund'] === 'dienst') {
                $status = 503;
            }

            return $this->antwort($ergebnis, $status);
        }

        return $this->antwort([
            'ok' => true,
            'rest' => $this->turnstile->freigabeRest(),
            'nummern' => $this->nummernListe(),
        ]);
    }

    /**
     * Die Nummern für eine bereits freigeschaltete Sitzung, etwa nach
     * einem Neuladen der Seite innerhalb der Freigabezeit.
     */
    public function nummern(Request $request)
    {
        if (!$this->turnstile->istFrei()) {
            return $this->antwort([
                'ok' => false,
                'grund' => 'gesperrt',
                'meldung' => 'Diese Sitzung ist nicht freigeschaltet.',
            ], 403);
        }

        return $this->antwort([
            'ok' => true,
            'rest' => $this->turnstile->freigabeRest(),
            'nummern' => $this->nummernListe(),
        ]);
    }

    /* ============================================================== */

    /**
     * Baut aus der Konfiguration die drei Formen, die der Browser braucht:
     * die Anzeige, das Ziel des tel:-Links und eine vorlesbare Fassung.
     */
    protected function nummernListe()
    {
        $liste = [];

        foreach ((array) $this->config->get('bdp_telefon.nummern', []) as $eintrag) {
            if (empty($eintrag['nummer'])) {
                continue;
            }

            $anzeige = trim($eintrag['nummer']);

            $liste[] = [
                'name' => isset($eintrag['name']) ? $eintrag['name'] : '',
                'rolle' => isset($eintrag['rolle']) ? $eintrag['rolle'] : '',
                'anzeige' => $anzeige,
                'tel' => $this->telForm($anzeige),
                'vorlesen' => $this->vorleseForm($anzeige),
            ];
        }

        return $liste;
    }

    /**
     * '+49 171 3920000' → '+491713920000'
     */
    protected function telForm($anzeige)
    {
        return preg_replace('/[^\d+]/', '', $anzeige);
    }

    /**
     * Vorlesefassung für den Screenreader.
     *
     * Ohne Aufbereitung liest NVDA „1023919“ als „eine Million
     * dreiundzwanzigtausendneunhundertneunzehn“ vor — als Telefonnummer
     * unbrauchbar. Deshalb Ziffer für Ziffer, das „+“ als Wort, und die
     * Gruppen durch Kommas getrennt, damit die Stimme dort eine Pause
     * macht. Die Gruppierung ist dieselbe, die auch zu sehen ist.
     */
    protected function vorleseForm($anzeige)
    {
        $gruppen = preg_split('/\s+/', trim($anzeige), -1, PREG_SPLIT_NO_EMPTY);
        $teile = [];

        foreach ($gruppen as $i => $gruppe) {
            $ziffern = preg_replace('/\D/', '', $gruppe);

            if ($ziffern === '') {
                continue;
            }

            $gesprochen = implode(' ', str_split($ziffern));

            if ($i === 0 && strpos($gruppe, '+') === 0) {
                $gesprochen = 'plus ' . $gesprochen;
            }

            $teile[] = $gesprochen;
        }

        return implode(', ', $teile);
    }

    /**
     * Immer JSON, immer unzwischenspeicherbar, nie in einem Index.
     */
    protected function antwort(array $daten, $status = 200)
    {
        $antwort = new JsonResponse($daten, $status);

        $antwort->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $antwort->headers->set('Pragma', 'no-cache');
        $antwort->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $antwort->headers->set('Referrer-Policy', 'same-origin');

        return $antwort;
    }
}
