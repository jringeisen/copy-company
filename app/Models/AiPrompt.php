<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiPrompt extends Model
{
    use HasFactory;

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

    /**
     * @param  Builder<AiPrompt>  $query
     * @return Builder<AiPrompt>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @param  Builder<AiPrompt>  $query
     * @return Builder<AiPrompt>
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param  Builder<AiPrompt>  $query
     * @return Builder<AiPrompt>
     */
    public function scopeForIndustry(Builder $query, ?string $industry): Builder
    {
        return $query->where(function (Builder $q) use ($industry): void {
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
