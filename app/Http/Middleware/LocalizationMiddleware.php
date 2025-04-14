<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocalizationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $langRequest = request()->language;

        if (! empty($langRequest) && empty($user?->language)) {
            App::setlocale($langRequest);

            return $next($request);
        }

        if (! empty($user?->language)) {
            App::setlocale($user->language);
        } else {
            App::setlocale(config('app.locale'));
        }

        return $next($request);
    }
}
