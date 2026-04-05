<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_if(! $user || ! $user->isStaff(), 403, 'Forbidden.');

        return $next($request);
    }
}
