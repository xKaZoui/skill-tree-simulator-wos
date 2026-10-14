// Skill card (tooltip)

// Level value placeholders in descriptions and their colours
export const VALUE_KEYS = ['x', 'y', 'z', 'a', 'b'];
const VALUE_CLASSES = {
    x: 'bg-green-400',
    y: 'bg-red-400',
    z: 'bg-blue-400',
    a: 'bg-orange-400',
    b: 'bg-teal-400',
};

// Name colour: red without points, otherwise by skill type
const NAME_CLASSES = {
    none: 'text-red-500',
    attack: 'text-orange-400',
    passive: 'text-blue-400',
    support: 'text-yellow-400',
};

// MP value the game uses for "all your MP"
const ALL_MP = 9999;

const escapeHtml = (text) => String(text)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');

// Arabic counted nouns = 2-10 take the plural, the rest the singular
const arabicPlural = (n) => n >= 2 && n <= 10;

export function formatCooltime(seconds, labels, locale) {
    const ar = locale === 'ar';
    const unit = (n) => {
        if (ar) return arabicPlural(n) ? labels.seconds : labels.second;
        return n === 1 ? labels.second : labels.seconds;
    };

    if (seconds < 60) return `${seconds} ${unit(seconds)}`;

    const minutes = Math.floor(seconds / 60);
    const rest = seconds % 60;
    const minuteLabel = minutes === 1 ? labels.minute : labels.minutes;
    const restText = rest ? ` ${rest} ${unit(rest)}` : '';

    // "1 minute 30 seconds (90 seconds)"
    return `${minutes} ${minuteLabel}${restText} (${seconds} ${ar ? labels.second : labels.seconds})`;
}

export function describe(description, levelValues) {
    let html = escapeHtml(description ?? '');

    // --note-- parts in purple
    html = html.replace(/--(.*?)--/g, (match, note) => `<span class="text-violet-300 ltr:font-medium rtl:font-bold">${note}</span>`);

    for (const key of VALUE_KEYS) {
        // no value known for it = show "?"
        const value = levelValues?.[key] ?? '?';
        html = html.replaceAll(`:${key}`, `<span class="${VALUE_CLASSES[key]} px-px py-px text-base font-bold text-black">${escapeHtml(value)}</span>`);
    }

    return html;
}

export function levelsTable(levels, current) {
    if (!levels?.length) return null;

    const keys = VALUE_KEYS.filter((key) => levels.some((row) => row[key] !== undefined && row[key] !== null));

    return {
        keys,
        rows: levels
            .filter((row) => row.level > 0)
            .map((row) => ({
                level: row.level,
                current: row.level === current,
                values: keys.map((key) => ({
                    key,
                    className: VALUE_CLASSES[key],
                    text: row[key] === 0 ? 'N/A' : (row[key] ?? ''),
                })),
            })),
    };
}

function jobText(tier, labels) {
    const names = labels.job_lvls ?? {};
    const name = tier >= 4 ? (names.fourth ?? names.third)
        : tier === 3 ? names.third
            : tier === 2 ? names.second
                : names.first;

    return `${labels.above} ${name}`;
}

function pointsText(points, treeName, labels) {
    const word = points <= 10 ? labels.plural_points_under_11 : labels.plural_points_above_11;

    return `${points} ${word} ${treeName}`;
}

function requiredPointsText(skill, treeName, labels) {
    if (skill.requiredPoints) return pointsText(skill.requiredPoints, treeName, labels);

    // tree 4 first row = given with the fourth job change
    return skill.jobTier >= 4 ? labels.learned_job_changed : labels.learned_instantly;
}

// "60 Points total in: Dragon Slayer, Bloody Song and Soul of Master."
// Arabic tree names drop their "شجرة" prefix = the label already says "من شجرات"
function totalPointsText({ points, treeNames }, labels, comma) {
    const names = treeNames.map((name) => name.replace(/^شجرة\s+/, ''));
    const last = names.pop();
    const list = names.length ? `${names.join(comma)}${labels.list_and}${last}` : last;

    return `${points} ${labels.total_points_in} ${list}.`;
}

/**
 * skill:    payload skill
 * context:  { points, canAdd, labels, locale, treeName, required: [{ name, maxPoints }],
 *             investment?: { points, treeName }, baseTotal?: { points, treeNames } }   (tree 4 only)
 */
export function skillCard(skill, context) {
    const { points, canAdd, labels, locale, treeName, required, investment, baseTotal } = context;
    const tip = skill.tip ?? {};
    const comma = locale === 'ar' ? '، ' : ', ';

    const rows = [
        [labels.job, jobText(skill.jobTier, labels)],
        [labels.required_skill, required.length ? `${required.map((r) => `${r.name} ${r.maxPoints}/${r.maxPoints}`).join(comma)}.` : labels.do_not_have],
        [labels.required_points, requiredPointsText(skill, treeName, labels)],
        // tree 4 = attack skills need points in one base tree, passives a total over the 3 base trees
        investment?.points ? [labels.required_tree_points, pointsText(investment.points, investment.treeName, labels)] : null,
        baseTotal?.points ? [labels.required_total_points, totalPointsText(baseTotal, labels, comma)] : null,
        [labels.skill_type, tip.typeName],
        // stat skills of tree 4 use no weapon
        [labels.weapon, tip.weapon || labels.do_not_have],
        // passive skills never show an attribute
        tip.type !== 'passive' ? [labels.attribute, tip.attribute || labels.does_not_exist] : null,
        tip.cooltime ? [labels.cool_time, formatCooltime(tip.cooltime, labels, locale)] : null,
        tip.mpUse ? [labels.mp_use, tip.mpUse === ALL_MP ? labels.allmp : `${tip.mpUse} ${labels.MP}`] : null,
        tip.attackType ? [labels.attack_type, tip.attackType] : null,
        tip.selectedTarget ? [labels.selected_target, tip.selectedTarget] : null,
        tip.applyTarget ? [labels.apply_target, tip.applyTarget] : null,
    ].filter((row) => row && row[1]);

    let status = null;
    if (points === 0) status = canAdd ? labels.can_learn : labels.not_activated;

    return {
        id: skill.id,
        image: skill.image,
        name: skill.name,
        level: `${labels.Lv} ${points}/${skill.maxPoints}`,
        nameClass: points === 0 ? NAME_CLASSES.none : (NAME_CLASSES[tip.type] ?? NAME_CLASSES.attack),
        textClass: points === 0 ? 'text-gray-400' : 'text-white',
        status,
        rows: rows.map(([label, value]) => ({ label, value })),
        description: describe(tip.description, tip.levels?.find((row) => row.level === points)),
        levels: levelsTable(tip.levels, points),
    };
}
