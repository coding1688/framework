export const notificationRoutes = [
    { path: 'notifications', name: 'notifications', component: () => import('./views/NotificationView.vue'), meta: { title: '通知中心', description: '及时掌握账户动态，管理消息接收方式。', icon: 'notification' } },
];
