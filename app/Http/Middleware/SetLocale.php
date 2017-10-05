<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if($request->filled('language') && in_array($request->langage, config('app.availableLocales')) ){
            App::setLocale($request->language);
        }

        return $next($request);
    }
}
