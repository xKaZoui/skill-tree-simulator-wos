// A build in the page URL after "#": "level:id-points,id-points", with Skill Point Books after the level as "b" with their count (e.g. "80b7:12-5")

export function encodeBuild(state) {
    const pairs = Object.entries(state.points)
        .filter(([, n]) => n > 0)
        .map(([id, n]) => [Number(id), n])
        .sort((a, b) => a[0] - b[0])
        .map(([id, n]) => `${id}-${n}`);
    const books = state.books > 0 ? `b${state.books}` : '';
    return `${state.level}${books}:${pairs.join(',')}`;
}

export function decodeBuild(text) {
    const match = /^(\d{1,3})(?:b(\d{1,2}))?:((?:\d+-\d+)(?:,\d+-\d+)*)?$/.exec(text ?? '');
    if (!match) return null;
    const points = {};
    for (const pair of (match[3] ?? '').split(',').filter(Boolean)) {
        const [id, n] = pair.split('-').map(Number);
        if (n > 0) points[id] = n;
    }
    return { level: Number(match[1]), books: Number(match[2] ?? 0), points };
}
