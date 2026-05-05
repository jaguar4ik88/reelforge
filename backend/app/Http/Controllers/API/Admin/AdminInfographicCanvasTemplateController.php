<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\Infographic\InfographicCanvasTemplateService;
use App\Services\Infographic\InfographicTemplateLayoutGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminInfographicCanvasTemplateController extends Controller
{
    private const RESERVED = ['templates-layout.json', 'templates-layout.example.json'];

    public function __construct(
        private readonly InfographicCanvasTemplateService $canvasTemplates,
        private readonly InfographicTemplateLayoutGenerator $layoutGenerator,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => $this->canvasTemplates->listForApi(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ]);

        $upload = $request->file('file');
        $ext = strtolower($upload->getClientOriginalExtension());
        $base = Str::slug(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME) ?: 'template');
        if ($base === '') {
            $base = 'template';
        }

        $dir = $this->canvasTemplates->templatesDirectory();
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $name = $base.'.'.$ext;
        $i = 0;
        while (File::exists($dir.DIRECTORY_SEPARATOR.$name)) {
            $i++;
            $name = $base.'-'.$i.'.'.$ext;
        }

        $upload->storeAs('infographic', $name, 'public');

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => [
                'filename' => $name,
                'url'      => Storage::disk('public')->url('infographic/'.$name),
            ],
        ], 201);
    }

    public function generateLayout(Request $request, string $filename): JsonResponse
    {
        $name = basename(str_replace('\\', '/', $filename));
        if (! $this->canvasTemplates->isAllowedBasename($name)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid filename.',
                'errors'  => [],
            ], 422);
        }
        if (in_array($name, self::RESERVED, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This file cannot be processed.',
                'errors'  => [],
            ], 403);
        }

        $full = $this->canvasTemplates->templatesDirectory().DIRECTORY_SEPARATOR.$name;
        if (! is_file($full)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
                'errors'  => [],
            ], 404);
        }

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            /** When false and a manifest entry already exists, returns 409. */
            'force' => ['nullable', 'boolean'],
        ]);

        $force = $request->boolean('force', true);
        $label = isset($validated['label']) && $validated['label'] !== '' ? (string) $validated['label'] : null;

        $result = $this->layoutGenerator->generate(
            $full,
            $name,
            $label,
            overwriteExisting: $force,
            dryRun: false,
        );

        if (! $result['ok']) {
            $status = ($result['code'] ?? '') === 'manifest_exists' ? 409 : 422;

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors'  => [],
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => [
                'filename'     => $name,
                'texts_count'  => $result['texts_count'] ?? 0,
                'manifest_key' => $result['manifest_key'] ?? $name,
            ],
        ]);
    }

    public function destroy(string $filename): JsonResponse
    {
        $name = basename(str_replace('\\', '/', $filename));
        if (! $this->canvasTemplates->isAllowedBasename($name)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid filename.',
                'errors'  => [],
            ], 422);
        }
        if (in_array($name, self::RESERVED, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This file cannot be deleted.',
                'errors'  => [],
            ], 403);
        }

        $full = $this->canvasTemplates->templatesDirectory().DIRECTORY_SEPARATOR.$name;
        if (! is_file($full)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
                'errors'  => [],
            ], 404);
        }

        File::delete($full);
        $this->removeManifestKey($name);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => null,
        ]);
    }

    private function removeManifestKey(string $basename): void
    {
        $path = $this->canvasTemplates->templatesDirectory().DIRECTORY_SEPARATOR.'templates-layout.json';
        if (! is_readable($path)) {
            return;
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return;
        }
        if (! array_key_exists($basename, $decoded)) {
            return;
        }
        unset($decoded[$basename]);
        file_put_contents($path, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
    }
}