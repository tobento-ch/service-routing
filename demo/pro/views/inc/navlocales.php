<?php
// handle active menu item.
// We could do it also outside the view.
$menus->get('locales')?->on($locale, function($item, $menu) {
    
    $item->itemTag()->class('active');
    
    if ($item->getTreeLevel() > 0) {
        $item->parentTag()->class('active');
    }
    
    if ($item instanceof \Tobento\Service\Menu\Taggable) {
        $item->tag()->class('active');
    }
    
    return $item;
});

$menus->get('locales')?->tag('ul')->level(0)->class('menu-h');
$menus->get('locales')?->tag('li')->class('delimiter');
?>
<?php if ($menus->get('locales')) { ?>
    <nav class="nav-locales"><?= $menus->get('locales') ?></nav>
<?php } ?>