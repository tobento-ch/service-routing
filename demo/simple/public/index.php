<?php
declare(strict_types=1);

namespace Tobento\Demo\Routing\Simple;

error_reporting( -1 );
ini_set('display_errors', '1');

require __DIR__ . '/../../../vendor/autoload.php';

use Tobento\Service\Routing\Router;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RouteResponseParser;
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Routing\TranslationException;
use Tobento\Service\View\ViewInterface;

// Defines: adjust depending on your setup.
define('URL_SRC', 'http://localhost/path/demo/simple/public/src/');
define('URL_BASE', 'http://localhost/path/demo/simple/public');
define('BASE_URI', '/path/demo/simple/public/');
define('SECRET_KEY', 'a-random-32-character-secret-key');

// Get container with implemented services such as View and Menus.
$container = require __DIR__ . '/../app/container.php';

// Create Router.
$router = new Router(
    new RequestData(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        rawurldecode($_SERVER['REQUEST_URI'] ?? ''),
        'localhost'
    ),
    new UrlGenerator(URL_BASE, SECRET_KEY),
    new RouteFactory(),
    new RouteDispatcher($container, new Constrainer()),
    new RouteHandler($container),
    new MatchedRouteHandler($container),
    new RouteResponseParser(),
);

$router->setBaseUri(BASE_URI);

// For autowiring.
$container->set(RouterInterface::class, $router);

// Define Routes:  
$router->route('GET', '', function(ViewInterface $view) {
    return $view->render('article', ['title' => 'Home', 'description' => 'Home Description']);
})->name('home')
  ->parameter('mainmenu', null);

// Example with translation:
$router->route('GET', '{?locale}/{articles}', [ArticleController::class, 'home'])
       ->name('articles')
       ->locales(['en', 'de'])
       ->localeOmit('en')
       ->trans('articles', ['en' => 'articles', 'de' => 'beitraege'])
       ->parameter('mainmenu', ['en' => 'Articles', 'de' => 'Beiträge']);

// Example with matches method and localization:
// We could also have put this route at the end,
// but if we have multiple {slug} routes, then we could handle it this way with matches method.
$router->route('GET', '{?locale}/{slug}', [ArticleController::class, 'show'])
       ->matches(function(RouterInterface $router, ArticleRepository $repo, RouteInterface $route) {
           
           $requestParams = $route->getParameter('request_parameters');
           $locale = $requestParams['locale'];
           $slug = $requestParams['slug'];
           
           $article = $repo->getBySlug($slug, $locale);
           
           if (is_null($article)) {
               return null;
           }
           
           // add trans for matched route url translated:
           $route->trans('slug', $repo->getSlugsById($article->id()));
           
           return $route;
       })
       ->name('article.show')
       ->locales(['en', 'de'])
       ->localeOmit('en');

// Example signed route.
$router->route('GET', 'unsubscribe', function(ViewInterface $view) {
    return $view->render('article', ['title' => 'Unsubscribe', 'description' => 'This is a signed route']);
})->signed('unsubscribe');

$router->route('GET', 'signed-urls', function(ViewInterface $view) {
    // Look in views/signed_urls.php for url generation demo.
    return $view->render('signed_urls', ['title' => 'Signed Urls']);
})->name('signed.urls')
  ->parameter('mainmenu', 'Signed Urls');

// Handle the matched route.
try {
    $matchedRoute = $router->dispatch();
} catch (RouteNotFoundException $e) {
    header('HTTP/1.1 404 Not Found');
    $view = $container->get(ViewInterface::class);
    echo $view->render('article', ['title' => '404', 'description' => 'Page not found!']);
    exit;
} catch (InvalidSignatureException $e) {
    header('HTTP/1.1 404 Not Found');
    $view = $container->get(ViewInterface::class);
    echo $view->render('article', ['title' => '404', 'description' => 'Page has expired or is invalid!']);
    exit;
} catch (TranslationException $e) {
    header('HTTP/1.1 404 Not Found');
    $view = $container->get(ViewInterface::class);
    echo $view->render('article', ['title' => '404', 'description' => 'Page not found!']);
    exit;
}

// Call matched route handler for handling registered matched event actions.
$router->getMatchedRouteHandler()->handle($matchedRoute);

// handle the matched route.
$routeResponse = $router->getRouteHandler()
                        ->handle($matchedRoute);

if (is_string($routeResponse)) {
    echo $routeResponse;
} else {
    $view = $container->get(ViewInterface::class);
    echo $view->render('article', ['title' => '404', 'description' => 'Page not found!']);    
}