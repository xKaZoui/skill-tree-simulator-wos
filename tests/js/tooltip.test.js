import test from 'node:test';
import assert from 'node:assert/strict';
import { describe, formatCooltime, levelsTable, skillCard } from '../../resources/js/skill-tree/tooltip.js';

const en = {
    job: 'Job', above: 'Above', job_lvls: { first: 'First Job', second: 'Second Job', third: 'Third Job' },
    required_skill: 'Required Skill', do_not_have: 'Do not have', required_points: 'Required Points',
    plural_points_under_11: 'Points in', plural_points_above_11: 'Points in', learned_instantly: 'Learned Instantly',
    skill_type: 'Skill Type', weapon: 'Weapon', attribute: 'Attribute', does_not_exist: 'Does not exist',
    cool_time: 'Cool Time', minute: 'minute', minutes: 'minutes', second: 'second', seconds: 'seconds',
    mp_use: 'MP Use', attack_type: 'Attack Type', selected_target: 'Selected Target', apply_target: 'Apply Target',
    Lv: 'Lv.', allmp: 'All your MP', MP: 'MP', not_activated: 'Not Activated', can_learn: 'You Can Learn',
};
const ar = { ...en, minute: 'دقيقة', minutes: 'دقائق', second: 'ثانية', seconds: 'ثواني' };

const skill = (tip, extra = {}) => ({ id: 1, name: 'Fire Bullet', image: '/i.webp', maxPoints: 5, jobTier: 1, requiredPoints: 0, tip, ...extra });
const ctx = (extra = {}) => ({ points: 0, canAdd: true, labels: en, locale: 'en', treeName: 'Hell Fire', required: [], ...extra });
const labels = (card) => card.rows.map((r) => r.label);

test('cool time in seconds and minutes', () => {
    assert.equal(formatCooltime(1, en, 'en'), '1 second');
    assert.equal(formatCooltime(45, en, 'en'), '45 seconds');
    assert.equal(formatCooltime(60, en, 'en'), '1 minute (60 seconds)');
    assert.equal(formatCooltime(90, en, 'en'), '1 minute 30 seconds (90 seconds)');
    assert.equal(formatCooltime(5, ar, 'ar'), '5 ثواني');
    assert.equal(formatCooltime(12, ar, 'ar'), '12 ثانية');
});

test('description: notes in purple, every placeholder replaced, html escaped', () => {
    const html = describe('Hit for :x% <b> -- note -- and :x again', { level: 1, x: 155 });
    assert.match(html, /text-violet-300[^>]*> note </);
    assert.equal((html.match(/>155</g) ?? []).length, 2);
    assert.match(html, /&lt;b&gt;/);
});

test('levels table skips level 0, shows N/A for 0 and marks the current level', () => {
    const table = levelsTable([{ level: 0, x: 0 }, { level: 1, x: 0, y: 3 }, { level: 2, x: 5, y: 6 }], 2);
    assert.deepEqual(table.keys, ['x', 'y']);
    assert.equal(table.rows.length, 2);
    assert.equal(table.rows[0].values[0].text, 'N/A');
    assert.equal(table.rows[1].current, true);
});

test('empty fields are hidden, passive skills have no attribute', () => {
    const card = skillCard(skill({ type: 'passive', typeName: 'Passive Skill', weapon: 'Staff' }), ctx());
    assert.deepEqual(labels(card), ['Job', 'Required Skill', 'Required Points', 'Skill Type', 'Weapon']);
});

test('attack skill shows attribute fallback and optional fields', () => {
    const card = skillCard(skill({ type: 'attack', typeName: 'Attack Skill', weapon: 'Staff', cooltime: 1, mpUse: 9999, applyTarget: 'Monster' }), ctx());
    const rows = Object.fromEntries(card.rows.map((r) => [r.label, r.value]));
    assert.equal(rows.Attribute, 'Does not exist');
    assert.equal(rows['MP Use'], 'All your MP');
    assert.equal(rows['Apply Target'], 'Monster');
    assert.equal(rows['Attack Type'], undefined);
});

test('required skills, required points and state', () => {
    const card = skillCard(skill({ type: 'support' }, { requiredPoints: 15, jobTier: 2 }), ctx({
        canAdd: false, required: [{ name: 'Focus', maxPoints: 5 }, { name: 'Provoke', maxPoints: 2 }],
    }));
    const rows = Object.fromEntries(card.rows.map((r) => [r.label, r.value]));
    assert.equal(rows['Required Skill'], 'Focus 5/5, Provoke 2/2.');
    assert.equal(rows['Required Points'], '15 Points in Hell Fire');
    assert.equal(rows.Job, 'Above Second Job');
    assert.equal(card.status, 'Not Activated');
    assert.equal(card.nameClass, 'text-red-500');
});

test('with points: no state, colour by type, level text', () => {
    const card = skillCard(skill({ type: 'support' }), ctx({ points: 3 }));
    assert.equal(card.status, null);
    assert.equal(card.nameClass, 'text-yellow-400');
    assert.equal(card.level, 'Lv. 3/5');
});

test('tree 4: own tree row points, base tree points, no level values yet', () => {
    const labels = { ...en, required_tree_points: 'Required Tree Points', required_total_points: 'Required Total Points', learned_job_changed: 'Learned Instantly when Job Changed', total_points_in: 'Points total in:', list_and: ' and ' };
    const rowsOf = (card) => Object.fromEntries(card.rows.map((r) => [r.label, r.value]));

    const attack = skillCard(skill({ type: 'attack', description: 'Hit for :x% for :y.' }, { requiredPoints: 15, jobTier: 4 }), ctx({
        labels, treeName: 'Fourth Tree', investment: { points: 40, treeName: 'Soul of Master' },
    }));
    assert.equal(rowsOf(attack)['Required Points'], '15 Points in Fourth Tree');
    assert.equal(rowsOf(attack)['Required Tree Points'], '40 Points in Soul of Master');
    assert.equal(rowsOf(attack)['Required Total Points'], undefined);
    assert.match(attack.description, /Hit for <span[^>]*>\?<\/span>% for <span[^>]*>\?<\/span>\./);
    assert.equal(attack.levels, null);

    const passive = skillCard(skill({ type: 'support' }, { requiredPoints: 0, jobTier: 4 }), ctx({
        labels, treeName: 'Fourth Tree', baseTotal: { points: 60, treeNames: ['Dragon Slayer', 'Bloody Song', 'Soul of Master'] },
    }));
    assert.equal(rowsOf(passive)['Required Points'], 'Learned Instantly when Job Changed');
    assert.equal(rowsOf(passive)['Required Total Points'], '60 Points total in: Dragon Slayer, Bloody Song and Soul of Master.');

    const arCard = skillCard(skill({ type: 'passive' }, { jobTier: 4 }), ctx({
        locale: 'ar',
        labels: { ...labels, total_points_in: 'نقطة مجموع من شجرات:', list_and: ' و', do_not_have: 'لا يوجد' },
        baseTotal: { points: 60, treeNames: ['شجرة قاهر التنين', 'شجرة أنشودة الهلاك', 'شجرة سيد الأعاصير'] },
    }));
    const arRows = Object.fromEntries(arCard.rows.map((r) => [r.label, r.value]));
    assert.equal(arRows['Required Total Points'], '60 نقطة مجموع من شجرات: قاهر التنين، أنشودة الهلاك وسيد الأعاصير.');
    assert.equal(arRows.Weapon, 'لا يوجد');
});
