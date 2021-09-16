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
            <form method="POST" action="<?= $view->url('products.update', ['id' => $product->id()]); ?>">
                <input type="hidden" name="_method" value="PUT">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" value="<?= $view->esc($product->title()); ?>">
                <button class="button" type="submit">Save</button>
            </form>
        </article>

    </body>
</html>