{{-- Skill Point Books --}}
<div class="flex items-center gap-0.5 text-base text-white" dir="ltr">
    <img src="{{ asset('images/tree/book.webp') }}" alt="{{ $labels['books'] }}" title="{{ $labels['books'] }}"
        class="me-1 size-8" :class="state.level < bookLevel && 'grayscale'">
    <div class="flex items-center gap-0.5" dir="{{ $dir }}" data-capture-shrink>
        <x-step-button icon="size-3.5" x-on:click="stepBooks(-1)" x-bind:disabled="state.level < bookLevel || state.books <= 0" />
        <input type="text" inputmode="numeric" x-model="booksInput" aria-label="{{ $labels['books'] }}" :disabled="state.level < bookLevel" data-capture-text
            @change="commitBooks()" @keydown.enter.prevent="$event.target.blur()"
            class="w-5 min-w-0 border-0 border-b-2 border-current bg-transparent p-0 text-center leading-tight caret-white focus:outline-hidden disabled:cursor-default">
        <x-step-button plus icon="size-3.5" x-on:click="stepBooks(1)" x-bind:disabled="state.level < bookLevel || state.books >= maxBooks" />
    </div>
    <span class="ms-0.5 text-lg leading-tight" x-text="'/' + maxBooks" data-capture-total></span>
</div>
