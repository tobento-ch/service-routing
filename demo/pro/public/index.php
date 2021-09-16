<?php
declare(strict_types=1);

namespace Tobento\Demo\Routing\Pro;

error_reporting( -1 );
ini_set('display_errors', '1');

require __DIR__ . '/../../../vendor/autoload.php';

use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

// Defines: adjust depending on your setup.
define('URL_SRC', 'http://localhost/path/demo/pro/public/src/');
define('URL_BASE', 'http://localhost/path/demo/pro/public');
define('BASE_URI', '/path/demo/pro/public/');
define('SECRET_KEY', 'a-random-32-character-secret-key');

// Get container with implemented services.
$container = require __DIR__ . '/../app/container.php';

$router = $container->get(RouterInterface::class);

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

// Example resource
$router->resource('products', ProductsResource::class)
       ->parameter('index', 'mainmenu', 'Products');

// Handle middleware:
$middlewareDispatcher = $container->get(MiddlewareDispatcherInterface::class);

$middlewareDispatcher->add(
    \Tobento\Demo\Routing\Pro\Middleware\ErrorHandler::class,
    \Tobento\Service\Routing\Middleware\MethodOverride::class,
    \Tobento\Service\Routing\Middleware\PreRouting::class,
    \Tobento\Service\Routing\Middleware\Routing::class,
);

$request = $container->get(ServerRequestInterface::class);

$response = $middlewareDispatcher->handle($request);

// emitting response.
(new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);