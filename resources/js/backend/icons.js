import iconNames from '@/components/ui/iconNames.json';

// Icon names from Remix Icon 4.3.0 (Apache-2.0); retain legacy stored menu names.
export const legacyIcons = {
    User: 'user-line', OfficeBuilding: 'building-line', Menu: 'menu-line',
    Document: 'file-text-line', Folder: 'folder-line', House: 'home-line',
    Lock: 'lock-line', Setting: 'settings-3-line', Monitor: 'computer-line', Bell: 'notification-3-line',
};
export const icons = [...Object.keys(legacyIcons), ...iconNames];
const availableIcons = new Set(iconNames);
export function resolveIcon(icon) {
    const name = legacyIcons[icon] || String(icon || '').replace(/^ri-/, '');
    return availableIcons.has(name) ? name : 'folder-line';
}
