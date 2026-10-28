export function buildTree(rows = [], idKey = 'id', parentKey = 'parent_id') {
    const nodes = new Map(rows.map(row => [row[idKey], { ...row, children: [] }]));
    const roots = [];

    for (const node of nodes.values()) {
        const parent = nodes.get(node[parentKey]);
        if (parent) parent.children.push(node);
        else roots.push(node);
    }

    return roots;
}

export function branchIds(rows = [], rootId, idKey = 'id', parentKey = 'parent_id') {
    const ids = new Set([rootId]);
    let changed = true;
    while (changed) {
        changed = false;
        for (const row of rows) {
            if (!ids.has(row[idKey]) && ids.has(row[parentKey])) {
                ids.add(row[idKey]);
                changed = true;
            }
        }
    }

    return ids;
}
