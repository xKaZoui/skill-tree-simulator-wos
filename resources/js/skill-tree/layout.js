
// Skill tree layout

/*
 *   route = {
 *     path:  [['d', 60], ['l', '1c'], ['d']],  // turtle walk, see below
 *     gap:   2,        // px between arrow tip and target icon
 *     out:   1,        // px between source icon and line start
 *     nudge: [0, -2],  // shifts where the arrow meets the target
 *   }
 *
 *   path legs: [dir, length]
 *     dir     'd' | 'u' | 'l' | 'r'
 *     length  px (number), '1c' = one column pitch, '2r' = two row pitches,
 *             or 'auto' / omitted:
 *               last leg   -> run until the arrowhead
 *               other legs -> run until level with the target's entry line
 */

export const GAME = {
    icon: 32,
    pitch: [80, 53],      // column, row distance between icon top-left corners
    padding: { top: 13, left: 19, right: 11, bottom: 13 },
    cellWidth: 70,        // icon + orbs + stat digits, used for the panel width
    stroke: 4,            // line thickness
    anchor: 17,           // lines run 17px in from an icon's left/top edge
    exitGap: 1,           // px between an icon and the line leaving it
    entryGap: 2,          // px between an arrow tip and the icon it points at
    headW: 10,            // arrowhead width (across the line)
    headL: 5,             // arrowhead length (along the line)
    barOffset: 11,        // default horizontal bar position above the target
    dividerGap: 0,        // extra px inserted between job tiers
};

const VEC = { d: [0, 1], u: [0, -1], r: [1, 0], l: [-1, 0] };
const VERTICAL = { d: true, u: true, r: false, l: false };

/**
 * tree = {
 *   skills:      [{ id, row, column, jobTier }],
 *   connections: [{ from, to, route? }],
 * }
 * options: overrides (e.g. { dividerGap: 24 }).
 */
export function layoutTree(tree, options = {}) {
    const M = { ...GAME, ...options, padding: { ...GAME.padding, ...(options.padding ?? {}) } };
    const warnings = [];

    // Job tier dividers = one before the first row of each later tier.
    const firstRowOfTier = new Map();
    for (const s of tree.skills) {
        const current = firstRowOfTier.get(s.jobTier);
        if (current === undefined || s.row < current) firstRowOfTier.set(s.jobTier, s.row);
    }
    const tiers = [...firstRowOfTier.keys()].sort((a, b) => a - b);
    const dividerRows = tiers.slice(1).map((tier) => ({ tier, row: firstRowOfTier.get(tier) }));
    const dividersBefore = (row) => dividerRows.filter((d) => d.row <= row).length;

    const rowY = (row) => M.padding.top + row * M.pitch[1] + dividersBefore(row) * M.dividerGap;
    const colX = (col) => M.padding.left + col * M.pitch[0];

    // Icons
    const rects = new Map();
    const cells = tree.skills.map((s) => {
        const rect = { x: colX(s.column), y: rowY(s.row), w: M.icon, h: M.icon };
        if ([...rects.values()].some((r) => r.x === rect.x && r.y === rect.y)) {
            throw new Error(`Skill ${s.id}: grid cell ${s.row},${s.column} is already used.`);
        }
        rects.set(s.id, rect);
        return { id: s.id, x: rect.x, y: rect.y };
    });

    const maxRow = Math.max(0, ...tree.skills.map((s) => s.row));
    const maxCol = Math.max(0, ...tree.skills.map((s) => s.column));
    const width = colX(Math.max(maxCol, 4)) + M.cellWidth + M.padding.right;
    const height = rowY(maxRow) + M.icon + M.padding.bottom;

    // Divider sits in the middle of the gap above its row.
    const dividers = dividerRows.map(({ tier, row }) => {
        const gapTop = rowY(row - 1) + M.icon;
        return { tier, y: Math.round((gapTop + rowY(row)) / 2) };
    });

    // Connectors
    function exitPoint(r, dir, out = M.exitGap) {
        switch (dir) {
            case 'd': return { x: r.x + M.anchor, y: r.y + r.h + out };
            case 'u': return { x: r.x + M.anchor, y: r.y - out };
            case 'r': return { x: r.x + r.w + out, y: r.y + M.anchor };
            case 'l': return { x: r.x - out, y: r.y + M.anchor };
        }
        throw new Error(`Bad direction ${dir}`);
    }

    // Where the last leg ends and where the arrowhead goes (dir = travel direction).
    function entry(r, dir, gap = M.entryGap, nudge = [0, 0]) {
        const [nx, ny] = nudge;
        const L = M.headL;
        const W = M.headW;
        let end;
        let head;
        switch (dir) {
            case 'd': end = { x: r.x + M.anchor, y: r.y - gap - L };
                head = { x: r.x + M.anchor - W / 2, y: r.y - gap - L, w: W, h: L }; break;
            case 'u': end = { x: r.x + M.anchor, y: r.y + r.h + gap + L };
                head = { x: r.x + M.anchor - W / 2, y: r.y + r.h + gap, w: W, h: L }; break;
            case 'r': end = { x: r.x - gap - L, y: r.y + M.anchor };
                head = { x: r.x - gap - L, y: r.y + M.anchor - W / 2, w: L, h: W }; break;
            case 'l': end = { x: r.x + r.w + gap + L, y: r.y + M.anchor };
                head = { x: r.x + r.w + gap, y: r.y + M.anchor - W / 2, w: L, h: W }; break;
            default: throw new Error(`Bad direction ${dir}`);
        }
        return {
            end: { x: end.x + nx, y: end.y + ny },
            head: { ...head, x: head.x + nx, y: head.y + ny, dir },
        };
    }

    function autoPath(a, b, out) {
        if (a.x === b.x) return [[b.y > a.y ? 'd' : 'u']];
        if (a.y === b.y) return [[b.x > a.x ? 'r' : 'l']];
        const side = b.x > a.x ? 'r' : 'l';
        const dx = Math.abs(b.x - a.x);
        if (b.y > a.y) {
            return [['d', b.y - M.barOffset - exitPoint(a, 'd', out).y], [side, dx], ['d']];
        }
        return [['u', exitPoint(a, 'u', out).y - (b.y + b.h + M.barOffset)], [side, dx], ['u']];
    }

    function parseLength(len) {
        if (typeof len === 'number') return len;
        const m = /^(\d+(?:\.\d+)?)([cr])$/.exec(String(len));
        if (!m) throw new Error(`Bad route length: ${len}`);
        return parseFloat(m[1]) * (m[2] === 'c' ? M.pitch[0] : M.pitch[1]);
    }

    function strokeRects(pts) {
        const h = M.stroke / 2;
        const out = [];
        for (let i = 0; i < pts.length - 1; i++) {
            const p = pts[i];
            const q = pts[i + 1];
            const vertical = p.x === q.x;
            const sign = vertical ? Math.sign(q.y - p.y) : Math.sign(q.x - p.x);
            const a0 = (vertical ? p.y : p.x) - (i > 0 ? sign * h : 0);
            const a1 = (vertical ? q.y : q.x) + (i < pts.length - 2 ? sign * h : 0);
            const lo = Math.min(a0, a1);
            const len = Math.abs(a1 - a0);
            if (len === 0) continue;
            out.push(vertical
                ? { x: p.x - h, y: lo, w: M.stroke, h: len }
                : { x: lo, y: p.y - h, w: len, h: M.stroke });
        }
        return out;
    }

    const connectors = tree.connections.map((c) => {
        const key = `${c.from}>${c.to}`;
        const a = rects.get(c.from);
        const b = rects.get(c.to);
        if (!a || !b) throw new Error(`Connection ${key}: skill not in this tree.`);

        const route = c.route ?? {};
        const path = route.path ?? autoPath(a, b, route.out);
        const last = path[path.length - 1][0];
        const ent = entry(b, last, route.gap, route.nudge);

        let cur = exitPoint(a, path[0][0], route.out);
        const pts = [cur];
        path.forEach(([dir, len], i) => {
            if (!VEC[dir]) throw new Error(`${key}: bad direction ${dir}.`);
            let next;
            if (len === undefined || len === 'auto') {
                if (i < path.length - 1 && VERTICAL[dir] === VERTICAL[last]) {
                    throw new Error(`${key}: an 'auto' leg must be perpendicular to the last leg.`);
                }
                next = VERTICAL[dir] ? { x: cur.x, y: ent.end.y } : { x: ent.end.x, y: cur.y };
                if (i === path.length - 1) {
                    const off = VERTICAL[dir] ? cur.x - ent.end.x : cur.y - ent.end.y;
                    if (off !== 0) warnings.push(`${key}: last leg is ${off}px off the target.`);
                }
            } else {
                const n = parseLength(len);
                next = { x: cur.x + VEC[dir][0] * n, y: cur.y + VEC[dir][1] * n };
            }
            pts.push(next);
            cur = next;
        });

        const segs = strokeRects(pts);

        // Lines are drawn behind icons
        for (const [id, r] of rects) {
            if (id === c.from || id === c.to) continue;
            if (segs.some((s) => s.x < r.x + r.w && s.x + s.w > r.x && s.y < r.y + r.h && s.y + s.h > r.y)) {
                warnings.push(`${key}: line passes behind skill ${id}.`);
            }
        }

        return { key, from: c.from, to: c.to, segs, heads: [ent.head] };
    });

    return { width, height, cells, connectors, dividers, warnings };
}