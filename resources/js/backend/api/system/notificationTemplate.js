import { request } from '@/utils/request';

export const notificationTemplateApi = {
    list: options => request('system.notificationTemplate.list', options),
    detail: options => request('system.notificationTemplate.detail', options),
    create: options => request('system.notificationTemplate.create', options),
    update: options => request('system.notificationTemplate.update', options),
    delete: options => request('system.notificationTemplate.delete', options),
};
