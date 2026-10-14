<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class SkillTree extends Model
{
    use HasTranslations;

    public $timestamps = false;

    public array $translatable = ['name'];

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }
}
