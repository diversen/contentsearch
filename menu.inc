<?php

use diversen\lang;
use diversen\session;

$_MODULE_MENU = array();

$_MODULE_MENU[] = array(
    'title' => lang::translate('Public'),
    'url' => '/contentsearch/index',
);

$_MODULE_MENU[] = array(
    'title' => lang::translate('Search'),
    'url' => '/contentsearch/search',
);

if (session::isUser()) {
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Your books'),
        'url' => '/contentsearch/user',
    );
}