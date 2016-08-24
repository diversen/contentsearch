<?php


use diversen\lang;

$title = lang::translate('Search', null, array('no_translate' => true));
$url = '/contentsearch/search';

$_INSTALL['MAIN_MENU_ITEMS'][] = array (
    'title' => $title,
    'url' => $url,
    'module_name' => 'contentsearch',
    'parent' => 0,
    'weight' => 5
);


