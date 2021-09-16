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
            
            <?php if ($description) { ?>
                <p><?= $view->esc($description) ?></p>
            <?php } ?>
            
            <?php foreach($articles as $article) { ?>
                <article class="list">
                    <h2><?= $view->esc($article->title()) ?></h2>
                    <a href="<?= $view->url('article.show', ['slug' => $article->slug()])->locale($locale); ?>">
                        <?= $view->esc($article->title()) ?>
                    </a>
                </article>
            <?php } ?>
        </article>

    </body>
</html>