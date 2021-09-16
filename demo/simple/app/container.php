<?php
declare(strict_types=1);

namespace Tobento\Demo\Routing\Simple;

use Tobento\Service\Container\Container;
use Tobento\Service\View\View;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\Dirs;
use Tobento\Service\View\Dir;
use Tobento\Service\View\Data;
use Tobento\Service\View\Assets;
use Tobento\Service\Menu\Menus;
use Tobento\Service\Menu\MenusInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\UrlException;
use Tobento\Service\Dater\Dater;

$container = new Container();

$container->set(ViewInterface::class, function($container) {
        
    $view = new View(
        new PhpRenderer(
            new Dirs([
                new Dir(__DIR__.'/../views/')
            ])
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