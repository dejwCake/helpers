<?php

use Cake\Event\EventManager;
use Cake\Routing\Middleware\RoutingMiddleware;
use DejwCake\Helpers\Middleware\LangFromUrlMiddleware;
use DejwCake\Helpers\Middleware\TranslateMiddleware;

EventManager::instance()->on(
    'Server.buildMiddleware',
    function ($event, $middleware) {
        $middleware->insertBefore(RoutingMiddleware::class, new LangFromUrlMiddleware());
        $middleware->add(new TranslateMiddleware());
    });
