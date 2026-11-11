<?php

namespace Application\Theme\EinfachBdp;

use Concrete\Core\Feature\Features;
use Concrete\Core\Page\Theme\Theme;

/**
 * Theme "einfachBdP" — für BdP-Ortsgruppen.
 *
 * Baut auf dem BdP Corporate Design auf.
 * Farben, Schriften, Logo, Ikons und der Kohtenanschnitt sind vom CD
 * vorgegeben; Layout, Spacing und Interaktion sind abgeleitet und in
 * den Stylesheets mit 🔶 gekennzeichnet.
 */
class PageTheme extends Theme
{
    public function getThemeName()
    {
        return t('einfachBdP');
    }

    public function getThemeDescription()
    {
        return t('Theme für BdP-Ortsgruppen auf Basis des BdP Corporate Design: Pfadiblau und Wölflingsgelb, Kohtenanschnitte als Diagonalen, gestrichelte Linien, einfachBdP-Schriften lokal ausgeliefert, WCAG 2.2 AA.');
    }

    /**
     * Concrete erzeugt für Layout-Blöcke Bootstrap-5-Klassennamen.
     * css/grid.css bedient genau diese Klassen, ohne Bootstrap selbst
     * einzubinden.
     */
    public function getThemeGridFrameworkHandle()
    {
        return 'bootstrap5';
    }

    public function getThemeSupportedFeatures()
    {
        return [
            Features::ACCORDIONS,
            Features::ACCOUNT,
            Features::BASICS,
            Features::CALENDAR,
            Features::DOCUMENTS,
            Features::FAQ,
            Features::FORMS,
            Features::IMAGERY,
            Features::MAPS,
            Features::NAVIGATION,
            Features::PROFILE,
            Features::SEARCH,
            Features::SOCIAL,
            Features::TAXONOMY,
            Features::TESTIMONIALS,
            Features::TYPOGRAPHY,
            Features::VIDEO,
        ];
    }

    /**
     * Breakpoints des Themes — Concrete nutzt sie für responsive Bilder.
     */
    public function getThemeResponsiveImageMap()
    {
        return [
            'xl' => '1200px',
            'lg' => '992px',
            'md' => '768px',
            'sm' => '576px',
            'xs' => '0',
        ];
    }

    /**
     * Klassen, die der Redaktion im Rich-Text-Editor angeboten werden.
     */
    public function getThemeEditorClasses()
    {
        return [
            ['title' => t('Anleser'),            'element' => ['p'],                    'attributes' => ['class' => 'lead']],
            ['title' => t('Slogan'),             'element' => ['p', 'h2', 'h3'],        'attributes' => ['class' => 'slogan']],
            ['title' => t('Headline mit Strich'),'element' => ['h2', 'h3', 'h4'],       'attributes' => ['class' => 'mit-strich']],
            ['title' => t('Kleiner Text'),       'element' => ['p', 'span'],            'attributes' => ['class' => 'klein']],
            ['title' => t('Lesebreite'),         'element' => ['div', 'p'],             'attributes' => ['class' => 'lesebreite']],
            ['title' => t('Hinweiskasten'),      'element' => ['div', 'p'],             'attributes' => ['class' => 'v-hinweis']],
            ['title' => t('Hinweiskasten gelb'), 'element' => ['div', 'p'],             'attributes' => ['class' => 'v-hinweis v-hinweis--gelb']],
            ['title' => t('Etikett'),            'element' => ['span'],                 'attributes' => ['class' => 'v-etikett']],
            ['title' => t('Akzent (MARKS)'),     'element' => ['span', 'p', 'h4', 'h5', 'h6'], 'attributes' => ['class' => 'v-akzent']],
            ['title' => t('Button blau'),        'element' => ['a'],                    'attributes' => ['class' => 'btn']],
            ['title' => t('Button gelb'),        'element' => ['a'],                    'attributes' => ['class' => 'btn btn--gelb']],
            ['title' => t('Button mit Linie'),   'element' => ['a'],                    'attributes' => ['class' => 'btn btn--linie']],
            ['title' => t('Pfeil-Link'),         'element' => ['a'],                    'attributes' => ['class' => 'pfeil-link']],
            ['title' => t('Bild mit Info'),      'element' => ['figure'],               'attributes' => ['class' => 'v-bild']],
            ['title' => t('Bildreihe'),          'element' => ['div'],                  'attributes' => ['class' => 'v-bildreihe']],
            ['title' => t('Tabelle'),            'element' => ['table'],                'attributes' => ['class' => 'v-tabelle']],
        ];
    }

    /**
     * Klassen, die einzelne Bereiche im Bearbeitungsmodus anbieten.
     */
    public function getThemeAreaClasses()
    {
        return [
            'Main' => ['lesebreite'],
        ];
    }
}
