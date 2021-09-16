<!DOCTYPE html>
<html>
    <head>
        <title><?= $view->esc($title) ?></title>

        <?= $view->assets()->render() ?>

        <?php
        $view->asset('app.css');
        ?>
    </head>
    <body>
        
        <?= $view->render('inc/nav') ?>
        
        <article class="main">
            <h1><?= $view->esc($title) ?></h1>
            
            <h2><?= $view->esc($product->title()) ?></h2>
        </article>

    </body>
</html>