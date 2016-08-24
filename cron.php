<?php

namespace modules\contentsearch;

use Cron\CronExpression;
use diversen\conf;
use modules\contentsearch\module as search;

class cron {

    public function run() {
        $cron = conf::getModuleIni('contentsearch_cron');
        if (!$cron) {
            return; 
        }
        $minute = CronExpression::factory($cron);
        if ($minute->isDue()) {
            $s = new search();
            $s->genereateIndex();
        }
    }
}
