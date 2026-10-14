@php($dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr')
<x-layouts.app :title="$title" :page-description="__('app.changelogdescr')" :page-image="asset('images/pages/preview-changelog.webp')">
    {{-- Loading screen until the page and its images have loaded --}}
    <x-loading-screen x-data="pageLoader" x-show="!ready" />

    <div class="mx-auto max-w-4xl px-4 pb-16 text-white" dir="{{ $dir }}" x-data="{ filter: 'all' }">
        <section class="overflow-hidden rounded-2xl border-4 border-white bg-tree-tile shadow-lg">

            {{-- Title on the stone ledge --}}
            <header class="flex h-12 items-center justify-center bg-tree-ledge-top pb-2">
                <h1 class="w-2/5 min-w-48 rounded-xs border border-zinc-700 bg-tree-frame py-0.5 text-center text-lg font-light tracking-wide">
                    {{ $title }}
                </h1>
            </header>

            <div class="p-4 lg:px-7 lg:pt-5 lg:pb-7">
                {{-- Class filter --}}
                <div class="mb-3 flex flex-wrap gap-2">
                    <button type="button" @click="filter = 'all'" class="rounded-xs border px-3 py-0.5 font-light"
                        :class="filter === 'all' ? 'border-yellow-400 bg-tree-hover text-yellow-400' : 'border-zinc-700 bg-tree-frame hover:bg-tree-hover hover:shadow-white'">
                        {{ __('changelog.all') }}
                    </button>
                    @foreach ($totals as $class => $count)
                        <button type="button" @click="filter = '{{ $class }}'" class="inline-flex items-center gap-1.5 rounded-xs border py-0.5 ps-1.5 pe-3 font-light"
                            :class="filter === '{{ $class }}' ? 'border-yellow-400 bg-tree-hover text-yellow-400' : 'border-zinc-700 bg-tree-frame hover:bg-tree-hover hover:shadow-white'">
                            <img src="{{ asset("images/classes/{$class}_ch.webp") }}" alt="" class="size-5.5">
                            {{ __("app.{$class}") }}
                            <span class="text-xs opacity-70">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Timeline --}}
                <div class="relative ps-7">
                    <div class="absolute inset-y-1.5 inset-s-2 w-0.5 bg-yellow-500"></div>

                    @forelse ($patches as $patch)
                        <section class="relative mb-6" x-data="{ open: @js($loop->first) }" x-show="filter === 'all' || @js(array_keys($patch['counts'])).includes(filter)">
                            <span class="absolute -inset-s-6.5 top-2.5 size-3.5 rotate-45 border-2 border-yellow-500 {{ $loop->first ? 'bg-yellow-500' : 'bg-tree-panel' }}"></span>

                            {{-- Date, change counts, source links --}}
                            <div class="flex flex-wrap items-center gap-x-3.5 gap-y-1.5">
                                <button type="button" @click="open = !open" :aria-expanded="open"
                                    class="inline-flex items-center gap-2 text-xl font-semibold tracking-wide">
                                    {{ $patch['date'] }}
                                    <x-bi-chevron-down class="size-4 opacity-80 transition-transform" ::class="open && 'rotate-180'" />
                                </button>
                                @foreach ($patch['counts'] as $class => $count)
                                    <span class="inline-flex items-center gap-1 rounded-xs bg-black/35 px-2 text-xs text-gray-300">
                                        <img src="{{ asset("images/classes/{$class}_ch.webp") }}" alt="{{ __("app.{$class}") }}" class="size-4">
                                        {{ trans_choice('changelog.changes', $count) }}
                                    </span>
                                @endforeach
                                <div class="ms-auto flex gap-4">
                                    @foreach ($patch['sources'] as $source)
                                        <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $source['title'] }}"
                                            class="inline-flex items-center gap-1.5 text-sm font-medium whitespace-nowrap">
                                            @if ($source['type'] === 'discord')
                                                <x-bi-discord class="size-4.5 text-amber-500" />
                                            @else
                                                <x-bi-chat-left-text class="size-4.5 text-amber-500" />
                                            @endif
                                            <span class="bg-linear-to-b from-yellow-400 to-amber-600 bg-clip-text text-transparent underline decoration-yellow-900 decoration-3 underline-offset-3">{{ __("changelog.{$source['type']}") }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Classes and their skills --}}
                            <div x-show="open" @unless ($loop->first) x-cloak @endunless x-transition.duration.300ms class="mt-3 space-y-3.5">
                                @foreach ($patch['classes'] as $class => $skills)
                                    <div x-show="filter === 'all' || filter === '{{ $class }}'">
                                        <h2 class="mb-2 flex items-center gap-2 font-semibold text-gray-100">
                                            <img src="{{ asset("images/classes/{$class}_ch.webp") }}" alt="" class="size-6">
                                            {{ __("app.{$class}") }}
                                            <span class="h-px flex-1 bg-linear-to-r from-gray-300/25 rtl:bg-linear-to-l"></span>
                                        </h2>

                                        @foreach ($skills as $skill)
                                            @include('changelog.partials.skill', ['skill' => $skill])
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <p class="text-sm text-gray-400">{{ __('changelog.empty') }}</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
