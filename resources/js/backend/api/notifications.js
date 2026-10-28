import { request } from '@/utils/request';

export const notificationApi = {
    list: options => request('backend.notifications.list', options),
    read: options => request('backend.notifications.read', options),
    readAll: options => request('backend.notifications.readAll', options),
    preferences: options => request('backend.notifications.preferences', options),
};
