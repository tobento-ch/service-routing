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
            <form method="POST" action="<?= $view->url('products.store'); ?>">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" value="">
                <button class="button" type="submit">Save</button>
            </form>
        </article>

    </body>
</html>