<?php

namespace App\Services\Card;

use RuntimeException;
use Symfony\Component\Process\Process;

class CardPuppeteerRendererService
{
    /**
     * @param  array<string, mixed>  $payload
     *                                         template, imagePath, width, height, badges, title, description, price, priceOld, accent,
     *                                         outputType (jpeg|png), jpegQuality, deviceScaleFactor, executablePath, outPath (optional)
     */
    public function renderCard(array $payload): string
    {
        $node = (string) config('platform.card.puppeteer.node_binary', 'node');
        $cli = (string) (config('platform.card.puppeteer.cli_path') ?: base_path('card-renderer/cli.mjs'));

        if (! is_file($cli)) {
            throw new RuntimeException('Card renderer CLI not found: '.$cli);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $process = new Process([$node, $cli], null, null, $json, 300.0);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'Card Puppeteer render failed: '.trim($process->getErrorOutput().' '.$process->getOutput())
            );
        }

        $line = trim($process->getOutput());
        $decoded = json_decode($line, true);
        if (! is_array($decoded) || empty($decoded['ok'])) {
            throw new RuntimeException('Card renderer returned invalid output: '.$line);
        }
        $path = (string) ($decoded['path'] ?? '');
        if ($path === '' || ! is_file($path)) {
            throw new RuntimeException('Card renderer did not produce a file.');
        }

        return $path;
    }
}
