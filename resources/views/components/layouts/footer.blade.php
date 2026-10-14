<footer class="mt-4 py-2 text-center text-sm text-gray-300/75">
    <span dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <x-bi-c-circle class="inline-block h-3 w-3" title="copyright" />
        2026 - {{ __('app.copyright') }}
        <a href="https://www.worldofsecrets.net/"
            class="bg-linear-to-b from-yellow-400 to-amber-600 bg-clip-text font-bold text-transparent underline decoration-yellow-900 decoration-4 underline-offset-2"
            target="_blank" rel="noopener noreferrer" aria-label="{{ __('app.urlLabel') }}"
            title="{{ __('app.urlLabel') }}">{{ __('app.wos') }}</a>.
    </span>
</footer>
