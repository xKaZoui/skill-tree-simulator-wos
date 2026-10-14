{{-- One skill --}}
<article class="mb-2 grid grid-cols-skill-row gap-x-3 gap-y-1 rounded-xs border border-white/85 bg-black/60 px-3 py-2.5">
    <img src="{{ $skill['image'] }}" alt="" width="32" height="32" class="size-10 shadow-pic">

    <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-0.5">
        <h3 class="text-lg font-medium text-yellow-400">{{ $skill['name'] }}</h3>
        <span class="text-sm text-gray-400/85">{{ $skill['tree'] }}</span>
        <a href="{{ $skill['url'] }}" target="_blank" rel="noopener" class="ms-auto inline-flex items-center gap-1 text-sm whitespace-nowrap text-gray-300/75 hover:text-gray-300 hover:underline">
            {{ __('changelog.open_tree') }} <x-bi-box-arrow-up-right class="size-3 rtl:-scale-x-100" />
        </a>
    </div>

    <div class="col-span-2 mt-0.5 flex flex-col gap-2 sm:col-start-2 sm:col-span-1">
        @foreach ($skill['changes'] as $change)
            @php($color = $change['buff'] ? 'text-green-400' : 'text-red-400')

            @if ($change['note'])
                {{-- purple text like the notes on the skill card --}}
                <p class="text-sm font-medium text-violet-300">
                    <span class="me-1.5 inline-block size-1.5 rounded-full bg-violet-300 align-middle"></span>{!! $change['text'] !!}
                    @include('changelog.partials.tag', ['buff' => $change['buff']])
                </p>
            @else
                <div>
                    <p class="text-sm font-medium">
                        <span class="me-1 {{ $color }}" title="{{ __($change['up'] ? 'changelog.went_up' : 'changelog.went_down') }}">
                            @if ($change['up'])
                                <x-bi-arrow-up-right class="inline size-3.5 align-middle rtl:-scale-x-100" />
                            @else
                                <x-bi-arrow-down-right class="inline size-3.5 align-middle rtl:-scale-x-100" />
                            @endif
                        </span>{!! $change['text'] !!}
                        @include('changelog.partials.tag', ['buff' => $change['buff']])
                    </p>

                    @if (count($change['before']) === 1)
                        {{-- before → after --}}
                        <p class="mt-0.5 text-sm">
                            <span class="text-gray-100/50" dir="ltr">{{ $change['before'][0] }}</span>
                            <span class="rtl:hidden">→</span><span class="ltr:hidden">←</span>
                            <span class="font-semibold {{ $color }}" dir="ltr">{{ $change['after'][0] }}</span>
                        </p>
                    @else
                        {{-- before and after rows per level --}}
                        <div class="levels-grid mt-1 grid max-w-md overflow-hidden rounded-xs border border-white/10 text-center text-sm whitespace-nowrap"
                            style="--cols: {{ count($change['before']) }}">
                            <div class="bg-white/10 px-1.5 text-start text-xs text-white/60">{{ __('changelog.level') }}</div>
                            @foreach ($change['before'] as $i => $value)
                                <div class="bg-white/10 px-1.5 text-xs text-white/60">{{ $i + 1 }}</div>
                            @endforeach

                            <div class="px-1.5 text-start text-xs text-white/60">{{ __('changelog.before') }}</div>
                            @foreach ($change['before'] as $value)
                                <div class="px-1.5 text-gray-100/50" dir="ltr">{{ $value }}</div>
                            @endforeach

                            <div class="border-t border-white/10 px-1.5 text-start text-xs text-white/60">{{ __('changelog.after') }}</div>
                            @foreach ($change['after'] as $value)
                                <div class="border-t border-white/10 px-1.5 font-semibold {{ $color }}" dir="ltr">{{ $value }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</article>
