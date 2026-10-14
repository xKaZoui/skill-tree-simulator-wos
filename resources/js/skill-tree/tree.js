import { createEngine, MIN_LEVEL } from './engine.js';
import { layoutTree } from './layout.js';
import { skillCard } from './tooltip.js';
import { encodeBuild, decodeBuild } from './build-url.js';
import { domToForeignObjectSvg } from 'modern-screenshot';

// Extra vertical space for job divider
export const DIVIDER_GAP = 24;

// Icon size and where lines meet it
export const ICON = 34;
export const ANCHOR = 18;

// Largest scale for the tree panels
export const MAX_ZOOM = 1.5;

// Smallest scale for 4 trees in one row (wide screens), below it tree 4 goes under the others.
const FOUR_IN_ROW_ZOOM = 0.82;

// Side by side trees also shrink so the board fits the window height, only when it fits at this scale or more
const MIN_FIT_ZOOM = 1;

// Tree scale in the downloaded image (same picture from every screen)
const IMAGE_ZOOM = 1.1;

// Width around the trees = page padding, white border, side ledges, lines between trees
const PAGE_CHROME = 32 + 8 + 40 + 8;

// Longest wait for images before the loading screen goes away anyway
const LOADING_TIMEOUT_MS = 5000;

// Longest wait for the downloaded image
const IMAGE_TIMEOUT_MS = 60000;

// Skill card = touch hold time, finger movement that cancels a hold, distance from the pointer, screen margin
const LONG_PRESS_MS = 500;
const MOVE_TOLERANCE = 10;
const CARD_OFFSET = 15;
const CARD_MARGIN = 8;

export function skillTreePage(payload) {
    const engine = createEngine(payload);
    const skillsById = new Map(payload.skills.map((s) => [s.id, s]));
    const treeNames = new Map(payload.trees.map((t) => [t.id, t.name]));
    const requiredBy = new Map();
    for (const c of payload.connections) {
        if (!requiredBy.has(c.to)) requiredBy.set(c.to, []);
        requiredBy.get(c.to).push(skillsById.get(c.from));
    }

    // Touch hold in progress and what the card is placed next to
    let press = null;
    let anchor = null;
    let lastPointer = 'mouse';
    let copiedTimer = null;

    const trees = payload.trees.map((tree) => {
        const skills = payload.skills.filter((s) => s.treeId === tree.id);
        const ids = new Set(skills.map((s) => s.id));
        const layout = layoutTree(
            { skills, connections: payload.connections.filter((c) => ids.has(c.from)) },
            { dividerGap: DIVIDER_GAP, icon: ICON, anchor: ANCHOR },
        );
        for (const w of layout.warnings) console.warn(`[skill-tree] tree ${tree.id}: ${w}`);

        return {
            ...tree,
            layout,
            cells: layout.cells.map((c) => ({ ...c, skill: skillsById.get(c.id) })),
        };
    });

    return {
        trees,
        state: engine.emptyState(payload.defaultLevel ?? MIN_LEVEL),
        levelInput: String(payload.defaultLevel ?? MIN_LEVEL),
        minLevel: MIN_LEVEL,
        maxLevel: payload.maxLevel ?? 250,
        recentlyReduced: [],
        dividerLabels: payload.labels?.dividers ?? {},
        guardianOrbs: payload.assets.orbs,
        zoom: 1,
        sideBySide: true,
        // wide screens = tree 4 next to trees 1-3
        fourInRow: false,
        // false while the loading screen is shown
        ready: false,
        // true while the image is being made
        saving: false,
        // true for a moment after the link is copied
        linkCopied: false,
        // skill card = shown skill, pinned by a long press, position, measured and placed
        tip: { id: null, pinned: false, x: 0, y: 0, ready: false },

        init() {
            this.restoreBuild();
            this.$watch('state', () => this.saveBuild());
            this.fit();
            // after Alpine has drawn the trees, wait for every image on the page (header, icons, backgrounds)
            this.$nextTick(() => {
                // again, now that the trees are drawn and the window height can be used
                this.fit();
                const images = Array.from(document.images).filter((img) => !img.complete);
                const loaded = Promise.all(images.map((img) => new Promise((done) => {
                    img.addEventListener('load', done, { once: true });
                    img.addEventListener('error', done, { once: true });
                })));
                const timeout = new Promise((done) => setTimeout(done, LOADING_TIMEOUT_MS));
                Promise.race([loaded, timeout]).then(() => {
                    this.ready = true;
                    this.openLinkedSkill();
                });
            });
        },

        // Build in the URL (#level:id-points,...): read on load, rewritten after every change (not affected by language switch).
        restoreBuild() {
            let build = null;
            try {
                build = decodeBuild(decodeURIComponent(location.hash.slice(1)));
            } catch {
                // a broken '#' part is ignored
            }
            if (!build || build.level < MIN_LEVEL || build.level > (payload.maxLevel ?? 250)) return;
            this.state = engine.normalize(build);
            this.levelInput = String(build.level);
            this.saveBuild();
        },
        saveBuild() {
            const empty = Object.keys(this.state.points).length === 0 && this.state.level === (payload.defaultLevel ?? MIN_LEVEL);
            const hash = empty ? '' : `#${encodeBuild(this.state)}`;
            history.replaceState(history.state, '', location.pathname + location.search + hash);
            for (const link of document.querySelectorAll('a[data-keep-build]')) {
                link.href = link.href.split('#')[0] + hash;
            }
        },

        // "Open tree" on the change log links to ?skill=id: show that skill's card once the page is ready.
        // It stays until the pointer goes over another skill, or a click/tap outside the card.
        openLinkedSkill() {
            const params = new URLSearchParams(location.search);
            const id = Number(params.get('skill'));
            params.delete('skill');
            history.replaceState(history.state, '', location.pathname + (params.size ? `?${params}` : '') + location.hash);

            const el = skillsById.has(id) ? document.querySelector(`[data-skill-id="${id}"]`) : null;
            if (!el) return;
            el.scrollIntoView({ block: 'center', inline: 'center' });
            anchor = { el };
            this.showCard(id, false);

            const follow = () => { if (this.tip.id === id) this.placeCard(); };
            const stop = () => {
                window.removeEventListener('pointerover', over);
                window.removeEventListener('pointerdown', down);
                window.removeEventListener('scroll', follow);
            };
            // another skill takes over the card (its own hover shows it)
            const over = (event) => {
                const skill = event.target.closest('[data-skill-id]');
                if (skill && skill !== el) stop();
            };
            const down = (event) => {
                if (event.target.closest('[data-skill-card]')) return;
                stop();
                if (this.tip.id === id) this.hideCard();
            };
            window.addEventListener('pointerover', over);
            window.addEventListener('pointerdown', down);
            window.addEventListener('scroll', follow, { passive: true });
        },

        // Copies the page link
        async copyLink() {
            const url = location.href;
            try {
                await navigator.clipboard.writeText(url);
            } catch {
                // older browsers / no permission: copy through a hidden text box
                const box = document.createElement('textarea');
                box.value = url;
                document.body.append(box);
                box.select();
                document.execCommand('copy');
                box.remove();
            }
            this.linkCopied = true;
            clearTimeout(copiedTimer);
            copiedTimer = setTimeout(() => { this.linkCopied = false; }, 2000);
        },

        // Saves the page (level, points, trees) as a PNG. The picture looks the same on every screen.
        async downloadImage() {
            if (this.saving) return;
            this.saving = true;
            this.hideCard();
            const before = { zoom: this.zoom, sideBySide: this.sideBySide, fourInRow: this.fourInRow };
            const scroll = { x: window.scrollX, y: window.scrollY };
            Object.assign(this, { zoom: IMAGE_ZOOM, sideBySide: true, fourInRow: this.fourthTree !== null });
            await this.$nextTick();
            try {
                const timeout = new Promise((_, fail) => setTimeout(() => fail(new Error('image took too long')), IMAGE_TIMEOUT_MS));
                const blob = await Promise.race([this.sheetToPng(), timeout]);
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.download = `${payload.class}-level-${this.state.level}.png`;
                link.href = url;
                link.click();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            } catch (error) {
                console.error('[skill-tree] download image:', error);
            } finally {
                Object.assign(this, before);
                // back where the page was before the layout change
                await this.$nextTick();
                window.scrollTo(scroll.x, scroll.y);
                this.saving = false;
            }
        },

        // The sheet copied into an SVG picture, then drawn on a canvas a few times = Firefox and Safari can paint
        async sheetToPng() {
            const scale = Math.min(2, window.devicePixelRatio || 1);
            const svg = await domToForeignObjectSvg(this.$refs.sheet, {
                features: { fixSvgXmlDecode: false },
                filter: (node) => !(node instanceof Element && node.hasAttribute('data-no-capture')),
                // Alpine attributes (@click, :class, x-...) are not valid in the SVG
                onCloneEachNode: (node) => {
                    if (!(node instanceof Element)) return;
                    for (const { name } of [...node.attributes]) {
                        if (/^(@|:|x-)/.test(name)) node.removeAttribute(name);
                    }
                },
                onCloneNode: (clone) => {
                    // the ring drawn as a plain border instead of a shadow
                    for (const box of clone.querySelectorAll('[data-plain-ring]')) {
                        box.style.boxShadow = 'none';
                        box.style.border = '1px solid rgb(209 213 219)';
                    }
                    for (const input of clone.querySelectorAll('input')) {
                        const box = document.createElement('div');
                        for (const { name, value } of [...input.attributes]) {
                            if (name === 'class' || name === 'style') box.setAttribute(name, value);
                        }
                        box.style.display = 'inline-flex';
                        box.style.alignItems = 'center';
                        box.style.justifyContent = 'center';
                        // no native field look
                        box.style.appearance = 'none';
                        box.textContent = input.getAttribute('value') ?? '';
                        input.replaceWith(box);
                    }
                },
            });
            const width = Number(svg.getAttribute('width'));
            const height = Number(svg.getAttribute('height'));
            const picture = new Image();
            picture.src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(new XMLSerializer().serializeToString(svg))}`;
            await picture.decode();

            const canvas = document.createElement('canvas');
            canvas.width = Math.round(width * scale);
            canvas.height = Math.round(height * scale);
            const context = canvas.getContext('2d');
            for (let i = 0; i < 4; i++) {
                context.clearRect(0, 0, canvas.width, canvas.height);
                context.drawImage(picture, 0, 0, canvas.width, canvas.height);
                await new Promise((done) => setTimeout(done, 200));
            }
            return new Promise((done, fail) => canvas.toBlob((blob) => (blob ? done(blob) : fail(new Error('empty image'))), 'image/png'));
        },

        // Derived values
        get summary() {
            return engine.summary(this.state);
        },
        get mainTrees() {
            return this.trees.filter((t) => t.position < 4);
        },
        get fourthTree() {
            return this.trees.find((t) => t.position === 4 && t.cells.length > 0) ?? null;
        },
        treeTotal(treeId) {
            return this.summary.treeTotals.get(treeId) ?? 0;
        },
        points(id) {
            return this.state.points[id] ?? 0;
        },
        canAdd(id) {
            return engine.canAdd(this.state, id);
        },
        get card() {
            const skill = skillsById.get(this.tip.id);
            if (!skill) return null;

            return skillCard(skill, {
                points: this.points(skill.id),
                canAdd: this.canAdd(skill.id),
                labels: payload.labels?.card ?? {},
                locale: payload.locale,
                treeName: treeNames.get(skill.treeId) ?? '',
                required: requiredBy.get(skill.id) ?? [],
                investment: skill.requiredInvestmentTreeId
                    ? { points: skill.requiredInvestmentPoints, treeName: treeNames.get(skill.requiredInvestmentTreeId) ?? '' }
                    : null,
                baseTotal: skill.requiredTotalBasePoints
                    ? { points: skill.requiredTotalBasePoints, treeNames: payload.trees.filter((t) => t.position < 4).map((t) => t.name) }
                    : null,
            });
        },

        // Actions
        // Red outline on skills that lost points as a side effect = a level drop, or a removal that cascades to other skills.
        apply(next, { outline = true, except = null } = {}) {
            this.recentlyReduced = outline ? engine.reduced(this.state, next).filter((id) => id !== except) : [];
            this.state = next;
        },
        click(id, event) {
            this.apply(engine.add(this.state, id, { max: event.ctrlKey || event.metaKey }));
        },
        rightClick(id, event) {
            this.apply(engine.remove(this.state, id, { all: event.ctrlKey || event.metaKey }), { except: id });
        },
        resetTree(treeId) {
            this.apply(engine.resetTree(this.state, treeId), { outline: false });
        },
        resetAll() {
            this.apply(engine.resetAll(this.state), { outline: false });
        },
        commitLevel() {
            const level = Number.parseInt(this.levelInput, 10);
            if (!Number.isInteger(level) || level < MIN_LEVEL || level > (payload.maxLevel ?? 250)) {
                this.levelInput = String(this.state.level);
                return;
            }
            this.apply(engine.setLevel(this.state, level));
            this.levelInput = String(level);
        },
        // - and + in the level box
        stepLevel(step) {
            const level = Math.min(Math.max(this.state.level + step, this.minLevel), this.maxLevel);
            this.levelInput = String(level);
            this.commitLevel();
        },

        // Skill card. 
        // Mouse: shows while hovering a skill and follows the pointer.
        // Touch: a long press pins it; another long press on the same skill, a tap on another skill or a tap outside closes it.
        // A normal tap still adds a point.
        pointerEnter(id, event) {
            if (event.pointerType !== 'mouse' || this.tip.pinned) return;
            anchor = { x: event.clientX, y: event.clientY };
            this.showCard(id, false);
        },
        pointerMove(event) {
            if (press && Math.hypot(event.clientX - press.x, event.clientY - press.y) > MOVE_TOLERANCE) this.cancelPress();
            if (event.pointerType !== 'mouse' || this.tip.pinned || this.tip.id === null) return;
            anchor = { x: event.clientX, y: event.clientY };
            this.placeCard();
        },
        pointerLeave(event) {
            if (event.pointerType !== 'mouse') this.cancelPress();
            else if (!this.tip.pinned) this.hideCard();
        },
        pointerDown(id, event) {
            lastPointer = event.pointerType;
            this.cancelPress();
            press = null;
            if (event.pointerType === 'mouse') return;
            const el = event.currentTarget;
            press = {
                x: event.clientX,
                y: event.clientY,
                fired: false,
                timer: setTimeout(() => this.longPress(id, el), LONG_PRESS_MS),
            };
        },
        cancelPress() {
            if (press && !press.fired) {
                clearTimeout(press.timer);
                press = null;
            }
        },
        longPress(id, el) {
            press.fired = true;
            if (this.tip.pinned && this.tip.id === id) {
                this.hideCard();
                return;
            }
            anchor = { el };
            this.showCard(id, true);
        },
        tap(id, event) {
            // the click that ends a long press does not add a point
            if (press?.fired) {
                press = null;
                return;
            }
            this.cancelPress();
            if (this.tip.pinned && this.tip.id !== id) this.hideCard();
            this.click(id, event);
            if (this.tip.id !== null) this.$nextTick(() => this.placeCard());
        },
        contextMenu(id, event) {
            // a long press on touch screens also opens the context menu = ignore it there
            if (lastPointer !== 'mouse') return;
            this.rightClick(id, event);
            if (this.tip.id !== null) this.$nextTick(() => this.placeCard());
        },
        // Buttons in the pinned card (for touch screens because they got no right click or Ctrl)
        cardAdd(max) {
            this.apply(engine.add(this.state, this.tip.id, { max }));
            this.$nextTick(() => this.placeCard());
        },
        cardRemove(all) {
            this.apply(engine.remove(this.state, this.tip.id, { all }), { except: this.tip.id });
            this.$nextTick(() => this.placeCard());
        },
        outsidePress(event) {
            if (!this.tip.pinned || event.target.closest('[data-skill], [data-skill-card]')) return;
            this.hideCard();
        },
        showCard(id, pinned) {
            this.tip = { ...this.tip, id, pinned, ready: false };
            // measure the card while it is invisible, then place it = no jump, no scrollbars
            this.$nextTick(() => {
                this.placeCard();
                this.tip.ready = true;
            });
        },
        hideCard() {
            this.tip = { ...this.tip, id: null, pinned: false, ready: false };
        },
        placeCard() {
            const card = this.$refs.card;
            if (!card || !anchor || this.tip.id === null) return;

            const vw = document.documentElement.clientWidth;
            const vh = document.documentElement.clientHeight;
            const w = card.offsetWidth;
            const h = card.offsetHeight;
            const clamp = (value, size, max) => Math.max(CARD_MARGIN, Math.min(value, max - size - CARD_MARGIN));
            let x;
            let y;

            if (anchor.el) {
                // under the skill, or above it when there is no room
                const r = anchor.el.getBoundingClientRect();
                x = r.left + r.width / 2 - w / 2;
                y = r.bottom + CARD_MARGIN + h <= vh - CARD_MARGIN ? r.bottom + CARD_MARGIN : r.top - CARD_MARGIN - h;
            } else {
                // beside the pointer, flipped to the other side near the screen edge
                x = anchor.x + CARD_OFFSET + w > vw - CARD_MARGIN ? anchor.x - CARD_OFFSET - w : anchor.x + CARD_OFFSET;
                y = anchor.y + CARD_OFFSET + h > vh - CARD_MARGIN ? anchor.y - CARD_OFFSET - h : anchor.y + CARD_OFFSET;
            }

            this.tip.x = Math.round(clamp(x, w, vw));
            this.tip.y = Math.round(clamp(y, h, vh));
        },

        // Scale the panels to the screen
        fit() {
            const panelWidth = Math.max(...this.trees.map((t) => t.layout.width));
            const available = document.documentElement.clientWidth - PAGE_CHROME;
            // 4 in a row, one more line between the trees (4px)
            this.fourInRow = this.fourthTree !== null && (available - 4) / 4 / panelWidth >= FOUR_IN_ROW_ZOOM;
            this.sideBySide = this.fourInRow || available >= 3 * panelWidth;
            const columns = this.fourInRow ? 4 : (this.sideBySide ? 3 : 1);
            let zoom = (this.fourInRow ? available - 4 : available) / columns / panelWidth;
            // side by side = also fit the board (level box to the tree footers) in the window height, unless that makes the trees too small (then the page scrolls and keeps the width size)
            const heightZoom = this.sideBySide ? this.heightZoom() : Infinity;
            if (heightZoom >= MIN_FIT_ZOOM) zoom = Math.min(zoom, heightZoom);
            this.zoom = Math.min(MAX_ZOOM, Math.floor(zoom * 100) / 100);
        },
        // Scale at which the first tree ends at the bottom of the window, page not scrolled (site header included)
        heightZoom() {
            const body = this.$refs.sheet?.querySelector('[data-tree-body]');
            if (!body) return Infinity;
            const rect = body.getBoundingClientRect();
            const above = rect.top + window.scrollY;
            // 4: sheet border, 8: a little space under it
            return (window.innerHeight - above - 4 - 8) / (rect.height / this.zoom);
        },

        // View helpers
        box(r) {
            return `left:${r.x}px;top:${r.y}px;width:${r.w}px;height:${r.h}px`;
        },
        at(c) {
            return `left:${c.x}px;top:${c.y}px`;
        },
        panelSize(tree) {
            return `width:${tree.layout.width}px;height:${tree.layout.height}px`;
        },
    };
}

// "How to use" card beside the toolbar
export function helpCard() {
    return {
        hover: false,
        open: false,
        x: 0,
        y: 0,
        get visible() {
            return this.hover || this.open;
        },
        show(how) {
            this[how] = true;
            this.$nextTick(() => this.place());
        },
        hide(how) {
            this[how] = false;
        },
        place() {
            const r = this.$refs.button.getBoundingClientRect();
            const w = this.$refs.card.offsetWidth;
            const vw = document.documentElement.clientWidth;
            this.x = Math.round(Math.max(CARD_MARGIN, Math.min(r.right - w, vw - w - CARD_MARGIN)));
            this.y = Math.round(r.bottom);
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('skillTreePage', skillTreePage);
    window.Alpine.data('helpCard', helpCard);
});
