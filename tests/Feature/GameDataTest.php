<?php

use App\Models\Skill;
use App\Models\SkillTreeConnection;

// Checks the seeded game data for mistakes that would show on the skill cards

it('has an icon file for every skill', function () {
    $missing = Skill::all()
        ->reject(fn (Skill $skill) => is_file(public_path('images/skills/'.$skill->image)))
        ->map(fn (Skill $skill) => "{$skill->id} {$skill->image}");

    expect($missing->values()->all())->toBe([]);
});

it('keeps every connection inside one tree', function () {
    $trees = Skill::pluck('skill_tree_id', 'id');

    $crossing = SkillTreeConnection::all()
        ->filter(fn ($c) => $trees[$c->from_skill_id] !== $trees[$c->to_skill_id])
        ->map(fn ($c) => "{$c->from_skill_id} -> {$c->to_skill_id}");

    expect($crossing->values()->all())->toBe([]);
});

it('has an Arabic text for every English text', function () {
    $fields = ['name', 'description', 's_type', 'weapon', 'attribute', 'attack_type', 'selected_target', 'apply_target'];

    $missing = [];
    foreach (Skill::all() as $skill) {
        foreach ($fields as $field) {
            if (filled($skill->getTranslation($field, 'en', false)) && blank($skill->getTranslation($field, 'ar', false))) {
                $missing[] = "{$skill->id} {$field}";
            }
        }
    }

    expect($missing)->toBe([]);
});

it('uses the same placeholders in both languages and in the levels', function () {
    $placeholders = function (string $text): array {
        preg_match_all('/(?<![A-Za-z0-9:]):([a-z])(?![A-Za-z])/', $text, $matches);
        $found = array_values(array_unique($matches[1]));
        sort($found);

        return $found;
    };

    $problems = [];
    foreach (Skill::all() as $skill) {
        $en = $placeholders($skill->getTranslation('description', 'en', false));
        $ar = $placeholders($skill->getTranslation('description', 'ar', false));
        $keys = collect($skill->levels ?? [])->flatMap(fn (array $row) => array_keys($row))
            ->unique()->reject(fn ($key) => $key === 'level')->sort()->values()->all();

        if ($en !== $ar) {
            $problems[] = "{$skill->id}: en uses ".implode(',', $en).', ar uses '.implode(',', $ar);
        }
        if ($skill->levels && $en !== $keys) {
            $problems[] = "{$skill->id}: description uses ".implode(',', $en).', levels have '.implode(',', $keys);
        }
        if ($skill->levels && count($skill->levels) !== $skill->max_points + 1) {
            $problems[] = "{$skill->id}: ".count($skill->levels).' level rows for '.$skill->max_points.' points';
        }
    }

    expect($problems)->toBe([]);
});
