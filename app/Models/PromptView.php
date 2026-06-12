<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptView extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'prompt_id',
        'visitor_hash',
        'counted',
        'user_id',
    ];

    protected $casts = [
        'counted' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
