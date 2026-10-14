{{-- Round − / + of the level box --}}
@props(['plus' => false])
<svg viewBox="0 0 11 11" fill="currentColor" fill-rule="evenodd" aria-hidden="true" {{ $attributes }}>
    <path d="M0 5.5a5.5 5.5 0 1 0 11 0a5.5 5.5 0 1 0-11 0Z{{ $plus ? 'M5 2h1v3h3v1h-3v3h-1v-3h-3v-1h3Z' : 'M2 5h7v1h-7Z' }}" />
</svg>
