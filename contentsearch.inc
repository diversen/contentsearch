<?php

use modules\contentsearch\module as search;


function content_generate_index () {
    $s = new search();
    $s->genereateIndex();
}

// enable commands in shell - first we set a base command
self::setCommand('search', array(
    'description' => 'Create search table from content articles',
));

self::setOption('content_generate_index', array(
    'long_name'   => '--generate-index',
    'description' => 'Make articles searchable, create an index',
    'action'      => 'StoreTrue'
));