<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPrompt extends Model
{
    protected $fillable = [
        'type',
        'industry',
        'system_prompt',
        'user_prompt_template',
        'version',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForIndustry($query, ?string $industry)
    {
        return $query->where(function ($q) use ($industry) {
            $q->where('industry', $industry)
                ->orWhereNull('industry');
        });
    }

    public static function getPrompt(string $type, ?string $industry = null): ?self
    {
        return static::active()
            ->forType($type)
            ->forIndustry($industry)
            ->orderByRaw('industry IS NULL')
            ->orderByDesc('version')
            ->first();
    }
}
