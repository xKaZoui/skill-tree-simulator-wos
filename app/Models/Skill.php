<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Skill extends Model
{
    use HasTranslations;

    public $timestamps = false;

    public array $translatable = ['name', 'description', 's_type', 'weapon',
        'attribute', 'attack_type', 'selected_target', 'apply_target'];

    protected $casts = [
        'levels' => 'array',
    ];

    public function skillTree(): BelongsTo
    {
        return $this->belongsTo(SkillTree::class);
    }
}
