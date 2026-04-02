<?php

namespace App\Http\Resources;

use App\Services\Credits\CreditService;
use App\Support\ReelForgeStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('creditWallet');
        $creditService = app(CreditService::class);

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'plan'              => $this->plan,
            'locale'            => $this->locale ?? 'uk',
            'avatar_url'        => ReelForgeStorage::url(
                ReelForgeStorage::contentDisk(),
                $this->avatar_path,
                2
            ),
            'credits'           => [
                'balance'                    => (int) ($this->creditWallet?->balance ?? 0),
                'video_generation_cost'      => $creditService->getOperationCost('video_generation'),
                'photo_guided_generation_cost' => $creditService->getOperationCost('photo_guided_generation'),
            ],
            'videos_this_month' => $this->videosThisMonth(),
            'video_limit'       => $this->videoLimit(),
            'can_generate'      => $this->canGenerateVideo(),
            'created_at'        => $this->created_at->toISOString(),
        ];
    }
}
