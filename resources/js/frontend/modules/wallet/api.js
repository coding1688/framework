import { request } from '@/shared/api/client';

export const walletApi = {
    list: () => request('/api/v1/wallets/list'),
    history: () => request('/api/v1/wallets/history'),
};
