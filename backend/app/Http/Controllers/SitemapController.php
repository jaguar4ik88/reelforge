<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Public SEO URLs for the SPA (same host as APP_URL).
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim((string) config('app.url'), '/');

        $entries = [
            ['loc' => $base.'/', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => $base.'/pricing', 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => $base.'/blog', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => $base.'/register', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => $base.'/login', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ];

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($entries as $e) {
            $loc = htmlspecialchars($e['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.$loc.'</loc>';
            $lines[] = '    <changefreq>'.$e['changefreq'].'</changefreq>';
            $lines[] = '    <priority>'.$e['priority'].'</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
