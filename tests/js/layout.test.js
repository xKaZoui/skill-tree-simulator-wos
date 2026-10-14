import { test } from 'node:test';
import assert from 'node:assert/strict';
import { layoutTree, GAME } from '../../resources/js/skill-tree/layout.js';

const skill = (id, row, column, jobTier = 1) => ({ id, row, column, jobTier });
const cell = (L, id) => L.cells.find((c) => c.id === id);
const conn = (L, key) => L.connectors.find((c) => c.key === key);

const left = GAME.padding.left;
const top = GAME.padding.top;
const [px, py] = GAME.pitch;

test('grid positions become pixel positions', () => {
    const L = layoutTree({ skills: [skill(1, 0, 0), skill(2, 2, 3)], connections: [] });
    assert.deepEqual(cell(L, 1), { id: 1, x: left, y: top });
    assert.deepEqual(cell(L, 2), { id: 2, x: left + 3 * px, y: top + 2 * py });
});

test('two skills in the same cell are rejected', () => {
    assert.throws(() => layoutTree({ skills: [skill(1, 0, 0), skill(2, 0, 0)], connections: [] }));
});

test('a connection to a skill outside the tree is rejected', () => {
    assert.throws(() => layoutTree({ skills: [skill(1, 0, 0)], connections: [{ from: 1, to: 9 }] }));
});

test('straight arrow down: one segment and a downward head', () => {
    const L = layoutTree({ skills: [skill(1, 0, 0), skill(2, 1, 0)], connections: [{ from: 1, to: 2 }] });
    const c = conn(L, '1>2');
    assert.equal(c.segs.length, 1);
    assert.equal(c.segs[0].w, GAME.stroke);
    assert.equal(c.heads[0].dir, 'd');
    assert.equal(c.heads[0].y + c.heads[0].h, cell(L, 2).y - GAME.entryGap);
    assert.deepEqual(L.warnings, []);
});

test('diagonal neighbours get down / sideways / down', () => {
    const L = layoutTree({ skills: [skill(1, 0, 1), skill(2, 2, 0)], connections: [{ from: 1, to: 2 }] });
    const c = conn(L, '1>2');
    assert.equal(c.segs.length, 3);
    assert.equal(c.heads[0].dir, 'd');
    const bar = c.segs[1];
    assert.equal(bar.y + GAME.stroke / 2, cell(L, 2).y - GAME.barOffset);
});

test('same row gives a sideways arrow', () => {
    const L = layoutTree({ skills: [skill(1, 0, 2), skill(2, 0, 3)], connections: [{ from: 2, to: 1 }] });
    assert.equal(conn(L, '2>1').heads[0].dir, 'l');
});

test('a line that passes behind another icon is reported', () => {
    const L = layoutTree({
        skills: [skill(1, 0, 0), skill(2, 1, 0), skill(3, 2, 0)],
        connections: [{ from: 1, to: 3 }],
    });
    assert.deepEqual(L.warnings, ['1>3: line passes behind skill 2.']);
});

test('a custom route goes around it', () => {
    const L = layoutTree({
        skills: [skill(1, 0, 0), skill(2, 1, 0), skill(3, 2, 0)],
        connections: [{ from: 1, to: 3, route: { path: [['l', 10], ['d', 'auto'], ['r']], gap: 1 } }],
    });
    const c = conn(L, '1>3');
    assert.deepEqual(L.warnings, []);
    assert.equal(c.segs.length, 3);
    assert.equal(c.heads[0].dir, 'r');
    assert.equal(c.heads[0].x + c.heads[0].w, cell(L, 3).x - 1); // gap 1
});

test('route lengths in columns and rows', () => {
    const L = layoutTree({
        skills: [skill(1, 0, 1), skill(2, 2, 0)],
        connections: [{ from: 1, to: 2, route: { path: [['d', 9], ['l', '1c'], ['d']] } }],
    });
    const bar = conn(L, '1>2').segs[1];
    assert.equal(bar.w, px + GAME.stroke);
    assert.deepEqual(L.warnings, []);
});

test('a route that misses the target is reported', () => {
    const L = layoutTree({
        skills: [skill(1, 0, 1), skill(2, 2, 0)],
        connections: [{ from: 1, to: 2, route: { path: [['d', 9], ['l', 50], ['d']] } }],
    });
    assert.equal(L.warnings.length, 1);
    assert.match(L.warnings[0], /off the target/);
});

test('job dividers sit between tiers', () => {
    const skills = [skill(1, 0, 0, 1), skill(2, 3, 0, 1), skill(3, 4, 0, 2), skill(4, 8, 0, 3)];
    const L = layoutTree({ skills, connections: [] });
    assert.deepEqual(L.dividers.map((d) => d.tier), [2, 3]);
    const y3 = cell(L, 2).y + GAME.icon;
    assert.ok(L.dividers[0].y > y3 && L.dividers[0].y < cell(L, 3).y);
});

test('dividerGap adds space before each new tier', () => {
    const skills = [skill(1, 3, 0, 1), skill(2, 4, 0, 2), skill(3, 8, 0, 3)];
    const plain = layoutTree({ skills, connections: [] });
    const spaced = layoutTree({ skills, connections: [] }, { dividerGap: 20 });
    assert.equal(cell(spaced, 1).y, cell(plain, 1).y);
    assert.equal(cell(spaced, 2).y, cell(plain, 2).y + 20);
    assert.equal(cell(spaced, 3).y, cell(plain, 3).y + 40);
    assert.equal(spaced.height, plain.height + 40);
});