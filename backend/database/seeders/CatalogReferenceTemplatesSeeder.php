<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copies reference images from project /tmp into storage (templates/previews)
 * and upserts catalog templates with English generation prompts for FLUX/Kontext.
 *
 * Run: php artisan db:seed --class=CatalogReferenceTemplatesSeeder
 */
class CatalogReferenceTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $tmp = dirname(base_path()).DIRECTORY_SEPARATOR.'tmp';

        $items = [
            [
                'src'  => $tmp.DIRECTORY_SEPARATOR.'найк главная .png',
                'dest' => 'templates/previews/catalog-af1-triple-white.png',
                'record' => [
                    'name'               => 'Air Force 1 — triple white hero',
                    'slug'               => 'air-force-1-triple-white-hero',
                    'category'           => 'sneakers',
                    'is_active'          => true,
                    'sort_order'         => 10,
                    'generation_prompt'  => <<<'PROMPT'
Professional product photography, triple white Nike Air Force 1 low sneakers suspended by white laces, clean side profile, crisp sharp focus. Bright directional sunlight from upper right with defined shadows on leather. Dark out-of-focus metal diamond mesh grate background, high contrast minimalist commercial look, catalog hero shot, 8k photorealistic, shallow depth of field.
PROMPT,
                    'negative_prompt' => 'blurry, watermark, logo distortion, muddy colors, low resolution, deformed shoe',
                    'config_json'     => ['style' => 'product_hero', 'subject' => 'sneakers'],
                ],
            ],
            [
                'src'  => $tmp.DIRECTORY_SEPARATOR.'kak-vnedrit-krossovki-nike-najk-v-povsednevnyj-garderob-1-marathon.ua.jpg',
                'dest' => 'templates/previews/catalog-jordan-street.jpg',
                'record' => [
                    'name'               => 'Jordan 1 — street stride',
                    'slug'               => 'jordan-1-street-stride',
                    'category'           => 'sneakers',
                    'is_active'          => true,
                    'sort_order'         => 11,
                    'generation_prompt'  => <<<'PROMPT'
Street lifestyle sneaker photography, Air Jordan 1 high-top in classic red, black and white leather, subject walking mid-stride on grey rectangular stone pavement, slim dark blue jeans slightly cuffed, low camera angle, shallow depth of field, soft bokeh modern building background with glass facade, natural daylight, editorial commercial quality, sharp focus on footwear, 8k photorealistic.
PROMPT,
                    'negative_prompt' => 'blurry, extra legs, deformed sneaker, cartoon, watermark',
                    'config_json'     => ['style' => 'street_lifestyle', 'subject' => 'sneakers'],
                ],
            ],
            [
                'src'  => $tmp.DIRECTORY_SEPARATOR.'generated_10_0.png',
                'dest' => 'templates/previews/catalog-jordan-space.png',
                'record' => [
                    'name'               => 'Jordan 1 — lunar space',
                    'slug'               => 'jordan-1-lunar-space',
                    'category'           => 'sneakers',
                    'is_active'          => true,
                    'sort_order'         => 12,
                    'generation_prompt'  => <<<'PROMPT'
Cinematic photorealistic product shot, Nike Air Jordan 1 high-top sneakers in red and black leather colorway, placed on dark rocky lunar surface with fine dust. Deep space background with dense starfield and glowing nebula or galaxy, dramatic soft key light from front-above, subtle shadows on moon rocks, premium advertising visual, hyper-detailed leather grain, slight bokeh on distant stars, 8k, epic mood.
PROMPT,
                    'negative_prompt' => 'blurry, floating debris clutter, fake HDR halos, watermark, melted sole',
                    'config_json'     => ['style' => 'cinematic_fantasy', 'subject' => 'sneakers'],
                ],
            ],
            [
                'src'  => $tmp.DIRECTORY_SEPARATOR.'5096342431_w1280_h640_5096342431.webp',
                'dest' => 'templates/previews/catalog-apparel-color-grid.webp',
                'record' => [
                    'name'               => 'Apparel — multi-color grid',
                    'slug'               => 'apparel-multi-color-grid',
                    'category'           => 'apparel',
                    'is_active'          => true,
                    'sort_order'         => 13,
                    'generation_prompt'  => <<<'PROMPT'
E-commerce catalog layout on pure white studio background, split composition: left side male model wearing plain black crew neck short-sleeve t-shirt and dark jeans with hands in pockets, bright even softbox lighting; right side vertical grid of nine t-shirt color variants with small product numbers on each swatch, clean retail listing style, sharp fabric detail, no harsh shadows, professional marketplace photography, 8k.
PROMPT,
                    'negative_prompt' => 'dirty background, wrinkled chaos, illegible numbers, distorted face, watermark',
                    'config_json'     => ['style' => 'catalog_grid', 'subject' => 'apparel'],
                ],
            ],
        ];

        $disk = config('reelforge.storage.templates_disk', config('filesystems.default', 'public'));

        foreach ($items as $item) {
            if (! is_file($item['src'])) {
                $this->command?->warn('Skip missing file: '.$item['src']);

                continue;
            }

            $bytes = file_get_contents($item['src']);
            if ($bytes === false) {
                $this->command?->warn('Could not read: '.$item['src']);

                continue;
            }

            Storage::disk($disk)->put($item['dest'], $bytes);

            $data = $item['record'];
            $data['preview_path'] = $item['dest'];

            Template::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            $this->command?->info('Template OK: '.$data['slug'].' → '.$item['dest']);
        }

        $this->command?->info('Done. Files live under disk "'.$disk.'" at templates/previews/…');
    }
}
