import { request } from '@/shared/api/client';

export const notificationApi = {
    list: () => request('/api/v1/notifications/list'),
    read: id => request(`/api/v1/notifications/read/${id}`, { method: 'POST', data: {} }),
    readAll: () => request('/api/v1/notifications/read-all', { method: 'POST', data: {} }),
    preferences: preferences => request('/api/v1/notifications/preferences', { method: 'POST', data: { preferences } }),
};
