<?php

namespace App\Jobs;

use App\DTO\VideoGenerationDTO;
use App\Models\Project;
use App\Services\Credits\CreditService;
use App\Services\Video\VideoGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(private readonly Project $project) {}

    public function handle(VideoGenerationService $service): void
    {
        $this->project->loadMissing(['images', 'template']);

        $dto = new VideoGenerationDTO(
            projectId:      $this->project->id,
            userId:         (int) $this->project->user_id,
            title:          $this->project->title,
            price:          (string) $this->project->price,
            description:    $this->project->description,
            imagePaths:     $this->project->images->pluck('path')->toArray(),
            templateConfig: $this->project->template->config_json ?? [],
        );

        $videoPath = $service->generate($dto);

        $this->project->update([
            'status'     => 'done',
            'video_path' => $videoPath,
        ]);

        Log::info("Video generated for project {$this->project->id}", ['path' => $videoPath]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Video generation failed for project {$this->project->id}", [
            'error' => $exception->getMessage(),
        ]);

        app(CreditService::class)->refundFailedVideoGeneration($this->project->fresh());

        $this->project->update(['status' => 'failed']);
    }
}
