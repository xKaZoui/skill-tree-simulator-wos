<section class="group/tree flex flex-col bg-tree-panel" dir="ltr">
    {{-- Header --}}
    <header class="relative flex h-12 items-center justify-center bg-tree-ledge-top pb-2">
        <div class="inline-block w-2/5 rounded-sm border border-zinc-700 bg-tree-frame text-center font-light text-nowrap group-hover/tree:bg-tree-hover group-hover/tree:shadow-white rtl:w-2/4"
            dir="{{ $dir }}">
            <h2 class="px-1" x-text="tree.name"></h2>
        </div>
        <button type="button" @click="resetTree(tree.id)" dir="{{ $dir }}"
            class="absolute top-2 right-2 rounded-sm border border-zinc-700 bg-tree-frame px-2 py-0.5 text-center font-light whitespace-nowrap hover:bg-tree-hover hover:shadow-white">
            {{ $labels['reset'] }}
        </button>
    </header>

    {{-- The panel + footer --}}
    <div data-tree-body class="flex flex-1 flex-col overflow-hidden bg-tree-tile" :style="`zoom:${zoom}`">
        <div class="relative select-none overflow-hidden" :style="panelSize(tree)">

            {{-- Connectors --}}
            <div class="pointer-events-none absolute inset-0 z-3 opacity-59" aria-hidden="true">
                <template x-for="c in tree.layout.connectors" :key="c.key">
                    <span>
                        <template x-for="(s, i) in c.segs" :key="i">
                            <i class="absolute bg-tree-line" :style="box(s)"></i>
                        </template>
                        <template x-for="(h, i) in c.heads" :key="'h' + i">
                            <i class="absolute bg-tree-line" :class="{ 'arrowhead-d': h.dir === 'd', 'arrowhead-u': h.dir === 'u', 'arrowhead-l': h.dir === 'l', 'arrowhead-r': h.dir === 'r' }" :style="box(h)"></i>
                        </template>
                    </span>
                </template>
            </div>

            {{-- Job dividers --}}
            <template x-for="d in tree.layout.dividers" :key="d.tier">
                <div class="pointer-events-none absolute inset-x-0 z-2 inline-flex -translate-y-1/2 items-center justify-center"
                    :style="`top:${d.y}px`">
                    <hr class="h-px grow border-0 bg-linear-to-l from-gray-300">
                    <span class="px-3 text-sm whitespace-nowrap font-light text-gray-300" dir="{{ $dir }}" x-text="dividerLabels[d.tier] ?? ''"></span>
                    <hr class="h-px grow border-0 bg-linear-to-r from-gray-300">
                </div>
            </template>

            {{-- Skills --}}
            <template x-for="cell in tree.cells" :key="cell.id">
                <div class="absolute size-8.5" :style="at(cell)">
                    <button type="button" data-skill :data-skill-id="cell.id" class="group no-callout absolute inset-0 z-4 block touch-manipulation"
                        :class="{ 'animate-reduced': recentlyReduced.includes(cell.id) }" :aria-label="cell.skill.name"
                        @click="tap(cell.id, $event)" @contextmenu.prevent="contextMenu(cell.id, $event)"
                        @pointerenter="pointerEnter(cell.id, $event)" @pointermove="pointerMove($event)"
                        @pointerleave="pointerLeave($event)" @pointerdown="pointerDown(cell.id, $event)"
                        @pointercancel="cancelPress()">
                        <img class="block size-full" :src="cell.skill.image" :alt="cell.skill.name" draggable="false"
                            :class="points(cell.id) > 0
                                ? 'group-hover:brightness-110'
                                : (canAdd(cell.id) ? 'grayscale group-hover:brightness-125' : 'grayscale')">
                        {{-- current/max --}}
                        <span class="pointer-events-none absolute inset-x-0 bottom-0.5 text-center text-xxs font-medium text-white text-shadow-points"
                            x-text="`${points(cell.id)}/${cell.skill.maxPoints}`"></span>
                    </button>

                    {{-- golden frame --}}
                    <img x-show="canAdd(cell.id)" class="skill-ready-frame pointer-events-none z-4"
                        src="{{ $assets['tree'] }}/frame_ready.webp" alt="" draggable="false">

                    {{-- guardian orbs + points --}}
                    <template x-for="(value, g) in cell.skill.guardians" :key="g">
                        <span class="skill-stat pointer-events-none z-2 text-xxs/2.5 text-white"
                            :style="`top:${g * 12}px;background-image:url(${guardianOrbs[g]})`" x-text="value ?? 0"></span>
                    </template>
                </div>
            </template>
        </div>

        {{-- Tree 4 extra space under the last row --}}
        <div x-show="tree.position === 4" class="h-20 grow"></div>

        {{-- Points spent in this tree --}}
        <footer class="relative">
            <div class="relative z-1 mb-1 flex items-center justify-center text-xs font-thin">
                <img src="{{ $assets['tree'] }}/icon_points_gold.webp" alt="" class="absolute left-2">
                <span dir="{{ $dir }}">
                    {{ trim($labels['total_points']) }}:
                    <span class="ms-1 font-normal text-yellow-400" x-text="treeTotal(tree.id)"></span>
                </span>
            </div>
            <div class="h-5 bg-tree-ledge-bottom"></div>
        </footer>
    </div>
</section>
