<?php

namespace DejwCake\Helpers\Middleware;

use Cake\Core\Configure;

class TranslateMiddleware
{
    public function __invoke($request, $response, $next)
    {
        //TODO remove, forms according required requests
        $data = $request->getParsedBody();
        if(isset($data['_translations'][Configure::read('App.defaultLocale')])) {
            foreach ($data['_translations'][Configure::read('App.defaultLocale')] as $field => $value) {
                $data[$field] = $value;
                unset($data['_translations'][Configure::read('App.defaultLocale')]);
            }
            $request = $request->withParsedBody($data);
        }

        return $next($request, $response);
    }
}