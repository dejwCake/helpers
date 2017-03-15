<?php

namespace DejwCake\Helpers\Middleware;

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\Routing\Router;

class LangFromUrlMiddleware
{
    public function __invoke($request, $response, $next)
    {
        //TODO move to Standard Web
        $segments = array_filter(explode('/', urldecode($request->getUri()->getPath())));
        if(!empty($segments[1])) {
            $supportedLanguages = Configure::read('App.supportedLanguages');
            if (array_key_exists($segments[1], $supportedLanguages)) {
                I18n::locale($supportedLanguages[$segments[1]]['locale']);
            }
        }

        return $next($request, $response);
    }
}