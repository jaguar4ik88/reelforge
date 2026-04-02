<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Template::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $templates = $query->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => TemplateResource::collection($templates),
        ]);
    }

    public function show(Template $template): JsonResponse
    {
        abort_if(! $template->is_active, 404);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => new TemplateResource($template),
        ]);
    }
}
