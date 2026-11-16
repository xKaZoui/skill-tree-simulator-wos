{{-- Game state icon and its name and description show on hover or tap --}}
<span tabindex="0" class="group relative inline-block align-text-bottom">
    <img src="{{ asset("images/states/{$state}.webp") }}" alt="{{ $name ?? '' }}" width="24" height="24" class="ms-1 inline size-6">
    @isset($name)
        <span class="pointer-events-none invisible absolute top-full left-1/2 z-50 mt-1 w-56 -translate-x-1/2 rounded-xs border border-white bg-black/90 p-2 text-start text-sm font-normal text-white group-hover:visible group-focus:visible">
            <span class="block font-bold text-yellow-400">{{ $name }}</span>
            {{ $description }}
        </span>
    @endisset
</span>
