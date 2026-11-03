export function buildCategoryTree(items, type = '') {
    const filtered = items.filter(item => !type || item.type === type);
    const nodes = new Map(filtered.map(item => [item.id, { ...item, value: item.id, label: item.name, children: [] }]));
    const roots = [];
    for (const node of nodes.values()) {
        const parent = nodes.get(node.parent_id);
        if (parent) parent.children.push(node);
        else roots.push(node);
    }
    return roots;
}

export function withoutCategoryBranch(items, id) {
    if (!id) return items;
    const excluded = new Set([id]);
    let changed = true;
    while (changed) {
        changed = false;
        for (const item of items) {
            if (item.parent_id && excluded.has(item.parent_id) && !excluded.has(item.id)) {
                excluded.add(item.id);
                changed = true;
            }
        }
    }
    return items.filter(item => !excluded.has(item.id));
}
