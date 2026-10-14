<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillTree;
use App\Models\SkillTreeConnection;
use Illuminate\View\View;

class SkillTreeController extends Controller
{
    public const CLASSES = ['warrior', 'mage', 'archer'];

    public function show(string $class): View
    {
        $trees = SkillTree::query()
            ->where('class_key', $class)
            ->orderBy('position')
            ->with(['skills' => fn ($query) => $query->orderBy('grid_row')->orderBy('grid_column')])
            ->get();

        $skills = $trees->flatMap->skills;

        $connections = SkillTreeConnection::query()
            ->whereIn('from_skill_id', $skills->pluck('id'))
            ->orderBy('id')
            ->get();

        $assets = [
            'tree' => asset('images/tree'),
            'orbs' => [
                asset('images/tree/first_guardian.webp'),
                asset('images/tree/second_guardian.webp'),
                asset('images/tree/third_guardian.webp'),
            ],
        ];

        $labels = [
            'level' => __("{$class}tree.level"),
            'available_points' => __("{$class}tree.available_skill_points"),
            'reset_all' => __("{$class}tree.reset_all"),
            'reset' => __("{$class}tree.reset"),
            'total_points' => __("{$class}tree.total_points_used_on_tree"),
            'card_level' => __('skillc.level'),
            'card_values' => __('skillc.values'),
            'card_max' => __('skillc.max'),
            'card_clear' => __('skillc.clear'),
        ];

        $payload = [
            'class' => $class,
            'defaultLevel' => 80,
            'maxLevel' => 80,
            'assets' => $assets,
            'locale' => app()->getLocale(),
            'labels' => [
                'card' => trans('skillc'),
                'dividers' => [
                    2 => __("{$class}tree.second_job_divider"),
                    3 => __("{$class}tree.third_job_divider"),
                ],
            ],
            'trees' => $trees->map(fn (SkillTree $tree) => [
                'id' => $tree->id,
                'position' => $tree->position,
                'name' => $tree->position === 4 ? __('app.fourth_tree') : $tree->name,
            ])->values(),
            'skills' => $skills->map(fn (Skill $skill) => [
                'id' => $skill->id,
                'treeId' => $skill->skill_tree_id,
                'row' => $skill->grid_row,
                'column' => $skill->grid_column,
                'maxPoints' => $skill->max_points,
                'jobTier' => $skill->job_tier,
                'requiredPoints' => $skill->required_points,
                'requiredTotalBasePoints' => $skill->required_total_base_points,
                'requiredInvestmentTreeId' => $skill->required_investment_tree_id,
                'requiredInvestmentPoints' => $skill->required_investment_points,
                'name' => $skill->name,
                'image' => asset('images/skills/'.$skill->image),
                'guardians' => [$skill->fguardian_points, $skill->sguardian_points, $skill->tguardian_points],
                'tip' => $this->tooltip($skill),
            ])->values(),
            'connections' => $connections->map(fn (SkillTreeConnection $connection) => [
                'from' => $connection->from_skill_id,
                'to' => $connection->to_skill_id,
                'route' => $connection->route,
            ])->values(),
        ];

        return view('skill-tree.show', [
            'title' => __("{$class}tree.page_title"),
            'payload' => $payload,
            'assets' => $assets,
            'labels' => $labels,
        ]);
    }

    private function tooltip(Skill $skill): array
    {
        return [
            'type' => strtolower(strtok((string) $skill->getTranslation('s_type', 'en'), ' ')),
            'typeName' => $skill->s_type ?: null,
            'weapon' => $skill->weapon ?: null,
            'attribute' => $skill->attribute ?: null,
            'cooltime' => $skill->cooltime,
            'mpUse' => $skill->mp_use,
            'attackType' => $skill->attack_type ?: null,
            'selectedTarget' => $skill->selected_target ?: null,
            'applyTarget' => $skill->apply_target ?: null,
            'description' => $skill->description ?: null,
            'levels' => $skill->levels ?: null,
        ];
    }
}
