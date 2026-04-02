<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'is_active',
        'sort_order',
        'preview_path',
        'config_json',
    ];

    protected function casts(): array
    {
        return [
            'config_json' => 'array',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
