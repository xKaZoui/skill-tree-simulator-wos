{{-- Round − / + button of the level and book boxes --}}
@props(['plus' => false, 'icon' => 'size-4'])
<button type="button" data-no-capture aria-label="{{ $plus ? '+1' : '-1' }}"
    {{ $attributes->class('flex h-full shrink-0 items-center justify-center text-white/60 hover:text-white disabled:cursor-default disabled:opacity-25') }}>
    <x-icons.level-step :plus="$plus" :class="$icon" />
</button>
