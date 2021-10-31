<?php
declare(strict_types=1);

namespace Tobento\Demo\Routing\Pro;

use Tobento\Service\Container\Container;
use Tobento\Service\View\View;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\Dir\Dir;
use Tobento\Service\View\Data;
use Tobento\Service\View\Assets;
use Tobento\Service\Menu\Menus;
use Tobento\Service\Menu\MenusInterface;
use Tobento\Service\Routing\Router;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RouteResponseParser;
use Tobento\Service\Dater\Dater;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;
use Tobento\Service\Middleware\MiddlewareDispatcher;
use Tobento\Service\Middleware\AutowiringMiddlewareFactory;
use Tobento\Service\Middleware\FallbackHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;

$container = new Container();

$container->set(RouterInterface::class, function($container) {
    
    $request = $container->get(ServerRequestInterface::class);
    
    $router = new Router(
        new RequestData(
            $request->getMethod(),
            (string)$request->getUri()->withScheme('')->withUserInfo('')->withHost(''),
            $request->getUri()->getHost()
        ),
        new UrlGenerator(URL_BASE, SECRET_KEY),
        new RouteFactory(),
        new RouteDispatcher($container, new Constrainer()),
        new RouteHandler($container),
        new MatchedRouteHandler($container),
        new RouteResponseParser(),
    );
    
    $router->setBaseUri(BASE_URI);
    
    return $router;
});

$container->set(ServerRequestInterface::class, function($container) {
    
    $psr17Factory = new Psr17Factory();

    $creator = new ServerRequestCreator(
        $psr17Factory, // ServerRequestFactory
        $psr17Factory, // UriFactory
        $psr17Factory, // UploadedFileFactory
        $psr17Factory  // StreamFactory
    );

    return $creator->fromGlobals();
});

$container->set(ResponseFactoryInterface::class, function($container) { 
    return new Psr17Factory();
});

$container->set(MiddlewareDispatcherInterface::class, function($container) {
    
    return new MiddlewareDispatcher(
        new FallbackHandler((new Psr17Factory())->createResponse(200)),
        new AutowiringMiddlewareFactory($container)
    );
});

$container->set(ViewInterface::class, function($container) {
        
    $view = new View(
        new PhpRenderer(
            new Dirs(
                new Dir(__DIR__.'/../views/')
            )
        ),
        new Data(),
        new Assets(__DIR__.'/src/', URL_SRC)
    );
    
    $router = $container->get(RouterInterface::class);
    
    $view->macro('url', [$router, 'url']);
    
    $view->macro('now', function() {
        return new Dater();
    });
    
    $matchedRoute = $router->getMatchedRoute();
        
    $view->with('routeName', $matchedRoute?->getName() ?: '');
    $view->with('locale', $matchedRoute?->getParameter('locale') ?: 'en');
    $view->with('menus', $container->get(MenusInterface::class));
        
    return $view;
});

// Menus
$container->set(MenusInterface::class, function($container) {
    
    $router = $container->get(RouterInterface::class);
    $matchedRoute = $router->getMatchedRoute();
    $locale = $matchedRoute?->getParameter('locale') ?: 'en';
    
    $menus = new Menus();
    
    // generate menu items based on routes.
    foreach($router->getRoutes() as $route)
    {
        if (
            $route->getName()
            && $route->hasParameter('mainmenu')
        ) {
            $name = $route->getParameter('mainmenu');
            
            if (is_array($name)) {
                $name = $name[$locale] ?? null; 
            }
            
            if (is_null($name) || !is_string($name)) {
                $name = ucfirst($route->getName());
            }
            
            try {
                $menus->menu('main')
                      ->link($router->url($route->getName())->locale($locale)->get(), $name)
                      ->id($route->getName());
            } catch (UrlException $e) {
                //
            }   
        }
    }
            
    $menus->menu('main')->link($router->url('article.show', ['slug' => 'invalid-article']), 'Not Found')->id('notfound');
    
    // handle translated urls:
    if (is_null($matchedRoute)) {
        return $menus;
    }

    try {
        $translated = $router->url($matchedRoute->getName())->translated();
    } catch (UrlException $e) {
        $translated = [];
    }
    
    foreach($translated as $locale => $url)
    {
        $menus->menu('locales')->link($url, ucwords($locale))->id($locale);
    }    
    
    return $menus;
});

return $container;