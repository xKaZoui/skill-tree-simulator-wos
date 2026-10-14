// A build in the page URL after "#"" "level:id-points,id-points"

export function encodeBuild(state) {
    const pairs = Object.entries(state.points)
        .filter(([, n]) => n > 0)
        .map(([id, n]) => [Number(id), n])
        .sort((a, b) => a[0] - b[0])
        .map(([id, n]) => `${id}-${n}`);
    return `${state.level}:${pairs.join(',')}`;
}

export function decodeBuild(text) {
    const match = /^(\d{1,3}):((?:\d+-\d+)(?:,\d+-\d+)*)?$/.exec(text ?? '');
    if (!match) return null;
    const points = {};
    for (const pair of (match[2] ?? '').split(',').filter(Boolean)) {
        const [id, n] = pair.split('-').map(Number);
        if (n > 0) points[id] = n;
    }
    return { level: Number(match[1]), points };
}
