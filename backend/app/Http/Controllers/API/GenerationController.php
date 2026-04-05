<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Replicate\PromptBuilderService;
use App\Services\Replicate\ReplicateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GenerationController extends Controller
{
    public function __construct(
        private readonly ReplicateService    $replicate,
        private readonly PromptBuilderService $promptBuilder,
    ) {}

    /**
     * POST /api/generate
     * Start a new image generation prediction.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'style'       => ['required', 'string', Rule::in(['studio', 'lifestyle', 'minimal', 'outdoor', 'in_use', 'environment'])],
            'description' => ['required', 'string', 'min:3', 'max:500'],
            'contentType' => ['required', 'string', Rule::in(['photo', 'card', 'preview'])],
        ]);

        $prompt      = $this->promptBuilder->build($validated['style'], $validated['description'], $validated['contentType']);
        $modelConfig = $this->promptBuilder->modelConfig($validated['contentType']);
        $modelId     = $modelConfig['id'];

        $input = [
            'prompt'               => $prompt,
            'negative_prompt'      => $this->promptBuilder->negativePrompt(),
            'width'                => $modelConfig['width'],
            'height'               => $modelConfig['height'],
            'num_inference_steps'  => $modelConfig['num_inference_steps'],
            'guidance_scale'       => $modelConfig['guidance_scale'],
        ];

        $prediction = $this->replicate->createPrediction($modelId, $input);

        return response()->json([
            'success'      => true,
            'predictionId' => $prediction['id'],
            'status'       => $prediction['status'],
        ]);
    }

    /**
     * GET /api/generate/{predictionId}
     * Poll prediction status.
     */
    public function status(Request $request, string $predictionId): JsonResponse
    {
        $prediction = $this->replicate->getPrediction($predictionId);

        return response()->json([
            'success' => true,
            'status'  => $prediction['status'],
            'output'  => $prediction['output'],
            'error'   => $prediction['error'],
        ]);
    }
}
