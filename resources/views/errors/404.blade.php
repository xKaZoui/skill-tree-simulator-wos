{{-- Page not found 404 --}}
<x-layouts.app :title="__('app.not_found')">
    <div class="flex flex-col items-center gap-6 px-4 py-20 text-center sm:py-28" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <img src="{{ asset('images/background/NPC.webp') }}" alt="" width="182" height="202" class="w-32 sm:w-40">
        <div>
            <h1 class="text-3xl font-bold tracking-wide text-white text-shadow-sm text-shadow-black/50 sm:text-4xl">
                {{ __('app.not_found') }}
            </h1>
            <p class="mt-2 text-lg text-gray-400 ltr:font-light">{{ __('app.not_found_hint') }}</p>
        </div>
    </div>
</x-layouts.app>
