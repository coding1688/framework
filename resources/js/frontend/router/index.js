import { createRouter, createWebHistory } from 'vue-router';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { authRoutes } from '@/modules/auth/routes';
import { accountRoutes } from '@/modules/account/routes';
import { walletRoutes } from '@/modules/wallet/routes';
import { notificationRoutes } from '@/modules/notification/routes';
import { useSession } from '@/shared/composables/useSession';

export const router = createRouter({
    history: createWebHistory(),
    scrollBehavior(to) {
        return to.hash ? { el: to.hash, top: 24 } : { top: 0 };
    },
    routes: [
        {
            path: '/',
            component: SiteLayout,
            children: [
                { path: '', name: 'home', component: () => import('@/modules/home/views/HomeView.vue') },
                ...authRoutes,
                {
                    path: 'manage',
                    component: () => import('@/layouts/ManagementLayout.vue'),
                    meta: { requiresAuth: true },
                    children: [
                        { path: '', redirect: { name: 'account' } },
                        ...accountRoutes,
                        ...walletRoutes,
                        ...notificationRoutes,
                    ],
                },
            ],
        },
        { path: '/:pathMatch(.*)*', redirect: { name: 'home' } },
    ],
});

router.beforeEach(async to => {
    const { user, initialize } = useSession();
    await initialize();
    if (to.matched.some(record => record.meta.requiresAuth) && !user.value) {
        return { name: 'auth.login', query: { redirect: to.fullPath } };
    }
    if (to.matched.some(record => record.meta.guestOnly) && user.value) {
        return { name: 'account' };
    }
    return true;
});

router.afterEach(to => {
    document.title = `${to.meta.title || (to.meta.mode ? '账户登录' : '开发文档')} · Framework`;
});
