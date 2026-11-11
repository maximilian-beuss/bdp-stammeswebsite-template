<?php
/* @var Concrete\Core\Application\Application $app */
/* @var Concrete\Core\Console\Application $console only set in CLI environment */

/*
 * ----------------------------------------------------------------------------
 * # Custom Application Handler
 *
 * You can do a lot of things in this file.
 *
 * ## Set a theme by route:
 *
 * $app->make('\Concrete\Core\Page\Theme\ThemeRouteCollection')
 * ->setThemeByRoute('/login', 'greek_yogurt');
 *
 *
 * ## Register a class override.
 *
 * $app->bind('helper/feed', function() {
 * 	 return new \Application\Core\CustomFeedHelper();
 * });
 *
 * $app->bind('\Concrete\Attribute\Boolean\Controller', function($app, $params) {
 * 	return new \Application\Attribute\Boolean\Controller($params[0]);
 * });
 *
 * ## Register Events.
 *
 * Events::addListener('on_page_view', function($event) {
 * 	$page = $event->getPageObject();
 * });
 *
 *
 * ## Register some custom MVC Routes
 *
 * Route::register('/test', function() {
 * 	print 'This is a contrived example.';
 * });
 *
 * Route::register('/custom/view', '\My\Custom\Controller::view');
 * Route::register('/custom/add', '\My\Custom\Controller::add');
 *
 * ## Pass some route parameters
 *
 * Route::register('/test/{foo}/{bar}', function($foo, $bar) {
 *  print 'Here is foo: ' . $foo . ' and bar: ' . $bar;
 * });
 *
 *
 * ## Override an Asset
 *
 * use \Concrete\Core\Asset\AssetList;
 * AssetList::getInstance()
 *     ->getAsset('javascript', 'jquery')
 *     ->setAssetURL('/path/to/new/jquery.js');
 *
 * or, override an asset by providing a newer version.
 *
 * use \Concrete\Core\Asset\AssetList;
 * use \Concrete\Core\Asset\Asset;
 * $al = AssetList::getInstance();
 * $al->register(
 *   'javascript', 'jquery', 'path/to/new/jquery.js',
 *   array('version' => '2.0', 'position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false)
 *   );
 *
 * ----------------------------------------------------------------------------
 */

/*
 * ----------------------------------------------------------------------------
 * Telefonnummern hinter Cloudflare Turnstile
 *
 * Die Nummern stehen in application/config/einfachBdP_telefon.php und
 * kommen NUR über diese Endpunkte heraus, nie im HTML einer Seite.
 * Das Turnstile-Token wird serverseitig gegen Cloudflares siteverify
 * geprüft, die Freigabe liegt in der Sitzung.
 * Logik: application/src/EinfachBdP/Telefon/.
 * ----------------------------------------------------------------------------
 */

/** @var Concrete\Core\Routing\Router $router */
$router = $app->make(Concrete\Core\Routing\RouterInterface::class);

$router->get('/telefon/konfig', '\Application\EinfachBdP\Telefon\Endpunkt::konfig');
$router->post('/telefon/pruefen', '\Application\EinfachBdP\Telefon\Endpunkt::pruefen');
$router->get('/telefon/nummern', '\Application\EinfachBdP\Telefon\Endpunkt::nummern');
