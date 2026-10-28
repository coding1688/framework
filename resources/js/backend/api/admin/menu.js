import { request } from '@/utils/request';

export const menuApi = {
    sync: options => request('admin.menu.sync', options),
    list: options => request('admin.menu.list', options),
    create: options => request('admin.menu.create', options),
    update: options => request('admin.menu.update', options),
    delete: options => request('admin.menu.delete', options),
};
