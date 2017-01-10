<?php

namespace DejwCake\Helpers\Middleware;

use Cake\Core\Configure;
use Cake\I18n\I18n;

class LangFromUrlMiddleware
{
    public function __invoke($request, $response, $next)
    {
        //TODO move to Standard Web
        $params = $request->getAttribute('params', []);
        if(!empty($params['language'])) {
            $supportedLanguages = Configure::read('App.supportedLanguages');
            if (array_key_exists($params['language'], $supportedLanguages)) {
                I18n::locale($supportedLanguages[$params['language']]['locale']);
            }
        }

        return $next($request, $response);
    }
}