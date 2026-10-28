export function withDefaultPermissionGroup(items, selectedIds, permission) {
    const allowedIds = new Set(items.map(item => item.id));
    const selected = new Set(selectedIds.filter(id => allowedIds.has(id)));
    const root = items.find(item => item.permission === permission && item.type === 'directory');
    if (!root) return [...selected];
    if (!items.some(item => item.parent_id === root.id)) return [...selected];
    selected.add(root.id);
    return [...selected];
}
