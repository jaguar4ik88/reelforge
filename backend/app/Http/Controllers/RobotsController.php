<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * robots.txt with absolute Sitemap URL (required by Google).
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $body = <<<TXT
User-agent: *
Allow: /

Sitemap: {$base}/sitemap.xml
TXT;

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
