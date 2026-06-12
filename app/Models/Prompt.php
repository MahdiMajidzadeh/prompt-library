<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Prompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'is_public',
        'user_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'view_count' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (self $prompt) {
            if (empty($prompt->slug)) {
                $prompt->slug = self::generateUniqueSlug();
            }
        });
    }

    protected static function generateUniqueSlug(): string
    {
        do {
            $slug = Str::random(16);
        } while (self::where('slug', $slug)->exists());

        return $slug;
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(PromptView::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recordView(?string $visitorHash = null): void
    {
        try {
            $recent = PromptView::where('prompt_id', $this->id)
                ->where('visitor_hash', $visitorHash)
                ->where('created_at', '>=', now()->subSeconds(30))
                ->exists();

            if ($recent) {
                return;
            }

            PromptView::create([
                'prompt_id' => $this->id,
                'visitor_hash' => $visitorHash,
                'counted' => false,
            ]);
        } catch (\Throwable $e) {
            // Never let view recording break the page render.
        }
    }
}
