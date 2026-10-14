{{-- Loading screen --}}
<div {{ $attributes }} x-transition:leave="transition-opacity duration-300" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-60 flex flex-col items-center justify-center gap-6 bg-gray-900" role="status" aria-live="polite">
    <span class="sr-only">{{ __('app.name') }}</span>
    <img src="{{ asset('images/logos/logo-' . (app()->getLocale() === 'ar' ? 'ar' : 'en') . '.webp') }}" alt=""
        width="256" height="256" onload="this.classList.remove('opacity-0')"
        class="size-40 opacity-0 transition-opacity duration-200 motion-safe:animate-bounce">
    <svg class="size-10 text-yellow-500 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
        <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
    </svg>
    @if ($slot->isNotEmpty())
        <p class="text-lg text-white/80">{{ $slot }}</p>
    @endif
</div>
