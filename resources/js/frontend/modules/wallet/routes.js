export const walletRoutes = [
    { path: 'wallet', name: 'wallet', component: () => import('./views/WalletView.vue'), meta: { title: '我的钱包', description: '查看资产余额与每一笔账变记录。', icon: 'wallet' } },
];
