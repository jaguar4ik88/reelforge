<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Dark Elegance',
                'config_json' => [
                    'overlay_color' => '0x000000@0.6',
                    'font_color'    => 'white',
                    'accent_color'  => '#FFD700',
                    'style'         => 'dark',
                ],
            ],
            [
                'name' => 'Clean White',
                'config_json' => [
                    'overlay_color' => '0xFFFFFF@0.75',
                    'font_color'    => '0x111111',
                    'accent_color'  => '#E53E3E',
                    'style'         => 'light',
                ],
            ],
            [
                'name' => 'Neon Glow',
                'config_json' => [
                    'overlay_color' => '0x0A0A2E@0.7',
                    'font_color'    => '0x00FFCC',
                    'accent_color'  => '#FF00FF',
                    'style'         => 'neon',
                ],
            ],
            [
                'name' => 'Gradient Sunset',
                'config_json' => [
                    'overlay_color' => '0xFF6B35@0.55',
                    'font_color'    => 'white',
                    'accent_color'  => '#FFF200',
                    'style'         => 'warm',
                ],
            ],
        ];

        foreach ($templates as $order => $template) {
            $style = $template['config_json']['style'] ?? 'general';
            Template::query()->updateOrCreate(
                ['name' => $template['name']],
                [
                    'slug'        => Str::slug($template['name']),
                    'category'    => $style,
                    'is_active'   => true,
                    'sort_order'  => $order,
                    'preview_path'=> null,
                    'config_json' => $template['config_json'],
                ]
            );
        }
    }
}
