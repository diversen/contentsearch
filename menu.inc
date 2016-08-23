<?php

use diversen\lang;
use diversen\session;

$_MODULE_MENU = array();
$_MODULE_MENU[] = array(
    'title' => lang::translate('Public'),
    'url' => '/contentsearch/index',
);

if (session::isUser()) {
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Private'),
        'url' => '/contentsearch/user',
    );
}