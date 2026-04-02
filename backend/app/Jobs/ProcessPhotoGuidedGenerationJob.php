<?php

namespace App\Jobs;

use App\Models\GenerationJob;
use App\Services\Credits\CreditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPhotoGuidedGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;

    public int $timeout = 120;

    public function __construct(private readonly GenerationJob $generationJob) {}

    public function handle(): void
    {
        $job = GenerationJob::query()->findOrFail($this->generationJob->id);
        if (in_array($job->status, ['done', 'failed'], true)) {
            return;
        }

        $job->update(['status' => 'processing']);

        // Stub pipeline: replace with real image/video generation and write result_path.
        Log::info('Photo-guided generation (stub)', [
            'generation_job_id' => $job->id,
            'project_id'        => $job->project_id,
            'prompt_length'     => strlen($job->final_prompt),
        ]);

        $job->update([
            'status'   => 'done',
            'provider' => $job->provider ?? 'stub',
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $fresh = $this->generationJob->fresh();
        if ($fresh === null) {
            return;
        }

        Log::error('Photo-guided generation failed', [
            'generation_job_id' => $fresh->id,
            'error'             => $exception->getMessage(),
        ]);

        app(CreditService::class)->refundFailedPhotoGuidedGeneration($fresh);

        $fresh->update([
            'status'         => 'failed',
            'error_message'  => $exception->getMessage(),
        ]);
    }
}

```

</think>
Fixing the job: `lockForUpdate` requires an outer transaction; removing it for simplicity.

<｜tool▁calls▁begin｜><｜tool▁call▁begin｜>
StrReplace