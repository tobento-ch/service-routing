# Routing Service

The Routing Service provides a flexible way to build routes for any PHP application.

## Table of Contents

- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Highlights](#highlights)
- [Documentation](#documentation)
    - [Router](#router)
    - [Basic Routing](#basic-routing)
        - [Routing Methods](#routing-methods)
        - [Uri Definitions](#uri-definitions)
        - [Handler Definitions](#handler-definitions)
        - [Parameters](#parameters)
        - [Url Generation](#url-generation)
        - [More Routes Methods](#more-routes-methods)
    - [Group Routing](#group-routing)
    - [Resource Routing](#resource-routing)
    - [Domain Routing](#domain-routing)
        - [Domain Routes](#domain-routes)
        - [Domain Url Generation](#domain-url-generation)
        - [Managing Domains](#managing-domains)
    - [Signed Routing](#signed-routing)
        - [Signed Routes](#signed-routes)
        - [Signed Url Generation](#signed-url-generation)
    - [Localization and Translation Routing](#localization-and-translation-routing)
        - [Localize Routes](#localize-routes)
        - [Translatable Routes](#translatable-routes)
    - [Matched Route Event](#matched-route-event)
    - [Constrainer](#constrainer)
    - [Dispatching Strategies](#dispatching-strategies)
        - [Simple](#simple)
        - [With PSR-7 Response](#with-psr-7-response)
        - [With PSR-15 Middleware](#with-psr-15-middleware)
- [Credits](#credits)
___

# Getting started

Add the latest version of the routing service running this command.

```
composer require tobento/service-routing
```

## Requirements

- PHP 8.0 or greater

## Highlights

- Basic routing (GET, POST, PUT, PATCH, UPDATE, DELETE)
- Domain routing
- Group routing
- Resource routing
- Named routes
- Matched route handling
- Url and signed url generation
- PSR-15 middleware support
- Localization
- Autowiring
- Framework-agnostic, will work with any project
- Decoupled design
- Easily extendable or customizable

# Documentation

## Router

```php
use Tobento\Service\Routing\Router;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RouteResponseParser;

// Any PSR-11 container
$container = new \Tobento\Service\Container\Container();

$router = new Router(
    new RequestData(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        rawurldecode($_SERVER['REQUEST_URI'] ?? ''),
        'example.com',
    ),
    new UrlGenerator(
        'https://example.com/basepath',
        'a-random-32-character-secret-signature-key',
    ),
    new RouteFactory(),
    new RouteDispatcher($container, new Constrainer()),
    new RouteHandler($container),
    new MatchedRouteHandler($container),
    new RouteResponseParser(),
);

$router->setBaseUri('/path/app/');
```

## Basic Routing

### Routing methods

```php
$router->get('blog', [Controller::class, 'method']);
$router->post('blog', [Controller::class, 'method']);
$router->put('blog', [Controller::class, 'method']);
$router->patch('blog', [Controller::class, 'method']);
$router->delete('blog', [Controller::class, 'method']);
$router->head('blog', [Controller::class, 'method']);
$router->options('blog', [Controller::class, 'method']);

// Route multiple
$router->route('GET|POST', 'blog', [Controller::class, 'method']);

// Route any
$router->route('*', 'blog', [Controller::class, 'method']);
```

### Uri definitions

```php
$router->get('blog/{slug}', 'Controller::method');

// you can define as many as you want:
$router->get('blog/{slug}/comment/{id}', 'Controller::method');

// using a question mark for optional parameters
$router->get('{?locale}/blog/{?id}', 'Controller::method');

// or using wildcard to allow any path
$router->get('blog/{path*}', 'Controller::method');
```

### Handler definitions

The default RouteHandler supports autowiring and the following handler definitions.

```php
// By providing class and method name:
$router->get('blog', [Controller::class, 'method']);

// Using Class::method syntax:
$router->get('blog', 'Controller::method');

// Using closure:
$router->get('blog', function() {
    return 'welcome';
});

// You might provide data for build-in method parameters:
$router->get('blog', [Controller::class, 'method', ['name' => 'value']]);
```

### Parameters

**Name a route:**

The main purpose for named routes is the generation of URLs. But they might be useful for any other cases too.

```php
$router->get('blog', 'Controller::method')->name('blog');
```

> :warning: Named routes should be unique, otherwise the route got overwritten.


**Adding middleware:** see also [With PSR-15 Middleware](#with-psr-15-middleware)

```php
$router->get('blog', 'Controller::method')
       ->middleware(Middleware::class, Another::Middleware);
```

**Where constraint parameter:** see also [Constrainer](#constrainer)

```php
$router->get('blog/{slug}', 'Controller::method')
       ->where('slug', '[a-z]+');

$router->get('{path*}', 'Controller::method')
       ->where('path', '[^?]+');
```

**Query constraint parameter:**

```php
// do not allow any uri query parameters:
$router->get('blog/{slug}', 'Controller::method')->query(null);

// allow only certain query characters:
$router->get('blog/{slug}', 'Controller::method')->query('/^[a-zA-Z0-9=&\/,\[\]-]+?$/');
```

**Domain:** see also [Domain Routing](#domain-routing)

```php
$router->get('blog', [Controller::class, 'method'])
       ->domain('sub.example.com');
```

**Signed:** see also [Signed Routing](#signed-routing)

```php
$router->get('blog', [Controller::class, 'method'])
       ->signed('blog', validate: false); // default validate: true
```

**Matches:**

```php
use Tobento\Service\Routing\RouteInterface;

$router->get('{slug}', 'BlogController::method')
       ->matches(function(SlugsRepo $slugs, RouteInterface $route): null|RouteInterface {
           // we would need call matches handler later on RouteDispatcher in order to have request data
           $slug = $slugs->find($route->getParameter('request_parameters')['slug']);
           
           if (!$slug || $slug->getResourceKey() !== 'blog') {
               return null;
           }
           
           $requestParams = $route->getParameter('request_parameters', []);
           $requestParams['id'] = $slug->getResourceId();
           unset($requestParams['slug']);
           $route->parameter('request_parameters', $requestParams);
           
           return $route;
       });
       
$router->get('{slug}', 'ProductsController::method')
       ->matches(function(SlugsRepo $slugs, RouteInterface $route): null|RouteInterface {
       
           $slug = $slugs->find($route->getParameter('request_parameters')['slug']);
           
           if (!$slug || $slug->getResourceKey() !== 'products') {
               return null;
           }
           
           $requestParams = $route->getParameter('request_parameters', []);
           $requestParams['id'] = $slug->getResourceId();
           unset($requestParams['slug']);
           $route->parameter('request_parameters', $requestParams);
           
           return $route;
       });       
```

**BaseUrl:**

```php
$router->get('blog', [Controller::class, 'method'])
       ->baseUrl('https:://sub.example.com/app');
```

**Adding custom parameters:**

```php
$router->get('blog', [Controller::class, 'method'])
       ->parameter('name', 'value');
```

### Url Generation

**Generating url from named routes:**

```php
$router->get('blog', [Controller::class, 'method'])
       ->name('blog');
       
$blogUrl = $router->url('blog')->get();
$blogUrl = (string) $router->url('blog');

// if your route uri has parameters:
$router->get('blog/edit/{id}', [Controller::class, 'method'])
       ->name('blog.edit');
       
$blogUrl = $router->url('blog.edit', ['id' => 5])->get();
```

### More Routes methods

**Custom Routes:**

```php
use Tobento\Service\Routing\RouteInterface;

// must implement RouteInterface
$router->addRoute(new CustomRoute());

$router->addRoutes([
    new CustomRoute(),
    new CustomRoute(),
]);
```

**Get All Routes:**

```php
use Tobento\Service\Routing\RouteInterface;

$routes = $router->getRoutes();
// returns: array<int|string, RouteInterface>
```

**Get Named Route:**

```php
use Tobento\Service\Routing\RouteInterface;

$route = $router->getRoute('name');
// returns: null|RouteInterface
```

**Get Matched Route:**

```php
use Tobento\Service\Routing\RouteInterface;

$matchedRoute = $router->getMatchedRoute();
// returns: null|RouteInterface
```

## Group Routing

You might use groups to share parameters across routes:

```php
use Tobento\Service\Routing\RouteGroupInterface;

$router->group('admin', function(RouteGroupInterface $group) {
    
    // supports any basic routing methods:
    $group->get('blog', [Controller::class, 'method'])->name('admin.blog');
    
    // resources:
    $group->resource('products', ProductsController::class);
    // The group uri 'admin' gets prepended to route names for resources only.
    // $router->getRoute('admin.products.index');
    // $router->url('admin.products.index');
    
    // group:
    $group->group('account', function(RouteGroupInterface $group) {
        // define routes.
    });
    
    // you might overwrite the group parameters by defining it:
    $group->get('blog', [Controller::class, 'method'])
          ->middleware(Middleware::class);

})->domain('sub.example.com')
  ->middleware(Middleware::class)
  ->baseUrl('sub.example.com')
  ->parameter('name', 'value')
  ->locale('de')
  ->locales(['de', 'en'])
  ->localeOmit('de')
  ->localeName('locale')
  ->localeFallbacks(['de' => 'en']);
```

If the group uri definition has parameters, they are available on the routes:

```php
use Tobento\Service\Routing\RouteGroupInterface;
  
$router->group('{locale}', function(RouteGroupInterface $group) {
    
    // locale is available too.
    $group->get('blog/{id}', function($locale, $id) {
        // do something
    });

})->where('locale', ':in:de:fr');
```

## Resource Routing

You may use resource routing for convenience:

```php
$router->resource('products', ProductsController::class);
```

This will produce the following routes:

| Method | Uri | Action / Controller method | Route name |
| --- | --- | --- | --- |
| GET | products | index | products.index |
| GET | products/create | create | products.create |
| POST | products | store | products.store |
| GET | products/{id} | show | products.show |
| GET | products/{id}/edit | edit | products.edit |
| PUT/PATCH | products/{id} | update | products.update |
| DELETE | products/{id} | delete | products.delete |

**You might route only specific actions:**

```php
$router->resource('products', ProductsController::class)
       ->only(['index', 'show']);

$router->resource('products', ProductsController::class)
       ->except(['delete']);
```

**You might change the default where constraint:**

```php
$router->resource('products', ProductsController::class)
       ->where('[a-z0-9]+'); // default is [0-9]+
```

**Adding new or overwriting existing actions:**

```php
// creating new action:
$router->resource('products', ProductsController::class)
       ->action(
           action: 'display', 
           method: 'GET', 
           uri: '/display/{id}',
           parameters: ['constraints' => ['id' => '[0-9]+']],
       );   
// GET, products/display/{id}, display, products.display

// overwriting index action:
$router->resource('products', ProductsController::class)
       ->action(
           action: 'index', 
           method: 'GET', 
           uri: '/index',
           parameters: [],
       );
// GET, products/index, index, products.index      
```

**Middleware:**

```php
$router->resource('products', ProductsController::class)
       ->middleware(
           ['show'], // empty array for all actions
           Middleware::class,
           AnotherMiddleware::class,
       );   
```

**Adding additional route parameters for an action:**

```php
$router->resource('products', ProductsController::class)
       ->parameter(
           action: 'index',
           name: 'foo',
           value: 'bar',
       );
```

**Adding additional route parameters for all actions:**

```php
$router->resource('products', ProductsController::class)
       ->sharedParameter(
           name: 'foo',
           value: 'bar',
       );
```

**With localization and translation:**

```php
$router->resource('{?locale}/{products}', ProductsResource::class)
    // specify the name as above we set the uri:
    ->name('products')
    
    // specify the locales:
    ->locales(['de', 'en'])
    ->localeOmit('en')
    ->localeFallbacks(['de' => 'en'])
    
    // specify the translations for the verbs:
    ->trans('create', ['de' => 'neu', 'en' => 'create'], action: 'create')
    ->trans('edit', ['de' => 'bearbeiten', 'en' => 'edit'], action: 'edit')
    
    // for products:
    ->trans('products', ['de' => 'produkte', 'en' => 'products']);
    
// Example with new action:
$router->resource('products', ProductsController::class)
    ->action(
        action: 'display', 
        method: 'GET', 
        uri: '/display/{id}', // set without {display}
        parameters: ['constraints' => ['id' => '[0-9]+']],
    )
    
    // specify the locales:
    ->locales(['de', 'en'])
    ->localeOmit('en')
    ->localeFallbacks(['de' => 'en'])
    
    // specify the translations for the display verb:
    ->trans('display', ['de' => 'ansicht', 'en' => 'display'], action: 'display');
```

## Domain Routing

### Domain Routes

```php
// route:
$router->get('blog', [Controller::class, 'method'])
       ->domain('example.com');

// group:
$router->group('api', function($group) {})
       ->domain('api.example.com');

// resource:
$router->resource('products', ProductsController::class)
       ->domain('example.com');
```

**multiple domains**

You may add a route for multiple domains:

```php
$router->get('blog', [Controller::class, 'method'])
       ->domain('example.ch')
       ->domain('example.de');
```

**domain specific parameters**

You may set domain specific parameters for each domain:

```php
use Tobento\Service\Routing\RouteInterface;

$router->get('{?locale}/{about}', function($locale, $about) {
    return [$locale, $about];
})
->name('about')

// default parameters will be used if not specified on domain:
->trans('about', ['de' => 'ueber-uns', 'en' => 'about', 'fr' => 'se-presente'])

->domain('example.ch', function(RouteInterface $route): void {
    $route->locales(['de', 'fr'])
        ->localeOmit('de')
        ->localeFallbacks(['fr' => 'de'])
        ->trans('about', ['de' => 'ueber-uns', 'fr' => 'se-presente']);
})->domain('example.de', function(RouteInterface $route): void {
    $route->locales(['de', 'en'])
        ->localeOmit('de')
        ->localeFallbacks(['en' => 'de']);
});
```

### Domain Url Generation

```php
$router->get('blog', [Controller::class, 'method'])
       ->name('blog')
       ->domain('example.ch')
       ->domain('example.de');

// current domain url:
$url = $router->url('blog');

// get specific domain url:
$url = $router->url('blog')->domain('example.de');

// get all domained urls:
$urls = $router->url('blog')->domained();
/*[
    'example.ch' => 'https://example.ch/blog',
    'example.de' => 'https://example.de/blog',
]*/

// you may get all translated urls from current or specific domain:
$urls = $router->url('blog')->translated();
$urls = $router->url('blog')->domain('example.de')->translated();
```

### Managing Domains

You may specify the domains in order to managing them at one place.

```php
use Tobento\Service\Routing\Router;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RouteResponseParser;
use Tobento\Service\Routing\Domains;
use Tobento\Service\Routing\Domain;

// Any PSR-11 container
$container = new \Tobento\Service\Container\Container();

// Domains
$domains = new Domains(
    new Domain(key: 'example.ch', domain: 'ch.localhost', uri: 'http://ch.localhost'),
    new Domain(key: 'example.de', domain: 'de.localhost', uri: 'http://de.localhost'),
);

$router = new Router(
    new RequestData(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        rawurldecode($_SERVER['REQUEST_URI'] ?? ''),
        'ch.localhost',
    ),
    new UrlGenerator(
        'https://example.com/basepath',
        'a-random-32-character-secret-signature-key',
    ),
    new RouteFactory($domains), // pass domains
    new RouteDispatcher($container, new Constrainer()),
    new RouteHandler($container),
    new MatchedRouteHandler($container),
    new RouteResponseParser(),
);

// Adding Routes:
// Set the domain name as the key specified above:
$router->get('blog', [Controller::class, 'method'])
       ->domain('example.ch')
       ->domain('example.de');
```

## Signed Routing

### Signed Routes

**Add a signed route:**

```php
use Tobento\Service\Routing\InvalidSignatureException;

$router->get('unsubscribe/{user}', [Controller::class, 'method'])
       ->signed('unsubscribe');
       
try {
    $matchedRoute = $router->dispatch();    
} catch (InvalidSignatureException $e) {
    // handle invalid signature
}
```

**Add a signed route with validating on handler for custom response:**

```php
use Tobento\Service\Routing\RouterInterface;

$router->get('unsubscribe/{user}', function(RouterInterface $router, $user) {
    
    $matchedRoute = $router->getMatchedRoute();
    $requestUri = $router->getRequestData()->uri();
    
    if (! $router->getUrlGenerator()->hasValidSignature($matchedRoute->getUri(), $requestUri)) {
        // handle invalid signature.
    }
})
->signed('unsubscribe', validate: false);
```

### Signed Url Generation

```php
use Tobento\Service\Dater\Dater;

$router->get('unsubscribe/{user}', [Controller::class, 'method'])
       ->signed('unsubscribe');

// generate a signed url with no expiring.
$url = (string) $router->url('unsubscribe', ['user' => 5])->sign();
// https://example.com/basepath/unsubscribe/5/a0df83344703b26cd1f9cdcb05196082a6a7799e84b4748a5610d3256b556c55

// generate a signed url which expires in 10 days.
$url = (string) $router->url('unsubscribe', ['user' => 5])->sign((new Dater())->addDays(10));
// https://example.com/basepath/unsubscribe/5/a0df83344703b26cd1f9cdcb05196082a6a7799e84b4748a5610d3256b556c55/1630752459

// generate a signed url with no expiring and add signature data as query parameters.
$url = (string) $router->url('unsubscribe', ['user' => 5])->sign(withQuery: true);
// https://example.com/basepath/unsubscribe/5?signature=6d632a4a8981b1fb017ad6f82067370d6c98ddcd8c6d18cb4fc30c1d44e0f67e

// generate a signed url which expires in 10 days and add signature data as query parameters.
$url = (string) $router->url('unsubscribe', ['user' => 5])->sign((new Dater())->addDays(10), true);
// https://example.com/basepath/unsubscribe/5?expires=1630752540&signature=6d632a4a8981b1fb017ad6f82067370d6c98ddcd8c6d18cb4fc30c1d44e0f67e
```

## Localization and Translation Routing

### Localize Routes

```php
$router->get('{?locale}/about', [Controller::class, 'method'])
       ->name('about');

// get current locale url:
$url = (string) $router->url('about');
// https://example.com/basepath/about

// get specific locale url:
$url = (string) $router->url('about')->locale('en');
// https://example.com/basepath/en/about

// get all translated urls:
$urls = $router->url('about')->translated();
/*[]*/

// get/create specific translated urls:
$urls = $router->url('about')->translated(['de', 'fr']);
/*[
    'de' => 'https://example.com/basepath/de/about',
    'fr' => 'https://example.com/basepath/fr/about',
]*/
```

**Support only specific locales:**

```php
$router->get('{?locale}/about', [Controller::class, 'method'])
       ->name('about')
       ->locales(['de', 'en']); // the supported locales, MUST be called before any other locale methods.

// get current locale url:
$url = (string) $router->url('about');
// https://example.com/basepath/about

// get specific locale url:
$url = (string) $router->url('about')->locale('en');
// https://example.com/basepath/en/about

// get all translated urls:
$urls = $router->url('about')->translated();
/*[
    'de' => 'https://example.com/basepath/de/about',
    'en' => 'https://example.com/basepath/en/about',
]*/

// get/create specific translated urls:
$urls = $router->url('about')->translated(['de', 'fr']);
/*[
    'de' => 'https://example.com/basepath/about',
    'fr' => 'https://example.com/basepath/fr/about',
]*/
```

**Omit locale in request uri:**

```php
$router->get('{?locale}/about', [Controller::class, 'method'])
       ->name('about')
       ->locales(['de', 'en'])
       ->localeOmit('en');

// get current locale url:
$url = (string) $router->url('about');
// https://example.com/basepath/about

// get specific locale url:
$url = (string) $router->url('about')->locale('en');
// https://example.com/basepath/about

// get all translated urls:
$urls = $router->url('about')->translated();
/*[
    'de' => 'https://example.com/basepath/de/about',
    'en' => 'https://example.com/basepath/about',
]*/
```

**Define current locale:**

```php
$router->get('{locale}/about', [Controller::class, 'method'])
       ->name('about')
       ->locale('en');

// get current locale url:
$url = (string) $router->url('about');
// https://example.com/basepath/en/about

// get specific locale url:
$url = (string) $router->url('about')->locale('de');
// https://example.com/basepath/de/about

// get all translated urls:
$urls = $router->url('about')->translated();
/*[
    'en' => 'https://example.com/basepath/en/about',
]*/
```

**Rename locale uri definition:**

```php
$router->get('{?loc}/about', [Controller::class, 'method'])
       ->name('about')
       ->localeName('loc'); // the locale uri definition name, 'locale' is the default name
```

### Translatable Routes

#### Without locale uri definition

```php
$router->get('/{about}', [Controller::class, 'method'])
       ->name('about')
       ->locale('de') // set the current locale.
       ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);

// get current locale url:
$url = (string) $router->url('about');
// https://example.com/basepath/ueber-uns

// get specific locale url:
$url = (string) $router->url('about')->locale('en');
// https://example.com/basepath/about

// get all translated urls:
$urls = $router->url('about')->translated();
/*[
    'de' => 'https://example.com/basepath/ueber-uns',
    'en' => 'https://example.com/basepath/about',
]*/
```

**Support only specific locales:**

```php
$router->get('/{about}', [Controller::class, 'method'])
       ->name('about')
       ->locales(['en']) // the supported locales, MUST be called before any other locale methods.
       ->locale('de') // set the current locale.
       ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);

// get current locale url:
$url = (string) $router->url('about');
// https://example.com/basepath/ueber-uns

// get specific locale url:
$url = (string) $router->url('about')->locale('en');
// https://example.com/basepath/about

// get all translated urls:
$urls = $router->url('about')->translated();
/*[
    'en' => 'https://example.com/basepath/about',
]*/
```

**Define locale fallbacks:**

```php
$router->get('/{about}', [Controller::class, 'method'])
       ->name('about')
       ->locales(['en', 'de', 'fr'])
       ->localeFallbacks(['fr' => 'en'])
       ->locale('de') // set the current locale.
       ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);

// get current locale url:
$url = (string) $router->url('about');
// https://example.com/basepath/ueber-uns

// get specific locale url:
$url = (string) $router->url('about')->locale('fr');
// https://example.com/basepath/about

// get all translated urls:
$urls = $router->url('about')->translated();
/*[
    'en' => 'https://example.com/basepath/about',
    'de' => 'https://example.com/basepath/ueber-uns',
    'fr' => 'https://example.com/basepath/about',
]*/
```

#### With locale uri definition:

```php
$router->get('{?locale}/{about}', [Controller::class, 'method'])
       ->name('about')
       ->localeOmit('en')
       ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);

// get current locale url:
$url = (string) $router->url('about');
// https://example.com/basepath/about

// get specific locale url:
$url = (string) $router->url('about')->locale('de');
// https://example.com/basepath/de/ueber-uns

// get all translated urls:
$urls = $router->url('about')->translated();
/*[
    'de' => 'https://example.com/basepath/de/ueber-uns',
    'en' => 'https://example.com/basepath/about',
]*/
```

**Default parameters are always prioritized:**

```php
$router->get('/{about}', [Controller::class, 'method'])
       ->name('about')
       ->locale('de') // set the current locale.
       ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);

$url = (string) $router->url('about', ['locale' => 'de', 'about' => 'ueberuns'])->locale('en');
// https://example.com/basepath/ueberuns
```

## Matched Route Event

The default MatchedRouteHandler supports autowiring.

```php
$router->get('blog/edit', [Controller::class, 'method'])->name('blog.edit');

$router->matched('blog.edit', function() {
    // do something after the route has been matched.
});
```

## Constrainer

**Add rule constraint to route:**

```php
// instead of:
$router->get('blog/{word}', 'Controller::method')
       ->where('word', '(foo|bar)');

// you can use a rule:
$router->get('blog/{word}', 'Controller::method')
       ->where('word', ':in:foo:bar');
       
// using rule with array syntax.
$router->get('blog/{word}', 'Controller::method')
       ->where('word', ['in', 'foo', 'bar']);
```

**Available Rules:**

| Rule | Regex | Description |
| --- |  --- | --- |
| :alpha | [a-zA-Z]+ | |
| :alpha:2 | [a-zA-Z]{2} | n{x} Matches any string that contains a sequence of X n's |
| :alpha:2:5 | [a-zA-Z]{2,5} | n{x,y} Matches any string that contains a sequence of X to Y n's |
| :alpha:2: | [a-zA-Z]{2,} | n{x,} Matches any string that contains a sequence of at least X n's |
| :num | [0-9]+ | |
| :num:2 | [0-9]{2} | n{x} Matches any string that contains a sequence of X n's |
| :num:2:5 | [0-9]{2,5} | n{x,y} Matches any string that contains a sequence of X to Y n's |
| :num:2: | [0-9]{2,} | n{x,} Matches any string that contains a sequence of at least X n's |
| :alphaNum | [a-zA-Z0-9]+ | |
| :alphaNum:2 | [a-zA-Z0-9]{2} | n{x} Matches any string that contains a sequence of X n's |
| :alphaNum:2:5 | [a-zA-Z0-9]{2,5} | n{x,y} Matches any string that contains a sequence of X to Y n's |
| :alphaNum:2: | [a-zA-Z0-9]{2,} | n{x,} Matches any string that contains a sequence of at least X n's |
| :id:1:5 |  | :id:minNumber:maxLength |
| :id |  | Uses the default parameters from the rule :id:1:21 |
| :in:foo:bar:baz |  | If the value is is one of foo, bar, baz |

**Custom Rules:**

```php
// rule with regex:
$router->getRouteDispatcher()
       ->rule('slug')
       ->regex('[a-z0-9-]+');
       
// rule with regex closure:
$router->getRouteDispatcher()
       ->rule('slug')
       ->regex(function(array $parameters): null|string {
           // build the regex based on the parameters
       });       

// rule with matches:
$router->getRouteDispatcher()
       ->rule('slug')
       ->matches(function(string $value, array $parameters): bool {
           // handle
       });

// or by adding a rule.
$router->getRouteDispatcher()->addRule('slug', new SlugRule());
```

## Dispatching Strategies

There are different ways of handling the matched route, depending on your needs.

### Simple

No middleware support though.

```php
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Routing\TranslationException;
use Tobento\Service\Routing\RouteInterface;

try {
    $matchedRoute = $router->dispatch();
    
    var_dump($matchedRoute instanceof RouteInterface);
    // bool(true)
    
} catch (RouteNotFoundException $e) {
    // handle exception
} catch (InvalidSignatureException $e) {
    // handle exception
} catch (TranslationException $e) {
    // handle exception
}

// call matched route handler for handling registered matched event actions.
$router->getMatchedRouteHandler()->handle($matchedRoute);

// handle the matched route.
$routeResponse = $router->getRouteHandler()->handle($matchedRoute);
```

### With PSR-7 Response

No middleware support though.

```php
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Routing\TranslationException;

try {
    $matchedRoute = $router->dispatch();    
} catch (RouteNotFoundException $e) {
    // handle exception
} catch (InvalidSignatureException $e) {
    // handle exception
} catch (TranslationException $e) {
    // handle exception
}

// call matched route handler for handling registered matched event actions.
$router->getMatchedRouteHandler()->handle($matchedRoute);

// handle the matched route.
$routeResponse = $router->getRouteHandler()->handle($matchedRoute);

// create response.
$response = (new \Nyholm\Psr7\Factory\Psr17Factory())->createResponse(200);

// parse the route response.
$response = $router->getRouteResponseParser()->parse($response, $routeResponse);

// emitting response.
(new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
```

### With PSR-15 Middleware

You will need to define your MiddlewareDispatcher implementation on the container. You might customize this behaviour by your own RouteHandler though.

```php
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;
use Tobento\Service\Middleware\MiddlewareDispatcher;
use Tobento\Service\Middleware\AutowiringMiddlewareFactory;
use Tobento\Service\Middleware\FallbackHandler;

// adjust route parameters passed to the request attributes if needed:
$router->setRequestAttributes(['uri', 'name', 'request_uri']);
// After PreRouting or Routing Middleware: $request->getAttribute('route.name');

// Middleware Handling:
$container->set(MiddlewareDispatcherInterface::class, function($container) {
    
    return new MiddlewareDispatcher(
        new FallbackHandler((new \Nyholm\Psr7\Factory\Psr17Factory())->createResponse(200)),
        new AutowiringMiddlewareFactory($container)
    );
});

$middlewareDispatcher = $container->get(MiddlewareDispatcherInterface::class);

// add MethodOverride middleware if needed.
$middlewareDispatcher->add(\Tobento\Service\Routing\Middleware\MethodOverride::class);
// add PreRouting middleware if needed.
$middlewareDispatcher->add(\Tobento\Service\Routing\Middleware\PreRouting::class);
// ... more middlewares
$middlewareDispatcher->add(\Tobento\Service\Routing\Middleware\Routing::class);

$request = (new \Nyholm\Psr7\Factory\Psr17Factory())->createServerRequest('GET', 'https://example.com');

$response = $middlewareDispatcher->handle($request);

// emitting response.
(new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)