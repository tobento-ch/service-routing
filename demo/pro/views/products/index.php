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
            
            <a href="<?= $view->url('products.create'); ?>">Create New</a>
            
            <?php foreach($products as $product) { ?>
                <article class="list">
                    <h2><?= $view->esc($product->title()) ?></h2>
                    <a href="<?= $view->url('products.edit', ['id' => $product->id()]); ?>">Edit</a> |                     
                    <a href="<?= $view->url('products.show', ['id' => $product->id()]); ?>">Show</a> |
                    
                    <form class="inline" method="POST" action="<?= $view->url('products.update', ['id' => $product->id()]); ?>">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="button raw" type="submit">Delete</button>
                    </form>
                </article>
            <?php } ?>
        </article>

    </body>
</html>