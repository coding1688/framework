import { request } from '@/shared/api/client';

export const authApi = {
    user: () => request('/user'),
    login: data => request('/login', { method: 'POST', data }),
    register: data => request('/register', { method: 'POST', data }),
    forgotPassword: data => request('/password/forgot', { method: 'POST', data }),
    resetPassword: data => request('/password/reset', { method: 'POST', data }),
    logout: () => request('/logout', { method: 'POST' }),
};
