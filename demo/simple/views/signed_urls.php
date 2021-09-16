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
            <ul class="menu">
                <li><a href="<?= $view->url('unsubscribe')->sign(); ?>">Unsubscribe - never expires</a></li>
                <li><a href="<?= $view->url('unsubscribe')->sign($view->now()->addMinutes(1)); ?>">Unsubscribe - expires in one minute</a></li>
                <li><a href="<?= $view->url('unsubscribe')->sign(withQuery: true); ?>">Unsubscribe - never expires with query params</a></li>
                <li><a href="<?= $view->url('unsubscribe')->sign($view->now()->addMinutes(1), true); ?>">Unsubscribe - expires in one minute with query params</a></li>
            </ul>
        </article>

    </body>
</html>