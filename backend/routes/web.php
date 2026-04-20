<?php

use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

Route::get('/robots.txt', RobotsController::class);
Route::get('/sitemap.xml', SitemapController::class);

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'apple']);

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'apple']);

/**
 * If API and SPA are on different origins, redirect to config app.frontend_url.
 * Compare scheme+host+port via getSchemeAndHttpHost() vs parsed frontend URL (avoids host/port bugs).
 */
$spaRedirectToFrontend = static function (Request $request, string $pathAndQuery): ?SymfonyResponse {
    if ($pathAndQuery === '' || ($pathAndQuery[0] ?? '') !== '/') {
        $pathAndQuery = '/'.ltrim($pathAndQuery, '/');
    }

    $frontend = rtrim((string) config('app.frontend_url'), '/');
    if ($frontend === '') {
        return null;
    }

    $parsed = parse_url($frontend);
    if (! isset($parsed['host'])) {
        return null;
    }

    $frontendOrigin = ($parsed['scheme'] ?? 'http').'://'.$parsed['host'];
    if (isset($parsed['port'])) {
        $frontendOrigin .= ':'.$parsed['port'];
    }

    $requestOrigin = $request->getSchemeAndHttpHost();

    if ($requestOrigin === $frontendOrigin) {
        if (is_file(public_path('index.html'))) {
            return redirect()->to($pathAndQuery);
        }

        return null;
    }

    return redirect()->away($frontend.$pathAndQuery, 302);
};

$missingBundleMessage = static fn (): SymfonyResponse => response(
    'Frontend bundle missing. For production, run npm run build and copy frontend/dist into backend/public (see scripts/deploy.sh). Local: APP_ENV=local defaults frontend to http://localhost:5173; override with FRONTEND_URL.',
    503
)->header('Content-Type', 'text/plain; charset=UTF-8');

/*
 * WayForPay (and similar) may POST the browser to returnUrl after checkout; the SPA fallback is GET-only.
 * Redirect POST → GET so React Router can load; CSRF is skipped (external redirect, no session token).
 */
Route::post('/app/{any?}', function (Request $request, ?string $any = null) use ($spaRedirectToFrontend, $missingBundleMessage) {
    $path = '/app';
    if ($any !== null && $any !== '') {
        $path .= '/'.$any;
    }
    $qs = $request->getQueryString();
    $pathAndQuery = $path.($qs !== null && $qs !== '' ? '?'.$qs : '');

    $resp = $spaRedirectToFrontend($request, $pathAndQuery);

    return $resp ?? $missingBundleMessage();
})->where('any', '.*')->withoutMiddleware([ValidateCsrfToken::class]);

/*
 * SPA (Vite build): deploy copies frontend/dist into public/. Same document root as Laravel.
 * API stays under /api; OAuth above. Anything else → index.html for React Router.
 */
Route::fallback(function () use ($spaRedirectToFrontend, $missingBundleMessage): Response|SymfonyResponse {
    $index = public_path('index.html');
    if (! is_file($index)) {
        $request = request();
        $p = $request->path();
        $path = $p === '' ? '/' : '/'.$p;
        $qs = $request->getQueryString();
        $pathAndQuery = $path.($qs !== null && $qs !== '' ? '?'.$qs : '');

        $resp = $spaRedirectToFrontend($request, $pathAndQuery);

        return $resp ?? $missingBundleMessage();
    }

    return response()->file($index);
});
