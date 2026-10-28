import { reactive, ref, watch } from 'vue';
import { readStorage, writeStorage } from '@/utils/storage';
import { config } from '@/config';

const defaults = Object.freeze({ ...config.ui.theme.defaults });
const allowed = config.ui.theme.options;
const systemMode = window.matchMedia('(prefers-color-scheme: dark)');
export const preferences = reactive(normalize(readStorage('themePreferences', {})));
export const collapsed = ref(readStorage('sidebarCollapsed', false) === true);

function normalize(values = {}) {
    return Object.fromEntries(Object.entries(allowed).map(([key, options]) => [key, options.includes(values[key]) ? values[key] : defaults[key]]));
}

function renderPreferences() {
    const mode = preferences.mode === 'system' ? (systemMode.matches ? 'dark' : 'light') : preferences.mode;
    const root = document.documentElement;
    root.dataset.theme = mode;
    root.dataset.preset = preferences.preset;
    root.dataset.density = preferences.density;
    root.dataset.radius = preferences.radius;
    root.dataset.buttonSize = preferences.button_size;
    root.style.colorScheme = mode;
}

export function applyPreferences(values = {}) {
    Object.assign(preferences, normalize({ ...preferences, ...values }));
    renderPreferences();
}

export function resetPreferences() {
    applyPreferences(defaults);
}

watch(preferences, value => {
    writeStorage('themePreferences', { ...value });
    renderPreferences();
}, { deep: true });
watch(collapsed, value => writeStorage('sidebarCollapsed', value));
systemMode.addEventListener('change', renderPreferences);
renderPreferences();
