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
        if($request->filled('language') && in_array($request->language, config('app.availableLocales')) ){
            App::setLocale($request->language);
        } elseif (auth()->check() && in_array(auth()->user()->pref_language, config('app.availableLocales'))) {
            App::setLocale(auth()->user()->pref_language);
        }
        return $next($request);
    }
}
