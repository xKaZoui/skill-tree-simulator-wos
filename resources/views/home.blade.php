<x-layouts.app :title="$title" :page-description="__('app.description')" :page-image="asset('images/pages/preview-home.webp')">
    {{-- Loading screen until the page and its images have loaded --}}
    <x-loading-screen x-data="pageLoader" x-show="!ready" />

    <div class="mx-auto grid w-3/4 rounded-2xl border-4 border-white bg-brown text-white xl:h-96">

        <div class="grid auto-rows-auto rounded-2xl bg-tree-tile lg:grid-cols-3">

            {{-- Warrior --}}
            <div class="flex items-center justify-center rounded-2xl border-8 border-tree-frame p-2">
                <a href="{{ route('skill-tree.show', 'warrior') }}" class="group relative">
                    <img src="{{ asset('images/classes/warriorg.webp') }}" alt="warrior" class="relative z-10 duration-500 group-hover:scale-125" />
                    <span
                        class="absolute inset-0 z-0 flex items-center justify-center text-7xl font-medium text-red-900 transition-all duration-700 text-shadow-homeProf group-hover:z-40 group-hover:text-6xl group-hover:font-bold xl:text-8xl xl:group-hover:text-7xl">
                        {{ __('app.warrior') }}
                    </span>
                </a>
            </div>

            {{-- Mage --}}
            <div class="flex items-center justify-center rounded-2xl border-8 border-tree-frame p-2">
                <a href="{{ route('skill-tree.show', 'mage') }}" class="group relative">
                    <img src="{{ asset('/images/classes/mageg.webp') }}" alt="mage" class="relative z-10 duration-500 group-hover:scale-125" />
                    <span
                        class="absolute inset-0 z-0 flex items-center justify-center text-7xl font-medium text-blue-900 transition-all duration-700 text-shadow-homeProf group-hover:z-40 group-hover:text-6xl group-hover:font-bold xl:text-8xl xl:group-hover:text-7xl">
                        {{ __('app.mage') }}
                    </span>
                </a>
            </div>

            {{-- Archer --}}
            <div class="flex items-center justify-center rounded-2xl border-8 border-tree-frame p-2">
                <a href="{{ route('skill-tree.show', 'archer') }}" class="group relative">
                    <img src="{{ asset('/images/classes/archerg.webp') }}" alt="archer" class="relative z-10 duration-500 group-hover:scale-125" />
                    <span
                        class="absolute inset-0 z-0 flex items-center justify-center text-7xl font-medium text-orange-950 transition-all duration-700 text-shadow-homeProf group-hover:z-40 group-hover:text-6xl group-hover:font-bold xl:text-8xl xl:group-hover:text-7xl">
                        {{ __('app.archer') }}
                    </span>
                </a>
            </div>

        </div>

    </div>
</x-layouts.app>
