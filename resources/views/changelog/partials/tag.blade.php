{{-- Buff (green) or Nerf (red) --}}
<span class="ms-1 inline-block rounded-xs align-middle whitespace-nowrap border border-current bg-black/30 px-1.5 text-xs/4.5 font-semibold tracking-wide uppercase {{ $buff ? 'text-green-400' : 'text-red-400' }}">
    {{ __($buff ? 'changelog.buff' : 'changelog.nerf') }}
</span>
