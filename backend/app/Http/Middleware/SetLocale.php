<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    private const SUPPORTED = ['uk', 'en'];
    private const DEFAULT   = 'uk';

    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // 1. Authenticated user preference
        if ($user = $request->user()) {
            $locale = $user->locale ?? self::DEFAULT;
            if (in_array($locale, self::SUPPORTED)) {
                return $locale;
            }
        }

        // 2. Accept-Language header
        $header = $request->header('Accept-Language', '');
        foreach (explode(',', $header) as $part) {
            $lang = strtolower(trim(explode(';', $part)[0]));
            $short = substr($lang, 0, 2);
            if (in_array($short, self::SUPPORTED)) {
                return $short;
            }
        }

        // 3. X-Locale header (explicit override from frontend)
        $explicit = strtolower($request->header('X-Locale', ''));
        if (in_array($explicit, self::SUPPORTED)) {
            return $explicit;
        }

        return self::DEFAULT;
    }
}
