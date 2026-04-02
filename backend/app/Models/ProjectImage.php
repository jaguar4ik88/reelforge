<?php

namespace App\Models;

use App\Support\ReelForgeStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'path',
        'order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getUrlAttribute(): string
    {
        $url = ReelForgeStorage::url(ReelForgeStorage::contentDisk(), $this->path, 2);

        return $url ?? '';
    }
}
