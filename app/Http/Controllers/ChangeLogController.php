<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use App\Models\ChangeLogSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ChangeLogController extends Controller
{
    public function index(): View
    {
        $logs = ChangeLog::query()
            ->with('skill.skillTree')
            ->orderByDesc('dt')
            ->orderBy('id')
            ->get();

        $sources = ChangeLogSource::query()->orderBy('id')->get()->groupBy('dt');
        $ar = app()->getLocale() === 'ar';

        $patches = $logs->groupBy('dt')->map(fn (Collection $rows, string $dt) => [
            'dt' => $dt,
            'date' => Carbon::parse($dt)->locale(app()->getLocale())->translatedFormat($ar ? 'j F Y' : 'F j, Y'),
            'sources' => ($sources[$dt] ?? collect())->map(fn (ChangeLogSource $source) => [
                'url' => $source->source_url,
                'title' => $source->title,
                'type' => str_contains((string) parse_url($source->source_url, PHP_URL_HOST), 'discord') ? 'discord' : 'forum',
            ])->values(),
            'counts' => $this->countByClass($rows),
            'classes' => collect(SkillTreeController::CLASSES)
                ->filter(fn (string $class) => $rows->contains('cl', $class))
                ->mapWithKeys(fn (string $class) => [
                    $class => $rows->where('cl', $class)->groupBy('skill_id')
                        ->map(fn (Collection $skillRows) => $this->skill($skillRows, $class))
                        ->values(),
                ]),
        ])->values();

        return view('changelog.index', [
            'title' => __('app.changelog'),
            'patches' => $patches,
            'totals' => $this->countByClass($logs),
        ]);
    }

    private function countByClass(Collection $rows): array
    {
        return collect(SkillTreeController::CLASSES)
            ->mapWithKeys(fn (string $class) => [$class => $rows->where('cl', $class)->count()])
            ->filter()
            ->all();
    }

    private function skill(Collection $rows, string $class): array
    {
        $skill = $rows->first()->skill;
        $tree = $skill->skillTree;

        return [
            'id' => $skill->id,
            'name' => $skill->name,
            'image' => asset('images/skills/'.$skill->image),
            'tree' => $tree->position === 4 ? __('app.fourth_tree') : $tree->name,
            'url' => route('skill-tree.show', $class).'?skill='.$skill->id,
            'changes' => $rows->map(fn (ChangeLog $log) => [
                'note' => $log->isNote(),
                'up' => $log->is_up,
                'buff' => $log->is_buff,
                'text' => $log->description,
                'before' => $log->isNote() ? [] : explode('/', (string) $log->first_part),
                'after' => $log->isNote() ? [] : explode('/', (string) $log->second_part),
            ])->values()->all(),
        ];
    }
}
