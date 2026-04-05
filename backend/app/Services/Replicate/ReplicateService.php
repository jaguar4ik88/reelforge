<?php

namespace App\Services\Replicate;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ReplicateService
{
    private const BASE_URL = 'https://api.replicate.com/v1';

    private PendingRequest $http;

    public function __construct()
    {
        $token = config('services.replicate.token');

        if (empty($token)) {
            throw new RuntimeException('REPLICATE_API_TOKEN is not set.');
        }

        $this->http = Http::baseUrl(self::BASE_URL)
            ->withToken($token)
            ->timeout(30)
            ->acceptJson();
    }

    /**
     * Create a new prediction and return its ID + initial status.
     *
     * @return array{id: string, status: string}
     */
    public function createPrediction(string $modelId, array $input): array
    {
        $response = $this->http->post('/models/' . $modelId . '/predictions', [
            'input' => $input,
        ]);

        if ($response->failed()) {
            Log::error('Replicate createPrediction failed', [
                'model'  => $modelId,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('Replicate API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'id'     => $data['id'],
            'status' => $data['status'],
        ];
    }

    /**
     * Get current prediction status and output.
     *
     * @return array{status: string, output: array|null, error: string|null}
     */
    public function getPrediction(string $predictionId): array
    {
        $response = $this->http->get("/predictions/{$predictionId}");

        if ($response->failed()) {
            Log::error('Replicate getPrediction failed', [
                'prediction_id' => $predictionId,
                'status'        => $response->status(),
                'body'          => $response->body(),
            ]);
            throw new RuntimeException('Replicate API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'status' => $data['status'],
            'output' => $data['output'] ?? null,
            'error'  => $data['error'] ?? null,
        ];
    }
}
