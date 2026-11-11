<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

// If the follwing class is already provided, we are likely in a composer-based Concrete installation
if (!class_exists(Illuminate\Container\Container::class)) {
    // Otherwise, let's try to load composer ourselves
    if (!@include DIR_BASE_CORE . '/' . DIRNAME_VENDOR . '/autoload.php') {
        echo 'Third party libraries not installed. Make sure that composer has required libraries in the concrete/ directory.';
        exit(1);
    }
}

/*
 * Eigene Klassen unter Application\EinfachBdP\… in
 * application/src/EinfachBdP/… — ganz normales PSR-4.
 *
 * Concretes eigener Autoloader kennt unterhalb von Application\ nur
 * feste Fächer (Controller\, Block\, Theme\, Job\ …) und den alten
 * Application\Src\-Namensraum. Für Fachlogik, die in keines dieser
 * Fächer gehört, ist das nichts. Deshalb hier eine eigene, eng
 * begrenzte Registrierung: Sie greift ausschließlich für den Präfix
 * Application\EinfachBdP\ und lässt alles andere unangetastet.
 */
spl_autoload_register(static function (string $klasse): void {
    $praefix = 'Application\\EinfachBdP\\';

    if (strncmp($klasse, $praefix, strlen($praefix)) !== 0) {
        return;
    }

    $rest = substr($klasse, strlen($praefix));
    $datei = DIR_APPLICATION . '/' . DIRNAME_CLASSES . '/EinfachBdP/'
        . str_replace('\\', '/', $rest) . '.php';

    if (is_file($datei)) {
        require $datei;
    }
});
