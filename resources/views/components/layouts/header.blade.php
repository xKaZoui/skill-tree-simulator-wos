@php
    $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $other = $locale === 'ar' ? 'en' : 'ar';
    $onClass = fn (string $class) => request()->routeIs('skill-tree.show') && request()->route('class') === $class;
    $pages = [
        ['url' => route('skill-tree.show', 'warrior'), 'label' => __('app.warrior'), 'icon' => 'images/classes/sword.webp', 'active' => $onClass('warrior')],
        ['url' => route('skill-tree.show', 'mage'), 'label' => __('app.mage'), 'icon' => 'images/classes/wand.webp', 'active' => $onClass('mage')],
        ['url' => route('skill-tree.show', 'archer'), 'label' => __('app.archer'), 'icon' => 'images/classes/bow.webp', 'active' => $onClass('archer')],
        ['url' => route('Changelog'), 'label' => __('app.changelog'), 'icon' => null, 'active' => request()->routeIs('Changelog')],
    ];
@endphp
<header class="relative z-50 mt-4 mb-12 px-3 text-white antialiased select-none lg:mb-14 {{ $locale === 'ar' ? 'font-light' : 'font-normal' }}"
    dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" x-data="{ mobileMenuOpen: false }" x-on:keydown.escape.window="mobileMenuOpen = false">
    <div class="relative mx-auto w-full lg:w-fit">

        {{-- Desktop --}}
        <nav class="relative mx-auto hidden w-fit rounded-full border-2 border-yellow-900/65 px-4 whitespace-nowrap text-gray-300/75 shadow-lg lg:block"
            aria-label="{{ __('app.main_menu') }}">
            <div class="grid grid-cols-header items-center">
                @foreach (array_chunk($pages, 2) as $i => $side)
                    <div class="{{ $i === 0 ? 'col-start-1' : 'col-start-3' }} row-start-1 flex items-center">
                        @foreach ($side as $page)
                            <a href="{{ $page['url'] }}" @if ($page['active']) aria-current="page" @endif
                                class="group relative mx-2 inline-flex h-12 items-center px-2 leading-tight duration-300 ease-out hover:text-yellow-400 {{ $page['active'] ? 'text-yellow-400' : '' }}">
                                @if ($page['icon'])
                                    <img src="{{ asset($page['icon']) }}" alt=""
                                        class="inline-block px-1 group-hover:grayscale-0 {{ $page['active'] ? '' : 'grayscale' }}">
                                @else
                                    <x-bi-card-list class="inline-block h-5 w-7 px-1" aria-hidden="true" />
                                @endif
                                {{ $page['label'] }}

                                {{-- line under the link --}}
                                <span class="absolute bottom-0 h-px translate-y-px bg-linear-to-r from-yellow-900/65 via-yellow-500 to-yellow-900/65 duration-300 ease-out group-hover:left-0 group-hover:w-full {{ $page['active'] ? 'left-0 w-full' : 'left-1/2 w-0' }}"></span>

                                {{-- small diamond under the current page --}}
                                @if ($page['active'])
                                    <span aria-hidden="true" class="absolute -bottom-1 left-1/2 size-1.5 -translate-x-1/2 rotate-45 bg-yellow-500"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach

                {{-- Logo --}}
                <a href="{{ route('Home') }}" class="relative col-start-2 row-start-1 h-full" aria-label="{{ __('app.home') }}">
                    <img src="{{ asset("images/logos/logo-{$locale}.webp") }}" alt="{{ __('app.name') }}"
                        class="absolute left-1/2 -top-4 z-10 h-28 w-auto max-w-none -translate-x-1/2 drop-shadow-xl" width="256" height="256">
                </a>
            </div>
        </nav>

        {{-- Burger for small screens --}}
        <div class="flex h-12 items-center justify-between lg:hidden">
            <button type="button" x-on:click="mobileMenuOpen = ! mobileMenuOpen" x-bind:aria-expanded="mobileMenuOpen"
                aria-controls="mobile-navigation" class="cursor-pointer rounded-full text-white">
                <span class="sr-only" x-text="mobileMenuOpen ? @js(__('app.close_menu')) : @js(__('app.open_menu'))">{{ __('app.open_menu') }}</span>
                <x-bi-list x-show="! mobileMenuOpen" aria-hidden="true" class="size-8" />
                <x-bi-x-lg x-show="mobileMenuOpen" aria-hidden="true" class="size-8 text-red-500" x-cloak />
            </button>
            <a href="{{ route('locale.switch', $other) }}" data-keep-build class="inline-flex size-10 items-center justify-center"
                aria-label="{{ __('app.change_language_to') }} {{ config("app.supported_locales.{$other}.name") }}">
                <x-icon name="flag-country-{{ config('app.supported_locales.'.$other.'.icon') }}" class="h-6 w-6" />
            </a>
        </div>

        {{-- Logo for small screens --}}
        <a href="{{ route('Home') }}" class="absolute left-1/2 -top-4 z-10 -translate-x-1/2 lg:hidden" aria-label="{{ __('app.home') }}">
            <img src="{{ asset("images/logos/logo-{$locale}.webp") }}" alt="{{ __('app.name') }}"
                class="h-24 w-auto max-w-none drop-shadow-xl" width="256" height="256">
        </a>

        {{-- Language flag beside the menu for large screens --}}
        <div class="absolute top-1/2 hidden -translate-y-1/2 lg:block {{ $locale === 'ar' ? 'right-full -translate-x-8' : 'left-full translate-x-8' }}">
            <a href="{{ route('locale.switch', $other) }}" data-keep-build class="inline-flex size-10 items-center justify-center"
                aria-label="{{ __('app.change_language_to') }} {{ config("app.supported_locales.{$other}.name") }}">
                <x-icon name="flag-country-{{ config('app.supported_locales.'.$other.'.icon') }}" class="h-6 w-6" />
            </a>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-navigation" x-show="mobileMenuOpen" x-on:click.outside="mobileMenuOpen = false"
            class="mt-16 space-y-2 rounded-lg bg-gray-800 p-4 shadow-lg lg:hidden" x-transition.opacity.duration.150ms x-cloak>
            @foreach ($pages as $page)
                <a href="{{ $page['url'] }}" @if ($page['active']) aria-current="page" @endif
                    class="block rounded-md px-3 py-2 text-base font-medium hover:bg-gray-700 hover:text-white {{ $page['active'] ? 'bg-gray-900 text-white' : 'text-gray-300' }}">
                    {{ $page['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</header>
