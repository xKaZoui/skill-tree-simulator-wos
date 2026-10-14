<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ChangeLog extends Model
{
    use HasTranslations;

    public array $translatable = ['description'];

    protected $casts = [
        'is_up' => 'boolean',
        'is_buff' => 'boolean',
    ];

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function isNote(): bool
    {
        return $this->first_part === null && $this->second_part === null;
    }
}
