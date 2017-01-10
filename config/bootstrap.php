<?php

use Cake\Event\EventManager;
use DejwCake\Helpers\Middleware\LangFromUrlMiddleware;
use DejwCake\Helpers\Middleware\TranslateMiddleware;

EventManager::instance()->on(
    'Server.buildMiddleware',
    function ($event, $middleware) {
        $middleware->add(new LangFromUrlMiddleware());
        $middleware->add(new TranslateMiddleware());
    });
