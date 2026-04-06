<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminTemplateResource;
use App\Models\Template;
use App\Support\ReelForgeStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(50, max(5, (int) $request->get('per_page', 25)));

        $templates = Template::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => AdminTemplateResource::collection($templates)->response()->getData(true),
        ]);
    }

    public function show(Template $template): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => new AdminTemplateResource($template),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'category'           => 'nullable|string|max:64',
            'is_active'          => 'boolean',
            'sort_order'         => 'integer|min:0|max:999999',
            'generation_prompt'  => 'nullable|string|max:65535',
            'negative_prompt'    => 'nullable|string|max:65535',
            'config_json'        => 'nullable|string|max:65535',
            'preview'            => 'nullable|image|mimes:jpeg,png,webp,jpg|max:4096',
        ]);

        $configDecoded = $this->decodeConfigJson($request);

        $previewPath = $this->storePreview($request);

        $slug = $this->uniqueSlugFromName($validated['name']);

        $template = Template::query()->create([
            'name'               => $validated['name'],
            'slug'               => $slug,
            'category'           => $validated['category'] ?? null,
            'is_active'          => $validated['is_active'] ?? true,
            'sort_order'         => $validated['sort_order'] ?? 0,
            'preview_path'       => $previewPath,
            'generation_prompt'  => $validated['generation_prompt'] ?? null,
            'negative_prompt'    => $validated['negative_prompt'] ?? null,
            'config_json'        => $configDecoded,
        ]);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => new AdminTemplateResource($template),
        ], 201);
    }

    public function update(Request $request, Template $template): JsonResponse
    {
        $validated = $request->validate([
            'name'               => 'sometimes|string|max:255',
            'category'           => 'nullable|string|max:64',
            'is_active'          => 'boolean',
            'sort_order'         => 'integer|min:0|max:999999',
            'generation_prompt'  => 'nullable|string|max:65535',
            'negative_prompt'    => 'nullable|string|max:65535',
            'config_json'        => 'nullable|string|max:65535',
            'preview'            => 'nullable|image|mimes:jpeg,png,webp,jpg|max:4096',
        ]);

        if ($request->hasFile('preview')) {
            $this->deletePreviewIfAny($template);
            $validated['preview_path'] = $this->storePreview($request);
        }

        if ($request->exists('config_json')) {
            $validated['config_json'] = $this->decodeConfigJson($request);
        }

        if (isset($validated['name'])) {
            $validated['slug'] = $this->uniqueSlugFromName($validated['name'], $template->id);
        }

        $data = collect($validated)->except(['preview'])->all();
        $template->fill($data);
        $template->save();

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => new AdminTemplateResource($template->fresh()),
        ]);
    }

    public function destroy(Template $template): JsonResponse
    {
        DB::transaction(function () use ($template) {
            $template->projects()->update(['template_id' => null]);
            $this->deletePreviewIfAny($template);
            $template->delete();
        });

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => [],
        ]);
    }

    private function storePreview(Request $request): ?string
    {
        if (! $request->hasFile('preview')) {
            return null;
        }

        $file   = $request->file('preview');
        $disk   = ReelForgeStorage::templatesDisk();
        $prefix = ReelForgeStorage::templatesPathPrefix();
        $name   = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path   = $prefix.'/previews/'.$name;
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    private function deletePreviewIfAny(Template $template): void
    {
        $path = $template->preview_path;
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk(ReelForgeStorage::templatesDisk())->delete($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeConfigJson(Request $request): array
    {
        if (! $request->exists('config_json')) {
            return [];
        }

        $raw = $request->input('config_json');
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                'config_json' => ['Invalid JSON.'],
            ]);
        }

        return $decoded;
    }

    private function uniqueSlugFromName(string $name, ?int $ignoreTemplateId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'template';
        }

        $slug = $base;
        $q = Template::query()->where('slug', $slug);
        if ($ignoreTemplateId !== null) {
            $q->where('id', '!=', $ignoreTemplateId);
        }
        if (! $q->exists()) {
            return $slug;
        }

        return $base.'-'.Str::lower(Str::random(4));
    }
}
