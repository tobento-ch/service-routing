<?php
// handle active menu item.
// We could do it also outside the view.
$menus->get('main')->on($routeName, function($item, $menu) {
    
    $item->itemTag()->class('active');
    
    if ($item->getTreeLevel() > 0) {
        $item->parentTag()->class('active');
    }
    
    if ($item instanceof \Tobento\Service\Menu\Taggable) {
        $item->tag()->class('active');
    }
    
    return $item;
});

$menus->get('main')->tag('ul')->level(0)->class('menu');
?>
<nav id="nav-main"><?= $menus->get('main') ?><?= $view->render('inc/navlocales') ?></nav>