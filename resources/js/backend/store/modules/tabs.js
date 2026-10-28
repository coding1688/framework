import { ref } from 'vue';
import { config } from '@/config';
export const tabs = ref([config.pages.home]);
export function visitTab(path) { if (!tabs.value.includes(path)) tabs.value.push(path); }
export function closeTab(path) {
    if (path !== config.pages.home) tabs.value = tabs.value.filter(item => item !== path);
    return tabs.value.at(-1) || config.pages.home;
}
export function pruneTabs(allowed) { tabs.value = tabs.value.filter(path => path === config.pages.home || allowed(path)); }
export function resetTabs() { tabs.value = [config.pages.home]; }
window.addEventListener('backend:unauthorized', resetTabs);
