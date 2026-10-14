// Skill tree rules engine.
// state = { level: number, points: { [skillId]: number } }

export const MIN_LEVEL = 5;
export const BASE_POOL_MAX_LEVEL = 59;
export const POINTS_PER_LEVEL = 2;
export const FOURTH_TREE_POSITION = 4;

// Minimum level for each job tier
export const JOB_TIER_LEVELS = { 1: 5, 2: 24, 3: 40, 4: 60 };

export function basePool(level) {
    const capped = Math.min(Math.max(level, MIN_LEVEL - 1), BASE_POOL_MAX_LEVEL);
    return (capped - (MIN_LEVEL - 1)) * POINTS_PER_LEVEL;
}

export function fourthPool(level) {
    return Math.max(0, level - BASE_POOL_MAX_LEVEL) * POINTS_PER_LEVEL;
}

export function jobTierForLevel(level) {
    let tier = 0;
    for (const [t, minLevel] of Object.entries(JOB_TIER_LEVELS)) {
        if (level >= minLevel) tier = Math.max(tier, Number(t));
    }
    return tier;
}

/**
 * data = {
 *   trees:       [{ id, position }],
 *   skills:      [{ id, treeId, row, column, maxPoints, jobTier, requiredPoints, requiredTotalBasePoints?, requiredInvestmentTreeId?, requiredInvestmentPoints? }],
 *   connections: [{ from, to }],   // from skill must be maxed before to accepts points
 * }
 */
export function createEngine(data) {
    const trees = new Map(data.trees.map((t) => [t.id, t]));
    const skills = new Map(data.skills.map((s) => [s.id, s]));
    const requires = new Map(); // skillId -> [prerequisite ids]

    for (const s of skills.values()) {
        if (!trees.has(s.treeId)) throw new Error(`Skill ${s.id}: unknown tree ${s.treeId}.`);
        if (s.requiredInvestmentTreeId != null && !trees.has(s.requiredInvestmentTreeId)) {
            throw new Error(`Skill ${s.id}: unknown investment tree ${s.requiredInvestmentTreeId}.`);
        }
        requires.set(s.id, []);
    }
    for (const { from, to } of data.connections) {
        if (!skills.has(from) || !skills.has(to)) throw new Error(`Connection ${from} -> ${to}: unknown skill.`);
        requires.get(to).push(from);
    }

    const isFourth = (skill) => trees.get(skill.treeId).position === FOURTH_TREE_POSITION;
    const pointsOf = (state, id) => state.points[id] ?? 0;

    function treeTotals(state) {
        const totals = new Map([...trees.keys()].map((id) => [id, 0]));
        for (const [id, n] of Object.entries(state.points)) {
            const skill = skills.get(Number(id));
            if (skill && n > 0) totals.set(skill.treeId, totals.get(skill.treeId) + n);
        }
        return totals;
    }

    function summary(state) {
        const totals = treeTotals(state);
        let baseSpent = 0;
        let fourthSpent = 0;
        for (const [treeId, n] of totals) {
            if (trees.get(treeId).position === FOURTH_TREE_POSITION) fourthSpent += n;
            else baseSpent += n;
        }
        const base = basePool(state.level);
        const fourth = fourthPool(state.level);
        return {
            treeTotals: totals,
            baseSpent,
            fourthSpent,
            basePool: base,
            fourthPool: fourth,
            baseAvailable: base - baseSpent,
            fourthAvailable: fourth - fourthSpent,
            jobTier: jobTierForLevel(state.level),
        };
    }

    // Points in the same tree, in the rows above this skill only.
    function pointsAbove(state, skill) {
        let total = 0;
        for (const [id, n] of Object.entries(state.points)) {
            const other = skills.get(Number(id));
            if (other && other.treeId === skill.treeId && other.row < skill.row) total += n;
        }
        return total;
    }

    function unmetRequirements(state, skillId, totals = treeTotals(state)) {
        const skill = skills.get(skillId);
        const unmet = [];

        if (skill.jobTier > jobTierForLevel(state.level)) {
            unmet.push({ type: 'job_tier', jobTier: skill.jobTier, level: JOB_TIER_LEVELS[skill.jobTier] });
        }

        const above = pointsAbove(state, skill);
        if ((skill.requiredPoints ?? 0) > above) {
            unmet.push({ type: 'tree_points', required: skill.requiredPoints, current: above });
        }

        for (const preId of requires.get(skillId)) {
            if (pointsOf(state, preId) < skills.get(preId).maxPoints) {
                unmet.push({ type: 'prerequisite', skillId: preId });
            }
        }

        if (skill.requiredTotalBasePoints != null) {
            let baseTotal = 0;
            for (const [treeId, n] of totals) {
                if (trees.get(treeId).position !== FOURTH_TREE_POSITION) baseTotal += n;
            }
            if (baseTotal < skill.requiredTotalBasePoints) {
                unmet.push({ type: 'base_points', required: skill.requiredTotalBasePoints, current: baseTotal });
            }
        }

        if (skill.requiredInvestmentTreeId != null && skill.requiredInvestmentPoints != null) {
            const invested = totals.get(skill.requiredInvestmentTreeId);
            if (invested < skill.requiredInvestmentPoints) {
                unmet.push({
                    type: 'tree_investment',
                    treeId: skill.requiredInvestmentTreeId,
                    required: skill.requiredInvestmentPoints,
                    current: invested,
                });
            }
        }

        return unmet;
    }

    function blockers(state, skillId) {
        const skill = skills.get(skillId);
        if (!skill) throw new Error(`Unknown skill ${skillId}.`);

        const reasons = unmetRequirements(state, skillId);
        if (pointsOf(state, skillId) >= skill.maxPoints) reasons.unshift({ type: 'maxed' });

        const s = summary(state);
        const available = isFourth(skill) ? s.fourthAvailable : s.baseAvailable;
        if (available <= 0) reasons.push({ type: 'no_points', pool: isFourth(skill) ? 'fourth' : 'base' });

        return reasons;
    }

    const canAdd = (state, skillId) => blockers(state, skillId).length === 0;

    function withPoints(state, points) {
        return { ...state, points };
    }

    function setPoints(points, id, n) {
        const next = { ...points };
        if (n > 0) next[id] = n;
        else delete next[id];
        return next;
    }

    // Resets everything that breaks a rule until the build is valid

    function normalize(state) {
        let points = { ...state.points };

        for (const [id, n] of Object.entries(points)) {
            const skill = skills.get(Number(id));
            if (!skill || !(n > 0)) delete points[id];
            else if (n > skill.maxPoints) points[id] = skill.maxPoints;
        }

        for (; ;) {
            // drop invalid skills until stable
            let changed = true;
            while (changed) {
                changed = false;
                const current = withPoints(state, points);
                const totals = treeTotals(current);
                for (const id of Object.keys(points)) {
                    if (unmetRequirements(current, Number(id), totals).length > 0) {
                        points = setPoints(points, id, 0);
                        changed = true;
                        break; // totals changed, re-evaluate from scratch
                    }
                }
            }

            // fix overspent pools
            const s = summary(withPoints(state, points));
            const overBase = s.baseAvailable < 0;
            const overFourth = s.fourthAvailable < 0;
            if (!overBase && !overFourth) break;

            const candidates = Object.keys(points)
                .map(Number)
                .filter((id) => (isFourth(skills.get(id)) ? overFourth : overBase))
                .sort((a, b) => {
                    const sa = skills.get(a);
                    const sb = skills.get(b);
                    return (
                        sb.row - sa.row ||
                        trees.get(sb.treeId).position - trees.get(sa.treeId).position ||
                        sb.column - sa.column
                    );
                });
            const deepest = candidates[0];
            points = setPoints(points, deepest, points[deepest] - 1);
        }

        return withPoints(state, points);
    }

    // Actions
    // Left click: +1 point
    // Ctrl + left click: adds as many as possible
    function add(state, skillId, { max = false } = {}) {
        if (!canAdd(state, skillId)) return state;
        const skill = skills.get(skillId);
        const s = summary(state);
        const available = isFourth(skill) ? s.fourthAvailable : s.baseAvailable;
        const current = pointsOf(state, skillId);
        const amount = max ? Math.min(skill.maxPoints - current, available) : 1;
        return withPoints(state, setPoints(state.points, skillId, current + amount));
    }

    // Right click: -1 point
    // Ctrl + right click: returns the skill to 0
    function remove(state, skillId, { all = false } = {}) {
        const current = pointsOf(state, skillId);
        if (current === 0) return state;
        return normalize(withPoints(state, setPoints(state.points, skillId, all ? 0 : current - 1)));
    }

    function resetTree(state, treeId) {
        const points = { ...state.points };
        for (const id of Object.keys(points)) {
            if (skills.get(Number(id))?.treeId === treeId) delete points[id];
        }
        return normalize(withPoints(state, points));
    }

    function resetAll(state) {
        return { ...state, points: {} };
    }

    function setLevel(state, level) {
        if (!Number.isInteger(level) || level < MIN_LEVEL) throw new Error(`Invalid level ${level}.`);
        return normalize({ ...state, level });
    }

    // Skill ids that had points in 'before' and lost some in 'after'
    function reduced(before, after) {
        return Object.keys(before.points)
            .map(Number)
            .filter((id) => pointsOf(after, id) < before.points[id]);
    }

    function emptyState(level = MIN_LEVEL) {
        return { level, points: {} };
    }

    return {
        skills,
        trees,
        requires,
        emptyState,
        treeTotals,
        summary,
        unmetRequirements,
        blockers,
        canAdd,
        normalize,
        add,
        remove,
        resetTree,
        resetAll,
        setLevel,
        reduced,
    };
}