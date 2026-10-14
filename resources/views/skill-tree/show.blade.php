@php($dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr')
<x-layouts.app :title="$title" :page-image="asset('images/pages/preview-'.$payload['class'].'.webp')">
    <div class="px-4 py-6" :class="saving && 'overflow-hidden'" x-data="skillTreePage(@js($payload))" @resize.window.debounce.100ms="fit()"
        @pointerdown.window="outsidePress($event)" @scroll.window="tip.pinned && placeCard()" @keydown.escape.window="hideCard()">

        {{-- Loading screen until the trees and their images are ready --}}
        <x-loading-screen x-show="!ready" />
        <x-loading-screen x-show="saving" x-cloak>{{ __('treehelp.preparing') }}</x-loading-screen>
        {{-- the part saved by "Download Image" --}}
        <div x-ref="sheet" class="mx-auto grid w-fit overflow-hidden rounded-2xl border-4 border-white bg-brown text-white">

            {{-- Level --}}
            <div class="flex flex-col items-center justify-center" dir="{{ $dir }}">
                {{-- buttons --}}
                <div class="mt-2" x-show="!sideBySide">
                    @include('skill-tree.partials.toolbar')
                </div>
                <div>
                    <span class="text-2xl font-extrabold text-shadow-points">{{ $labels['level'] }}:</span>
                    {{-- level box with - and + inside --}}
                    <div data-plain-ring class="m-2 inline-flex h-16 w-28 items-center justify-center gap-0.5 rounded-lg bg-black px-2.5 align-middle ring-1 ring-gray-300 ring-inset focus-within:ring-2 focus-within:ring-white">
                        <button type="button" data-no-capture @click="stepLevel(-1)" :disabled="state.level <= minLevel" aria-label="-1"
                            class="flex h-full shrink-0 items-center justify-center text-white/60 hover:text-white disabled:cursor-default disabled:opacity-25">
                            <x-icons.level-step class="size-4" />
                        </button>
                        <input type="text" inputmode="numeric" x-model="levelInput" aria-label="{{ $labels['level'] }}"
                            @change="commitLevel()" @keydown.enter.prevent="$event.target.blur()"
                            class="h-full w-14 min-w-0 border-0 bg-transparent p-0 text-center text-3xl font-extrabold text-red-600 caret-white text-shadow-level focus:outline-hidden">
                        <button type="button" data-no-capture @click="stepLevel(1)" :disabled="state.level >= maxLevel" aria-label="+1"
                            class="flex h-full shrink-0 items-center justify-center text-white/60 hover:text-white disabled:cursor-default disabled:opacity-25">
                            <x-icons.level-step plus class="size-4" />
                        </button>
                    </div>
                </div>
                <div x-show="levelInput !== String(state.level)" x-cloak>
                    <span class="pointer-coarse:hidden">{{ __('treehelp.level_hint_mouse') }}</span>
                    <span class="hidden pointer-coarse:inline">{{ __('treehelp.level_hint_touch') }}</span>
                </div>
            </div>

            {{-- Points bars and trees. For wide screens trees 1-3 with their bar and tree 4 with its bar on the right --}}
            <div :class="fourInRow && 'grid grid-flow-col grid-rows-bar-trees'">
                {{-- Available points + reset all  --}}
                <div class="@container relative flex justify-center border-y-4 border-black text-sm" dir="ltr">
                    {{-- buttons on the right of the bar when the trees are side by side, on the left when tree 4 is next to the others --}}
                    <div x-show="sideBySide" class="absolute inset-y-0 flex items-center" :class="fourInRow ? 'left-3' : 'right-3'">
                        @include('skill-tree.partials.toolbar')
                    </div>
                    <img src="{{ asset('images/background/left_border_apoints.webp') }}" alt="">
                    <div class="flex w-full flex-row items-center justify-center bg-yellow-500 py-1 text-center text-black @xl:px-2 @2xl:w-2/3 @4xl:w-1/3">
                        <div class="flex-none py-0.5">
                            <x-bi-exclamation-square class="size-6 rounded-sm bg-white stroke-brown" />
                        </div>
                        <div class="flex-auto items-center px-1 whitespace-nowrap @xl:px-2 @2xl:flex-none" dir="{{ $dir }}">
                            {{ $labels['available_points'] }}
                            <span class="ms-1 inline-block min-w-10 border-b-2 border-current text-center leading-tight @xl:ms-4" x-text="summary.baseAvailable"></span>
                        </div>
                        <button type="button" @click="resetAll()"
                            class="shrink-0 rounded-sm border border-zinc-700 bg-yellow-600 px-1 py-0.5 text-center text-xs whitespace-nowrap hover:bg-yellow-700 @xl:px-2 @xl:text-sm">
                            {{ $labels['reset_all'] }}
                        </button>
                    </div>
                    <img src="{{ asset('images/background/right_border_apoints.webp') }}" alt="">
                </div>

                {{-- Trees 1, 2, 3 are always left to right, stone ledges on the outer sides --}}
                <div class="flex" dir="ltr">
                    <div class="w-5 shrink-0 bg-tree-ledge-left"></div>
                    <div class="flex divide-tree-frame" :class="sideBySide ? 'flex-row divide-x-4' : 'flex-col divide-y-4'">
                        <template x-for="tree in mainTrees" :key="tree.id">
                            @include('skill-tree.partials.tree')
                        </template>
                    </div>
                    <div class="w-5 shrink-0 bg-tree-ledge-right" :class="fourInRow && 'hidden'"></div>
                </div>

                {{-- Tree 4 --}}
                <template x-if="fourthTree">
                    <div :class="fourInRow && 'contents'">
                        <div class="@container flex justify-center border-y-4 border-black text-sm" dir="ltr">
                            <img src="{{ asset('images/background/left_border_apoints_green.webp') }}" alt="">
                            <div class="flex w-full flex-row items-center justify-center bg-tree-fourth py-1 text-center text-black @xl:px-2"
                                :class="!fourInRow && '@2xl:w-2/3 @4xl:w-1/3'">
                                <div class="flex-none py-0.5">
                                    <x-bi-exclamation-square class="size-6 rounded-sm bg-white stroke-brown" />
                                </div>
                                <div class="flex-auto items-center px-1 whitespace-nowrap @xl:px-2 @2xl:flex-none" dir="{{ $dir }}">
                                    {{ $labels['available_points'] }}
                                    <span class="ms-1 inline-block min-w-10 border-b-2 border-current text-center leading-tight @xl:ms-4" x-text="summary.fourthAvailable"></span>
                                </div>
                            </div>
                            <img src="{{ asset('images/background/right_border_apoints_green.webp') }}" alt="">
                        </div>
                        {{-- For wide screens a line instead of the left ledge --}}
                        <div class="flex justify-center" :class="fourInRow && 'border-s-4 border-tree-frame'" dir="ltr">
                            <div class="w-5 shrink-0 bg-tree-ledge-left" :class="fourInRow && 'hidden'"></div>
                            <div x-data="{ tree: fourthTree }" class="flex flex-col *:flex-1">
                                @include('skill-tree.partials.tree')
                            </div>
                            <div class="w-5 shrink-0 bg-tree-ledge-right"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Skill card --}}
        <div x-ref="card" x-show="tip.id !== null && card" x-cloak data-skill-card dir="{{ $dir }}"
            class="skill-card fixed z-50 space-y-px rounded-xs border border-white bg-black/70 p-2 text-sm/4.5 tracking-normal shadow-lg backdrop-blur-xs ltr:font-light rtl:font-normal"
            :class="[card?.textClass, tip.ready ? 'visible' : 'invisible', tip.pinned ? 'pointer-events-auto' : 'pointer-events-none']"
            :style="`left:${tip.x}px;top:${tip.y}px`">
            <template x-if="card">
                <div>
                    {{-- name (Lv. x/max) + state --}}
                    <h3 class="pe-10 text-lg font-bold" :class="card.nameClass">
                        <span x-text="card.name"></span> (<span x-text="card.level"></span>)
                    </h3>
                    <p x-show="card.status" class="pe-10 text-sm font-bold text-gray-400/70" x-text="card.status"></p>
                    <img :src="card.image" alt="" class="absolute top-2 inset-e-2">

                    {{-- For touch screens the long press card has the buttons a mouse gets from clicks --}}
                    <div x-show="tip.pinned" class="flex gap-2 py-1">
                        <button type="button" @click="cardRemove(true)" :disabled="points(card.id) === 0"
                            class="rounded-sm border border-zinc-500 bg-white/10 px-3 py-1 text-white disabled:opacity-40">{{ $labels['card_clear'] }}</button>
                        <button type="button" @click="cardRemove(false)" :disabled="points(card.id) === 0"
                            class="min-w-12 rounded-sm border border-zinc-500 bg-white/10 px-3 py-1 font-bold text-white disabled:opacity-40">−1</button>
                        <button type="button" @click="cardAdd(false)" :disabled="!canAdd(card.id)"
                            class="min-w-12 rounded-sm border border-zinc-500 bg-white/10 px-3 py-1 font-bold text-white disabled:opacity-40">+1</button>
                        <button type="button" @click="cardAdd(true)" :disabled="!canAdd(card.id)"
                            class="rounded-sm border border-zinc-500 bg-white/10 px-3 py-1 text-white disabled:opacity-40">{{ $labels['card_max'] }}</button>
                    </div>

                    {{-- fields --}}
                    <template x-for="row in card.rows" :key="row.label">
                        <p><span class="font-bold text-white/75" x-text="`${row.label}:`"></span> <span x-text="row.value"></span></p>
                    </template>

                    {{-- description with the current level's values --}}
                    <p class="py-2 text-sm/5 text-pretty" x-html="card.description"></p>

                    {{-- levels --}}
                    <template x-if="card.levels">
                        <table class="table-auto border-collapse border border-slate-500">
                            <thead>
                                <tr class="bg-yellow-500/70 text-black">
                                    <th class="border border-yellow-800 px-2 py-0.5 font-light">{{ $labels['card_level'] }}</th>
                                    <th class="border border-yellow-800 px-2 py-0.5 font-light" :colspan="card.levels.keys.length">{{ $labels['card_values'] }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in card.levels.rows" :key="row.level">
                                    <tr class="text-center">
                                        <td class="border border-slate-700 px-2 py-0.5" :class="row.current && 'bg-yellow-500/70 text-black'" x-text="row.level"></td>
                                        <template x-for="value in row.values" :key="value.key">
                                            <td class="border border-slate-700 px-2 py-0.5 font-bold text-black"
                                                :class="row.current ? 'bg-yellow-500/70' : value.className" x-text="value.text"></td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                </div>
            </template>
        </div>
    </div>
</x-layouts.app>
