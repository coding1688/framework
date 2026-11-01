import { request } from '@/shared/api/client';

export const accountApi = {
    profile: data => request('/profile', { method: 'POST', data }),
    password: data => request('/password', { method: 'POST', data }),
    email: data => request('/email', { method: 'POST', data }),
    avatar: data => request('/avatar', { method: 'POST', data }),
    unlink: provider => request(`/oauth/${provider}/unlink`, { method: 'POST', data: {} }),
    resend: () => request('/email/verification-notification', { method: 'POST' }),
};
