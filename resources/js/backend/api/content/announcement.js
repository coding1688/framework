import { request } from '@/utils/request';

export const announcementApi = {
    list: options => request('content.announcement.list', options),
    detail: options => request('content.announcement.detail', options),
    create: options => request('content.announcement.create', options),
    update: options => request('content.announcement.update', options),
    status: options => request('content.announcement.status', options),
    delete: options => request('content.announcement.delete', options),
};
