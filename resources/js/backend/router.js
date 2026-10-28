import { watch } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { config } from '@/config';
import { state, userinfo } from '@/store/modules/user';

// The server chooses routes; only locally built Vue pages may be loaded.
const pages = import.meta.glob('./pages/**/List.vue');
const removeRoutes = [];
export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: config.pages.login, name: 'login', component: () => import('@/pages/auth/Login.vue') },
        { path: config.pages.home, name: 'home', component: () => import('@/pages/home/Index.vue') },
        { path: config.pages.profile, name: 'profile', component: () => import('@/pages/auth/Profile.vue') },
        { path: '/:pathMatch(.*)*', name: 'unresolved', component: () => import('@/pages/home/Index.vue') },
    ],
});

function registerMenus(menus) {
    removeRoutes.splice(0).forEach(remove => remove());
    const paths = new Set(Object.values(config.pages));
    const names = new Set(['login', 'home', 'profile', 'unresolved']);
    for (const menu of menus) {
        const component = pages[`./pages/${menu.component}.vue`];
        if (!component || !menu.path?.startsWith('/') || menu.path.startsWith('//') || paths.has(menu.path) || names.has(menu.route_name)) continue;
        paths.add(menu.path);
        names.add(menu.route_name);
        removeRoutes.push(router.addRoute({
            path: menu.path, name: menu.route_name, component,
            meta: { title: menu.title, icon: menu.icon, menuId: menu.id, context: menu.context || {}, canonicalPermission: menu.canonical_permission || menu.route_name },
        }));
    }
}
watch(() => state.menus, registerMenus, { flush: 'sync' });

router.beforeEach(async to => {
    if (to.name === 'login') return state.user ? { name: 'home' } : true;
    try { await userinfo(); } catch (error) {
        if (error.status === 401) return { name: 'login' };
        throw error;
    }
    const resolved = router.resolve(to.fullPath);
    if (resolved.name === 'unresolved') return { name: 'home' };
    if (resolved.name !== to.name) return { path: to.path, query: to.query, hash: to.hash, replace: true };
    return true;
});
