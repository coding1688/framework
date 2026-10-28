import { reactive } from 'vue';
import { authApi } from '@/api/auth';
import { applyPreferences, resetPreferences } from '@/store/modules/settings';

export const state = reactive({ user: null, permissions: [], menus: [], navigation: [] });
let pendingIdentity;
let generation = 0;
let controller;

export function clearIdentity() {
    generation++;
    controller?.abort();
    pendingIdentity = null;
    Object.assign(state, { user: null, permissions: [], menus: [], navigation: [] });
    resetPreferences();
}
export function userinfo(refresh = false) {
    if (pendingIdentity) return pendingIdentity;
    if (!refresh && state.user) return Promise.resolve(state.user);
    const current = generation;
    controller = new AbortController();
    const pending = authApi.userinfo({ signal: controller.signal }).then(result => {
        if (current !== generation) throw new DOMException('Identity was cleared', 'AbortError');
        Object.assign(state, { user: result.data, permissions: result.permissions || [], menus: result.menus || [], navigation: result.navigation || [] });
        applyPreferences(result.data.preferences);
        return state.user;
    }).finally(() => { if (pendingIdentity === pending) pendingIdentity = null; });
    pendingIdentity = pending;
    return pending;
}
window.addEventListener('backend:unauthorized', clearIdentity);
