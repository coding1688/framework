export const authRoutes = [
    { path: 'auth/login', name: 'auth.login', component: () => import('./views/AuthView.vue'), meta: { mode: 'login', guestOnly: true } },
    { path: 'auth/register', name: 'auth.register', component: () => import('./views/AuthView.vue'), meta: { mode: 'register', guestOnly: true } },
    { path: 'auth/forgot', name: 'auth.forgot', component: () => import('./views/AuthView.vue'), meta: { mode: 'forgot' } },
    { path: 'auth/reset', name: 'auth.reset', component: () => import('./views/AuthView.vue'), meta: { mode: 'reset' } },
];
