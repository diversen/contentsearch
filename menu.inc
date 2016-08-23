<?php

use diversen\lang;
use diversen\session;

$_MODULE_MENU = array();

$_MODULE_MENU[] = array(
    'title' => lang::translate('View all'),
    'url' => '/contentsearch/index',
);

$_MODULE_MENU[] = array(
    'title' => lang::translate('Search'),
    'url' => '/contentsearch/search',
);

if (session::isUser()) {
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Private'),
        'url' => '/contentsearch/user',
    );
}