{{-- Copy link, download image, how to use --}}
<div class="flex items-center gap-2" data-no-capture dir="ltr">
    {{-- the page link holds the build --}}
    <div class="relative">
        <button type="button" @click="copyLink()" title="{{ __('treehelp.share') }}" aria-label="{{ __('treehelp.share') }}"
            class="flex size-8 items-center justify-center rounded-sm border border-zinc-700 bg-tree-frame text-white hover:bg-tree-hover hover:shadow-white">
            <x-bi-check-lg x-show="linkCopied" x-cloak class="size-5 text-green-400" />
            <x-bi-link-45deg x-show="!linkCopied" class="size-5" />
        </button>
        <span x-show="linkCopied" x-cloak x-transition.opacity dir="{{ $dir }}"
            class="absolute top-full right-0 z-50 mt-1 rounded-xs bg-black/80 px-2 py-0.5 text-xs whitespace-nowrap text-white">
            {{ __('treehelp.link_copied') }}
        </span>
    </div>

    {{-- spinner while the image is being made --}}
    <button type="button" @click="downloadImage()" :disabled="saving" title="{{ __('treehelp.download') }}" aria-label="{{ __('treehelp.download') }}"
        class="flex size-8 items-center justify-center rounded-sm border border-zinc-700 bg-tree-frame text-white hover:bg-tree-hover hover:shadow-white disabled:cursor-wait">
        <x-bi-download x-show="!saving" class="size-5" />
        <svg x-show="saving" x-cloak class="size-5 text-yellow-400 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        </svg>
    </button>

    {{-- how to use --}}
    <div x-data="helpCard" @mouseenter="show('hover')" @mouseleave="hide('hover')" @click.outside="hide('open')"
        @scroll.window="visible && place()" @resize.window="visible && place()">
        <button type="button" x-ref="button" @click="open ? hide('open') : show('open')" title="{{ __('treehelp.title') }}" aria-label="{{ __('treehelp.title') }}"
            class="flex size-8 items-center justify-center rounded-sm border border-zinc-700 bg-tree-frame text-white hover:bg-tree-hover hover:shadow-white">
            <x-bi-question-circle class="size-5" />
        </button>
        <div x-show="visible" x-cloak x-ref="card" class="skill-card fixed z-50 pt-2" :style="`left:${x}px;top:${y}px`">
            <div dir="{{ $dir }}" class="space-y-px rounded-xs border border-white bg-black/70 p-2 text-start text-sm/4.5 tracking-normal text-gray-400 shadow-lg backdrop-blur-xs ltr:font-light rtl:font-normal">
                <h3 class="pb-1 text-lg font-bold text-yellow-400">{{ __('treehelp.title') }}</h3>
                @foreach (__('treehelp.items') as [$device, $label, $text])
                    <p class="{{ ['mouse' => 'pointer-coarse:hidden', 'touch' => 'hidden pointer-coarse:block'][$device] ?? '' }}">
                        <span class="font-bold text-white/75">{{ $label }}</span> {{ $text }}
                    </p>
                @endforeach
            </div>
        </div>
    </div>
</div>
