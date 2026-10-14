import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
    createEngine,
    basePool,
    fourthPool,
    jobTierForLevel,
} from '../../resources/js/skill-tree/engine.js';

/*
 * Fixture: one class with 4 trees.
 * Tree 1 (id 1): A(1) r0, B(2) r1, C(3) r1 needs A maxed, K(6) r2, D(4) r2 tier 2 needs C maxed.
 * Tree 2 (id 2): F(10) r0.   Tree 3 (id 3): H(20) r0.
 * Tree 4 (id 4): T1(30) needs 10 points in tree 1; T2(31) needs 30 base points.
 */
const skill = (id, treeId, row, column, maxPoints, extra = {}) => ({
    id, treeId, row, column, maxPoints,
    jobTier: 1,
    requiredPoints: row * 5,
    requiredTotalBasePoints: null,
    requiredInvestmentTreeId: null,
    requiredInvestmentPoints: null,
    ...extra,
});

const data = {
    trees: [
        { id: 1, position: 1 },
        { id: 2, position: 2 },
        { id: 3, position: 3 },
        { id: 4, position: 4 },
    ],
    skills: [
        skill(1, 1, 0, 0, 5),
        skill(2, 1, 1, 0, 5),
        skill(3, 1, 1, 1, 1),
        skill(6, 1, 2, 1, 5),
        skill(4, 1, 2, 0, 3, { jobTier: 2 }),
        skill(10, 2, 0, 0, 5),
        skill(20, 3, 0, 0, 5),
        skill(30, 4, 0, 0, 5, { jobTier: 4, requiredInvestmentTreeId: 1, requiredInvestmentPoints: 10 }),
        skill(31, 4, 0, 1, 5, { jobTier: 4, requiredTotalBasePoints: 30 }),
    ],
    connections: [
        { from: 1, to: 3 },
        { from: 3, to: 4 },
    ],
};

const engine = createEngine(data);
const state = (level, points = {}) => ({ level, points });
const types = (reasons) => reasons.map((r) => r.type);

test('point pools', () => {
    assert.equal(basePool(5), 2);
    assert.equal(basePool(39), 70);
    assert.equal(basePool(59), 110);
    assert.equal(basePool(80), 110);
    assert.equal(fourthPool(59), 0);
    assert.equal(fourthPool(60), 2);
    assert.equal(fourthPool(80), 42);
});

test('job tier from level', () => {
    assert.equal(jobTierForLevel(5), 1);
    assert.equal(jobTierForLevel(23), 1);
    assert.equal(jobTierForLevel(24), 2);
    assert.equal(jobTierForLevel(39), 2);
    assert.equal(jobTierForLevel(40), 3);
    assert.equal(jobTierForLevel(59), 3);
    assert.equal(jobTierForLevel(60), 4);
});

test('adding a point spends from the base pool', () => {
    const s = engine.add(state(10), 1);
    assert.deepEqual(s.points, { 1: 1 });
    assert.equal(engine.summary(s).baseAvailable, 11);
});

test('add does not mutate the input state', () => {
    const before = state(10);
    engine.add(before, 1);
    assert.deepEqual(before.points, {});
});

test('row requirement blocks until the tree has enough points', () => {
    assert.deepEqual(types(engine.blockers(state(40), 2)), ['tree_points']);
    const s = engine.add(state(40, { 1: 4 }), 2);
    assert.deepEqual(s.points, { 1: 4 }); // unchanged
    assert.deepEqual(engine.add(state(40, { 1: 5 }), 2).points, { 1: 5, 2: 1 });
});

test('prerequisite must be maxed', () => {
    assert.ok(types(engine.blockers(state(40, { 1: 4, 10: 1 }), 3)).includes('prerequisite'));
    assert.deepEqual(engine.blockers(state(40, { 1: 5 }), 3), []);
});

test('job tier blocks', () => {
    const s = state(23, { 1: 5, 3: 1, 2: 4 }); // tree total 10
    assert.deepEqual(types(engine.blockers(s, 4)), ['job_tier']);
    assert.deepEqual(engine.blockers({ ...s, level: 24 }, 4), []);
});

test('maxed skill and empty pool block', () => {
    assert.ok(types(engine.blockers(state(40, { 1: 5 }), 1)).includes('maxed'));
    assert.ok(types(engine.blockers(state(5, { 10: 2 }), 1)).includes('no_points'));
});

test('ctrl + click adds as many points as possible', () => {
    assert.equal(engine.add(state(40), 1, { max: true }).points[1], 5);
    assert.equal(engine.add(state(6), 1, { max: true }).points[1], 4); // only 4 points available
});

test('removing from a prerequisite cascades to all dependents', () => {
    const s = state(40, { 1: 5, 3: 1, 2: 4, 4: 3 });
    const after = engine.remove(s, 1);
    assert.deepEqual(after.points, { 1: 4 }); // C and D reset, B too (row above has 4 < 5)
    assert.deepEqual(engine.reduced(s, after).sort(), [1, 2, 3, 4]);
});

test('only points in the rows above count toward a row requirement', () => {
    // 5 in A (row 0), 4 in B (row 1, needs 5). Removing 1 from A leaves 4 above B -> B resets.
    assert.deepEqual(engine.remove(state(40, { 1: 5, 2: 4 }), 1).points, { 1: 4 });
    // Points in B's own row don't help it: B5 + C1 can't hold B up without A.
    assert.deepEqual(engine.normalize(state(40, { 2: 5, 3: 1 })).points, {});
});

test('deeper rows cannot hold themselves up', () => {
    // A5 + B5 = 10 above K (row 2, needs 10). Removing 1 from A -> B and K both reset.
    assert.deepEqual(engine.remove(state(40, { 1: 5, 2: 5, 6: 5 }), 1).points, { 1: 4 });
    // K's own 5 points never count toward its 10.
    assert.ok(types(engine.blockers(state(40, { 1: 5, 6: 4 }), 6)).includes('tree_points'));
});

test('ctrl + right click returns a skill to 0 and cascades', () => {
    assert.deepEqual(engine.remove(state(40, { 1: 5, 3: 1 }), 1, { all: true }).points, {});
});

test('lowering the level removes points from the deepest skills first', () => {
    // level 9 = 10 points: A5 + B5. Level 8 = 8 points -> B loses 2 (deepest row).
    const after = engine.setLevel(state(9, { 1: 5, 2: 5 }), 8);
    assert.deepEqual(after.points, { 1: 5, 2: 3 });
    assert.equal(engine.summary(after).baseAvailable, 0);
});

test('lowering the level below a job tier resets those skills', () => {
    const after = engine.setLevel(state(24, { 1: 5, 3: 1, 2: 4, 4: 1 }), 23);
    assert.deepEqual(after.points, { 1: 5, 3: 1, 2: 4 });
});

test('levels above 59 do not add base points', () => {
    const s = engine.summary(state(80));
    assert.equal(s.basePool, 110);
    assert.equal(s.fourthPool, 42);
});

test('tree 4 uses only the fourth-job pool', () => {
    let s = state(60, { 1: 5, 2: 5 }); // 10 in tree 1, fourth pool = 2
    s = engine.add(s, 30);
    s = engine.add(s, 30);
    assert.equal(s.points[30], 2);
    assert.ok(types(engine.blockers(s, 30)).includes('no_points'));
    assert.equal(engine.summary(s).baseAvailable, 100); // base pool untouched
});

test('tree 4 specific-tree investment requirement', () => {
    assert.ok(types(engine.blockers(state(60, { 1: 5 }), 30)).includes('tree_investment'));
    assert.deepEqual(engine.blockers(state(60, { 1: 5, 2: 5 }), 30), []);
});

test('tree 4 total base points requirement', () => {
    assert.ok(types(engine.blockers(state(60, { 1: 5, 2: 5 }), 31)).includes('base_points'));
});

test('resetting an original tree resets tree 4 skills that depended on it', () => {
    const after = engine.resetTree(state(60, { 1: 5, 2: 5, 10: 3, 30: 2 }), 1);
    assert.deepEqual(after.points, { 10: 3 });
});

test('reset one tree and reset all', () => {
    const s = state(40, { 1: 5, 10: 3, 20: 2 });
    assert.deepEqual(engine.resetTree(s, 2).points, { 1: 5, 20: 2 });
    assert.deepEqual(engine.resetAll(s).points, {});
});

test('invalid data is rejected', () => {
    assert.throws(() => createEngine({ ...data, connections: [{ from: 1, to: 999 }] }));
    assert.throws(() => createEngine({ ...data, skills: [skill(1, 99, 0, 0, 5)] }));
});

test('invalid level is rejected', () => {
    assert.throws(() => engine.setLevel(state(10), 4));
    assert.throws(() => engine.setLevel(state(10), 10.5));
});